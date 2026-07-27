<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Maillog\entities;

use DateTimeImmutable;

/**
 * Иммутабельное представление разобранного письма.
 *
 * Тело (`text`/`html`) заполняется только при полном разборе (страница просмотра); в списке
 * оно равно null — там достаточно заголовков. См. {@see \Besnovatyj\Maillog\services\MailLogService}.
 */
final class MailMessage
{
    public function __construct(
        public readonly string $slug,
        public readonly string $relativePath,
        public readonly string $filename,
        public readonly int $sizeBytes,
        public readonly string $subject,
        public readonly string $from,
        public readonly string $to,
        public readonly string $dateRaw,
        public readonly ?DateTimeImmutable $date,
        public readonly ?string $messageId,
        public readonly ?string $text,
        public readonly ?string $html,
    ) {
    }

    public function hasText(): bool
    {
        return $this->text !== null && trim($this->text) !== '';
    }

    public function hasHtml(): bool
    {
        return $this->html !== null && trim($this->html) !== '';
    }
}
