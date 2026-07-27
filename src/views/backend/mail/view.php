<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Maillog\entities\MailMessage;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 * @var MailMessage $message
 */

$this->title = $message->subject !== '' ? $message->subject : '(без темы)';
$this->params['breadcrumbs'][] = ['label' => 'Mail Log', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
?>
<p>
    <?= Html::a('← К списку', ['index'], ['class' => 'btn btn-secondary']) ?>
</p>

<div class="card mb-3">
    <div class="card-header"><?= Html::encode($this->title) ?></div>
    <div class="card-body">
        <table class="table table-sm mb-0">
            <tbody>
            <tr>
                <th style="width:140px">Дата</th>
                <td><?= $message->date !== null
                        ? Html::encode($formatter->asDatetime($message->date, 'php:Y-m-d H:i:s'))
                        : Html::encode($message->dateRaw) ?></td>
            </tr>
            <tr>
                <th>От</th>
                <td><?= Html::encode($message->from) ?></td>
            </tr>
            <tr>
                <th>Кому</th>
                <td><?= Html::encode($message->to) ?></td>
            </tr>
            <?php if ($message->messageId !== null): ?>
                <tr>
                    <th>Message-ID</th>
                    <td class="text-muted"><code><?= Html::encode($message->messageId) ?></code></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Файл</th>
                <td class="text-muted">
                    <code><?= Html::encode($message->relativePath) ?></code>
                    (<?= Html::encode($formatter->asShortSize($message->sizeBytes, 1)) ?>)
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<?php if ($message->hasHtml()): ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>HTML</span>
            <span class="badge bg-light text-dark" title="Скрипты и внешние ресурсы отключены">
                изолированный просмотр
            </span>
        </div>
        <div class="card-body p-0">
            <iframe
                src="<?= Html::encode(Url::to(['html', 'id' => $message->slug])) ?>"
                sandbox
                referrerpolicy="no-referrer"
                loading="lazy"
                title="HTML-часть письма"
                style="width:100%;min-height:600px;border:0;background:#fff"></iframe>
        </div>
    </div>
<?php endif; ?>

<?php if ($message->hasText()): ?>
    <div class="card mb-3">
        <div class="card-header">Текст</div>
        <div class="card-body">
            <pre style="white-space:pre-wrap;word-break:break-word;margin:0"><?= Html::encode($message->text) ?></pre>
        </div>
    </div>
<?php endif; ?>

<?php if (!$message->hasHtml() && !$message->hasText()): ?>
    <div class="alert alert-warning">Тело письма пустое или не распознано.</div>
<?php endif; ?>
