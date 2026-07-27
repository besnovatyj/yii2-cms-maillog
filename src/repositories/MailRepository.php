<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Maillog\repositories;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use Yii;

/**
 * Репозиторий каталога писем файлового транспорта.
 *
 * Каталог может быть плоским (`@runtime/mail/20260727-...eml`) либо шардированным по подпапкам
 * (`@runtime/mail/Y-m-d/H/His-uniqid.eml`) — обходится рекурсивно. Список строится только из
 * ОТНОСИТЕЛЬНЫХ путей-строк (без чтения и разбора файлов), поэтому пагинация по десяткам тысяч
 * писем остаётся дешёвой: и путь-имя, и дата-подпапка сортируются лексикографически = хронологически,
 * так что содержимое читается лишь для писем текущей страницы.
 *
 * Адресация письма наружу — обратимый url-safe ключ (`slug`) от относительного пути; при обратном
 * разборе путь проверяется на выход за пределы каталога (защита от path traversal).
 */
final class MailRepository
{
    public function __construct(private readonly string $path)
    {
    }

    /** Абсолютный путь к каталогу писем. */
    public function getPath(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_dir($this->path);
    }

    /**
     * Относительные пути всех `.eml`-файлов, новые сверху.
     *
     * @return string[]
     */
    public function listRelativePaths(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $prefixLen = strlen($this->path) + 1;
        $out = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->path,
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $pathname => $_info) {
                // Фильтр по расширению — только строковая операция, без stat().
                if (strtolower(substr($pathname, -4)) === '.eml') {
                    $out[] = substr($pathname, $prefixLen);
                }
            }
        } catch (Throwable $e) {
            // Каталог мог оказаться частично нечитаемым — не роняем список, просто предупреждаем.
            Yii::warning('Ошибка обхода каталога писем: ' . $e->getMessage(), 'maillog');
        }

        rsort($out, SORT_STRING);

        return $out;
    }

    /**
     * Прочитать «сырой» текст письма по относительному пути.
     *
     * @throws NotFoundException
     */
    public function readRaw(string $relative): string
    {
        $full = $this->path . '/' . $relative;
        $data = @file_get_contents($full);
        if ($data === false) {
            throw new NotFoundException('Не удалось прочитать письмо.');
        }
        return $data;
    }

    /** Обратимый url-safe ключ письма из относительного пути. */
    public function slugFor(string $relative): string
    {
        return rtrim(strtr(base64_encode($relative), '+/', '-_'), '=');
    }

    /**
     * Относительный путь письма по ключу с валидацией (без выхода за пределы каталога).
     *
     * @throws NotFoundException
     */
    public function resolveSlug(string $slug): string
    {
        $relative = $this->decodeSlug($slug);

        if ($relative === '' || str_contains($relative, "\0") || str_contains($relative, '..')) {
            throw new NotFoundException('Письмо не найдено.');
        }

        $realBase = realpath($this->path);
        $realFull = realpath($this->path . '/' . $relative);

        if ($realBase === false || $realFull === false
            || !str_starts_with($realFull, $realBase . DIRECTORY_SEPARATOR)
            || !is_file($realFull)
            || strtolower((string)pathinfo($realFull, PATHINFO_EXTENSION)) !== 'eml'
        ) {
            throw new NotFoundException('Письмо не найдено.');
        }

        return $relative;
    }

    private function decodeSlug(string $slug): string
    {
        $b64 = strtr($slug, '-_', '+/');
        if (($pad = strlen($b64) % 4) !== 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        return (string)base64_decode($b64, true);
    }
}
