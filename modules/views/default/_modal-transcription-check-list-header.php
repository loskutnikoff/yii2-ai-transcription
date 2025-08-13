<?php

use app\components\Perm;
use app\helpers\Icons;
use app\modules\CheckList\models\CallTranscriptionCheckList;
use app\modules\transcription\models\CallTranscription;
use yii\bootstrap\Html;
use yii\web\View;

/**
 * @var View $this
 * @var CallTranscription $model
 * @var CallTranscriptionCheckList $checkList
 */
?>
<div class="row w-100p">
    <div class="col-sm-6">
        <div class="d-flex align-center gap-10 mb-10">
            <?= Html::tag(
                'span',
                $checkList->is_final_check_complete ? Yii::t('app', 'Пройдено') : Yii::t('app', 'Не пройдено'),
                [
                    'class' => [
                        'label',
                        $checkList->is_final_check_complete ? 'label-light-green' : 'label-light-red',
                    ],
                ]
            ) ?>
            <div class="h3 mb-0"><?= $checkList->checkList->name ?></div>
        </div>
        <div class="d-flex align-center gap-10">
            <?php if (Yii::$app->user->can(Perm::CALL_TRANSCRIPTION_CHECK_LIST_SURVEY_VIEW, ['model' => $checkList])): ?>
                <?= Html::a(
                    Icons::wrapper('bicolors-eye'),
                    ['/check-list/call-transcription-survey/view', 'id' => $checkList->id],
                    ['class' => 'btn-icon', 'target' => '_blank']
                ) ?>
            <?php endif; ?>
            <div class="fz13 nowrap">
                <?= (float)$checkList->total_earned_points . ' / ' . (float)$checkList->max_available_points . ' ' . Yii::t('app', 'баллов') ?>
            </div>
            <div class="fz13 text-grey4 nowrap"><?= Yii::$app->formatter->asDatetime($checkList->finished_at) ?></div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="d-flex align-center gap-10 h-100p modal-header-offset-left">
            <div class="h3 mb-0"><?= Yii::t('app', 'Расшифровка звонка') ?></div>
            <div>
                <div class="js-wrapper-record-player" data-audio-url="<?= $model->voximplantHistory->voximplant_call_record_url ?>"
                     id="player-container-voximplant-call-record-url"></div>
            </div>
        </div>
    </div>
</div>
