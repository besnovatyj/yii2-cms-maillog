<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Maillog\repositories\MailRepository;
use yii\di\Container;

/**
 * DI-конфигурация модуля (способ A: только для самого модуля, ленивая проводка при init).
 *
 * Репозиторий получает готовый абсолютный путь к каталогу писем: приоритет у настройки
 * `modules.Maillog.params.path` (модуль yii2-cms-config), иначе — путь файлового транспорта
 * мейлера (`fileTransportPath`, по умолчанию `@runtime/mail`). Алиас резолвится один раз здесь,
 * репозиторий работает с абсолютным путём и о конфигурации ничего не знает.
 */
return static function (Container $container): void {

    $container->setSingleton(MailRepository::class, static function (): MailRepository {
        $module = Yii::$app->getModule('Maillog');

        $path = trim((string)($module?->params['path'] ?? ''));

        if ($path === '') {
            $path = '@runtime/mail';
            try {
                $mailer = Yii::$app->get('mailer', false);
                if ($mailer !== null && !empty($mailer->fileTransportPath)) {
                    $path = (string)$mailer->fileTransportPath;
                }
            } catch (\Throwable) {
                // мейлер не сконфигурирован — остаёмся на дефолтном @runtime/mail
            }
        }

        return new MailRepository((string)Yii::getAlias($path));
    });
};
