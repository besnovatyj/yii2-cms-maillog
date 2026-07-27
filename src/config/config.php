<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [
    'id' => 'Maillog',
    'params' => [
        'iconClass' => 'bi bi-envelope',

        'directories' => false, // Если для работы модуля необходимы директории для статики

        // Каталог с письмами файлового транспорта. Абсолютный путь или Yii-алиас.
        // null/'' — берётся из настроек мейлера (Yii::$app->mailer->fileTransportPath, дефолт @runtime/mail).
        // Переопределяется через модуль настроек yii2-cms-config, см. config/options.php.
        'path' => null,

        // Размер страницы списка писем.
        'pageSize' => 25,
    ],
];
