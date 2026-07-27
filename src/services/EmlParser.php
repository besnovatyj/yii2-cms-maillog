<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Maillog\services;

/**
 * Разбор `.eml`-письма без внешних зависимостей (только ядро PHP).
 *
 * Обрабатывает то, чем кодирует symfony/mailer (транспорт Yii2):
 *  - заголовки с «мягким переносом» (unfolding) и MIME-encoded-words в Subject/From/To;
 *  - тело Content-Transfer-Encoding: quoted-printable / base64 / 7bit / 8bit;
 *  - произвольный charset → UTF-8;
 *  - multipart/* (alternative, mixed, related) — рекурсивно, с извлечением text/plain и text/html.
 *
 * «Расшифровка» строк вида `=D0=A1=D1=82...` — это штатная кодировка Quoted-Printable, снимается
 * встроенной {@see quoted_printable_decode()}; никакого шифрования в письмах нет.
 */
final class EmlParser
{
    /**
     * @param bool $withBody false — разобрать только заголовки (для списка), не декодируя тело.
     * @return array{subject:string,from:string,to:string,date:string,messageId:string,text:?string,html:?string}
     */
    public function parse(string $raw, bool $withBody = true): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        [$headerBlock, $body] = $this->splitHeadersBody($raw);
        $headers = $this->parseHeaders($headerBlock);

        $text = null;
        $html = null;
        if ($withBody) {
            [$text, $html] = $this->decodeContent($headers, $body);
        }

        return [
            'subject' => isset($headers['subject']) ? $this->decodeMimeHeader($headers['subject']) : '',
            'from' => isset($headers['from']) ? $this->decodeMimeHeader($headers['from']) : '',
            'to' => isset($headers['to']) ? $this->decodeMimeHeader($headers['to']) : '',
            'date' => $headers['date'] ?? '',
            'messageId' => trim($headers['message-id'] ?? '', " \t<>"),
            'text' => $text,
            'html' => $html,
        ];
    }

    /** Разделить блок заголовков и тело по первой пустой строке. */
    private function splitHeadersBody(string $raw): array
    {
        $pos = strpos($raw, "\n\n");
        if ($pos === false) {
            return [$raw, ''];
        }
        return [substr($raw, 0, $pos), substr($raw, $pos + 2)];
    }

    /**
     * Разбор заголовков с разворачиванием продолжений (строки, начинающиеся с пробела/таба).
     *
     * @return array<string,string> имя (в нижнем регистре) => значение
     */
    private function parseHeaders(string $block): array
    {
        $headers = [];
        $current = null;

        foreach (explode("\n", $block) as $line) {
            if ($line === '') {
                continue;
            }
            if (($line[0] === ' ' || $line[0] === "\t") && $current !== null) {
                $headers[$current] .= ' ' . trim($line);
                continue;
            }
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $colon)));
            $value = trim(substr($line, $colon + 1));
            $headers[$name] = isset($headers[$name]) ? $headers[$name] . ', ' . $value : $value;
            $current = $name;
        }

        return $headers;
    }

    /** Декодировать MIME-encoded-words (=?utf-8?B?..?= / =?utf-8?Q?..?=). */
    private function decodeMimeHeader(string $value): string
    {
        if (!str_contains($value, '=?')) {
            return $value;
        }
        return mb_decode_mimeheader($value);
    }

    /**
     * @return array{0:?string,1:?string} [textBody, htmlBody]
     */
    private function decodeContent(array $headers, string $body): array
    {
        [$type, $params] = $this->parseContentType($headers['content-type'] ?? 'text/plain');

        if (str_starts_with($type, 'multipart/') && !empty($params['boundary'])) {
            return $this->decodeMultipart($body, $params['boundary']);
        }

        $decoded = $this->decodeTransfer($body, $headers['content-transfer-encoding'] ?? '');
        $decoded = $this->toUtf8($decoded, $params['charset'] ?? 'utf-8');

        if ($type === 'text/html') {
            return [null, $decoded];
        }
        return [$decoded, null];
    }

    /**
     * @return array{0:?string,1:?string} [textBody, htmlBody]
     */
    private function decodeMultipart(string $body, string $boundary): array
    {
        $text = null;
        $html = null;

        $delimiter = '--' . $boundary;
        foreach (explode($delimiter, $body) as $segment) {
            $segment = ltrim($segment, "\n");
            // Преамбула, эпилог и закрывающая граница ("--boundary--") — пропускаем.
            if ($segment === '' || str_starts_with($segment, '--')) {
                continue;
            }

            [$partHeaders, $partBody] = $this->splitHeadersBody($segment);
            [$partText, $partHtml] = $this->decodeContent($this->parseHeaders($partHeaders), $partBody);

            $text ??= $partText;
            $html ??= $partHtml;
        }

        return [$text, $html];
    }

    /**
     * @return array{0:string,1:array<string,string>} [type, params]
     */
    private function parseContentType(string $value): array
    {
        $parts = explode(';', $value);
        $type = strtolower(trim(array_shift($parts)));

        $params = [];
        foreach ($parts as $part) {
            if (!str_contains($part, '=')) {
                continue;
            }
            [$key, $val] = explode('=', $part, 2);
            $params[strtolower(trim($key))] = trim(trim($val), '"\'');
        }

        return [$type ?: 'text/plain', $params];
    }

    private function decodeTransfer(string $body, string $encoding): string
    {
        return match (strtolower(trim($encoding))) {
            'quoted-printable' => quoted_printable_decode($body),
            'base64' => (string)base64_decode(preg_replace('/\s+/', '', $body) ?? '', true),
            default => $body, // 7bit, 8bit, binary, отсутствует
        };
    }

    private function toUtf8(string $body, string $charset): string
    {
        $charset = strtolower(trim($charset)) ?: 'utf-8';
        if (in_array($charset, ['utf-8', 'utf8', 'us-ascii', 'ascii'], true)) {
            return $body;
        }
        $converted = @mb_convert_encoding($body, 'UTF-8', $charset);
        return $converted !== false ? $converted : $body;
    }
}
