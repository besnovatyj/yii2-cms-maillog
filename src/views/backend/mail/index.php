<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Backend\Widgets\pagination\LinkPager;
use Besnovatyj\Maillog\entities\MailMessage;
use yii\data\Pagination;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 * @var MailMessage[] $messages
 * @var Pagination $pagination
 * @var int $total
 * @var string $path
 */

$this->title = 'Mail Log';
$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><?= Html::encode($this->title) ?></span>
        <span class="badge bg-secondary" title="<?= Html::encode($path) ?>">Всего: <?= $total ?></span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Каталог: <code><?= Html::encode($path) ?></code>
        </p>

        <?php if ($messages === []): ?>
            <div class="alert alert-info mb-0">Писем нет.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th style="width:180px">Дата</th>
                        <th>Тема</th>
                        <th style="width:200px">От / Кому</th>
                        <th style="width:90px" class="text-end">Размер</th>
                        <th style="width:90px"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($messages as $message): ?>
                        <?php $viewUrl = Url::to(['view', 'id' => $message->slug]); ?>
                        <tr>
                            <td>
                                <?= $message->date !== null
                                    ? Html::encode($formatter->asDatetime($message->date, 'php:Y-m-d H:i:s'))
                                    : Html::encode($message->dateRaw) ?>
                            </td>
                            <td>
                                <?= Html::a(
                                    Html::encode($message->subject !== '' ? $message->subject : '(без темы)'),
                                    $viewUrl
                                ) ?>
                                <div class="text-muted" style="font-size:75%">
                                    <?= Html::encode($message->relativePath) ?>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size:85%">
                                <?= Html::encode($message->from) ?><br>
                                <?= Html::encode($message->to) ?>
                            </td>
                            <td class="text-end text-muted">
                                <?= Html::encode($formatter->asShortSize($message->sizeBytes, 1)) ?>
                            </td>
                            <td>
                                <?= Html::a('Открыть', $viewUrl, ['class' => 'btn btn-sm btn-primary']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer clearfix">
        <nav aria-label="" class="nav-pagination">
            <?= LinkPager::widget(['pagination' => $pagination]) ?>
        </nav>
    </div>
</div>
