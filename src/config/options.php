<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

// Опции модуля настроек yii2-cms-config. Значения применяются в Yii::$app->getModule('Maillog')->params.*
return [
    'maillog_path' => [
        'path' => 'modules.Maillog.params.path',
        'label' => '[Maillog] Каталог писем',
        'description' => 'Абсолютный путь или Yii-алиас к каталогу писем файлового транспорта. '
            . 'Пусто — берётся из настроек мейлера (fileTransportPath, по умолчанию @runtime/mail).',
        'category' => 'Maillog',
        'rules' => [
            ['string'],
        ],
        'inputOptions' => [
            'type' => 'input',
        ],
    ],
    'maillog_page_size' => [
        'path' => 'modules.Maillog.params.pageSize',
        'label' => '[Maillog] Писем на странице',
        'description' => 'Размер страницы списка писем (1–200).',
        'category' => 'Maillog',
        'rules' => [
            ['integer', 'min' => 1, 'max' => 200],
        ],
        'inputOptions' => [
            'type' => 'input',
        ],
    ],
];
