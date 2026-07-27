<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Maillog\services;

use Besnovatyj\Maillog\entities\MailMessage;
use Besnovatyj\Maillog\repositories\MailRepository;
use DateTimeImmutable;
use Throwable;
use yii\data\Pagination;

/**
 * Сервис просмотра писем: пагинация списка и полный разбор одного письма.
 *
 * Список строится по относительным путям из репозитория (дёшево при десятках тысяч файлов),
 * а читаются и разбираются только письма текущей страницы. Тело в списке не декодируется.
 */
final class MailLogService
{
    public function __construct(
        private readonly MailRepository $repo,
        private readonly EmlParser $parser,
    ) {
    }

    /**
     * Страница списка писем.
     *
     * @return array{messages: MailMessage[], pagination: Pagination, total: int, path: string}
     */
    public function getPage(int $pageSize): array
    {
        $paths = $this->repo->listRelativePaths();

        $pagination = new Pagination([
            'totalCount' => count($paths),
            'defaultPageSize' => $pageSize,
            'pageSizeLimit' => [1, 200],
        ]);

        $messages = [];
        foreach (array_slice($paths, $pagination->offset, $pagination->limit) as $relative) {
            $messages[] = $this->buildMessage($relative, withBody: false);
        }

        return [
            'messages' => $messages,
            'pagination' => $pagination,
            'total' => count($paths),
            'path' => $this->repo->getPath(),
        ];
    }

    /**
     * Полный разбор одного письма по ключу.
     *
     * @throws \Besnovatyj\Maillog\repositories\NotFoundException
     */
    public function find(string $slug): MailMessage
    {
        $relative = $this->repo->resolveSlug($slug);
        return $this->buildMessage($relative, withBody: true);
    }

    private function buildMessage(string $relative, bool $withBody): MailMessage
    {
        $raw = $this->repo->readRaw($relative);
        $parsed = $this->parser->parse($raw, $withBody);

        $date = null;
        if ($parsed['date'] !== '') {
            try {
                $date = new DateTimeImmutable($parsed['date']);
            } catch (Throwable) {
                // нераспознанная дата в заголовке — оставляем только сырую строку
            }
        }

        return new MailMessage(
            slug: $this->repo->slugFor($relative),
            relativePath: $relative,
            filename: basename($relative),
            sizeBytes: strlen($raw),
            subject: $parsed['subject'],
            from: $parsed['from'],
            to: $parsed['to'],
            dateRaw: $parsed['date'],
            date: $date,
            messageId: $parsed['messageId'] !== '' ? $parsed['messageId'] : null,
            text: $parsed['text'],
            html: $parsed['html'],
        );
    }
}
