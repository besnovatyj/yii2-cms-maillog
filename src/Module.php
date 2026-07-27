<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Maillog;

use Besnovatyj\Contracts\module\DeclaresModule;
use Besnovatyj\Contracts\module\ProvidesAdminMenu;
use Besnovatyj\Contracts\module\ProvidesOptions;
use Besnovatyj\Kernel\module\CmsModule;

/**
 * Модуль просмотра писем, сложенных файловым транспортом Yii2 (`useFileTransport => true`).
 *
 * Только чтение: сканирует каталог `.eml`-файлов (в т.ч. вложенные шард-подпапки вида `Y-m-d/H`),
 * разбирает заголовки и декодирует тело (quoted-printable / base64, любой charset → UTF-8),
 * показывает список с пагинацией и отдельную страницу письма. HTML-часть рендерится в
 * изолированном `sandbox`-iframe. Никаких зависимостей для разбора писем — только ядро PHP.
 *
 * Каталог берётся из настройки `modules.Maillog.params.path` (модуль настроек yii2-cms-config),
 * а если она пуста — из конфигурации мейлера (`Yii::$app->mailer->fileTransportPath`,
 * по умолчанию `@runtime/mail`). См. {@see \Besnovatyj\Maillog\repositories\MailRepository}.
 *
 * Модуль намеренно НЕ умеет удалять письма: чисткой каталога занимается yii2-cms-clear-manager.
 */
class Module extends CmsModule implements
    DeclaresModule, ProvidesAdminMenu, ProvidesOptions
{
    public const bool EDITABLE = true;
    public const string VERSION = '1.0.0';
    public const string MODULE_ID = 'Maillog';

    public static function moduleId(): string { return self::MODULE_ID; }
    public static function moduleVersion(): string { return self::VERSION; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function adminMenu(): array { return require __DIR__ . '/config/adminMenu.php'; }
    public static function moduleConfig(): array { return require __DIR__ . '/config/config.php'; }
    public static function options(): array { return require __DIR__ . '/config/options.php'; }
}
