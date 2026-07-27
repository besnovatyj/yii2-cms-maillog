<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [
    [
        'label' => 'Mail Log',
        'iconClass' => 'bi bi-envelope-paper me-1',
        'url' => ['/Maillog/backend/mail/index'],
        'active' => static function () {
            return str_contains(\Yii::$app->request->url, 'Maillog/backend/mail');
        },
        '_meta' => [
            'placements' => [
                [
                    'location' => 'right-sidebar',
                    'group' => 'Logs',
                    'groupIcon' => 'bi bi-clock-history',
                    'priority' => 110,
                    'groupPriority' => 100,
                ],
            ],
        ],
    ],
];
