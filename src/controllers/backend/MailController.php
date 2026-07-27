<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Maillog\controllers\backend;

use Besnovatyj\Kernel\controller\ControllerTrait;
use Besnovatyj\Maillog\repositories\NotFoundException;
use Besnovatyj\Maillog\services\MailLogService;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Просмотр писем файлового транспорта. Только чтение.
 *
 *  - index: список писем с пагинацией;
 *  - view:  одно письмо (заголовки + text-часть; html-часть — в sandbox-iframe на actionHtml);
 *  - html:  «сырая» html-часть письма для iframe, отдаётся с жёстким CSP.
 */
class MailController extends Controller
{
    use ControllerTrait;

    public function __construct(
        $id,
        $module,
        private readonly MailLogService $service,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        $pageSize = (int)($this->module->params['pageSize'] ?? 25);

        return $this->render('index', $this->service->getPage($pageSize > 0 ? $pageSize : 25));
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionView(string $id): string
    {
        try {
            $message = $this->service->find($id);
        } catch (NotFoundException) {
            throw new NotFoundHttpException('Письмо не найдено.');
        }

        return $this->render('view', ['message' => $message]);
    }

    /**
     * HTML-часть письма для встраивания в изолированный iframe.
     *
     * Отдаётся как сырой HTML с политикой CSP, запрещающей скрипты и любые активные ресурсы;
     * сам iframe на странице просмотра дополнительно ограничен атрибутом `sandbox`.
     *
     * @throws NotFoundHttpException
     */
    public function actionHtml(string $id): Response
    {
        try {
            $message = $this->service->find($id);
        } catch (NotFoundException) {
            throw new NotFoundHttpException('Письмо не найдено.');
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'none'; img-src * data:; style-src 'unsafe-inline'; font-src *"
        );
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        $response->data = $message->hasHtml()
            ? (string)$message->html
            : '<!doctype html><meta charset="utf-8"><p style="font-family:sans-serif;color:#6c757d">Нет HTML-части.</p>';

        return $response;
    }
}
