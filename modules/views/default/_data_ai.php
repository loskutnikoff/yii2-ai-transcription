<?php

/** @noinspection PhpUnhandledExceptionInspection */

use app\helpers\Icons;
use app\modules\transcription\models\CallTranscription;
use app\modules\transcription\models\DataAI;
use yii\bootstrap\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var $this View
 * @var $model CallTranscription
 */
?>
<div class="js-wrap-push-ai">
    <?= $this->render('_summary_call', ['model' => $model]) ?>
</div>
<div class="commentBlock overflowY-auto scrollbar__thin mb-10 pr-10" style="max-height: 50vh;">
    <?php if ($model->data_json) : ?>
        <?php $date = ($date = $model->voximplantHistory->voximplant_call_date ?? null) ? Yii::$app->formatter->asDate($date) : null ?>
        <?php foreach ($model->data_json as $item): ?>
            <?php $role = $item['role'] ?? null ?>
            <div class="mb-10 <?= $role == 'Клиент' ? ' current-user-comment' : ' user-event' ?>">
                <div class="commentBlock_info">
                    <span class="commentBlock_infoUser">
                        <?= $role ?>,
                    </span>
                    <span class="commentBlock_infoDate">
                         <?= $date ?> <?= $item['time'] ?? null ?>
                    </span>
                </div>
                <div class="commentBlock_comments bg-info">
                    <div><?= $item['text'] ?? null ?></div>
                </div>
            </div>
                <div>
                </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="mb-10"><?= nl2br($model->text) ?></div>
    <?php endif; ?>
</div>
<div class="h4 mb-10"><?= Yii::t('app', 'Промпт') ?></div>
<div class="padding-0 bg-white">
    <div class="commentBlock_inputGroup commentBlock_inputGroup__bigHeight">
        <?= Html::textarea('prompt', $model->dataAI->prompt ?? DataAI::TMP_PROMPT, ['rows' => 5, 'class' => 'commentBlock_formControl scrollbar__thin commentBlock_formControl__resizeVert overflowY-auto js-prompt', 'placeholder' => Yii::t('app', "Введите промпт")]) ?>
        <div class="commentBlock_actionBlock">
            <?= Html::button(Icons::wrapper('bicolors-send'), [
                'title' => Yii::t('app', 'Обработать ИИ'),
                'data-toggle' => 'tooltip',
                'class' => 'btn-icon commentBlock_btnSend js-do-ai',
                'data-entity-id' => $model->id,
                'data-entity-type' => DataAI::ENTITY_TYPE_CALL_TRANSCRIPTION,
                'data-url' => Url::to(['/transcription/data-ai/transcription']),
                'data-complete-url' => Url::to(['/transcription/default/summary']),
            ]) ?>
        </div>
    </div>
</div>

<div class="alert alert-danger js-data-ai-error" style="display: none;"></div>
<div class="js-ai-spinner spinner-parent-modal-wait-push" style="display: none;">
    <div class="spinner-modal-wait-push"></div>
</div>
