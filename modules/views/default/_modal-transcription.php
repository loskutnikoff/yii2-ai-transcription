<?php

/** @noinspection PhpUnhandledExceptionInspection */

use app\modules\CheckList\models\CheckList;
use app\modules\CheckList\models\CheckListTemplate;
use app\modules\transcription\models\CallTranscription;
use yii\bootstrap\Html;
use yii\bootstrap\Modal;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var $this View
 * @var $model CallTranscription
 */

$checkLists = CheckList::getList(CheckListTemplate::TYPE_CALL_TRANSCRIPTION);
?>
<?php Modal::begin(
    [
        'header' => '<h4 class="modal-title">' . Yii::t('app', 'Транскрибация звонка') . '</h4>',
        'footer' =>
            Html::button(Yii::t('app', 'Закрыть'), ['class' => 'btn btn-sm btn-default', 'data-dismiss' => 'modal']),
        'size' => 'modal-md',
    ]
); ?>
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="nav-item active">
            <a id="text-link" href="#text" class="nav-link" aria-controls="text" role="tab" data-toggle="tab">
                <?= Yii::t('app', 'Транскрибация') ?>
            </a>
        </li>
        <?php if ($checkLists || $model->checkList) : ?>
            <li role="presentation" class="nav-item">
                <a id="check-list-link" class="nav-link" href="#check-list" aria-controls="check-list" role="tab"
                   data-toggle="tab">
                    <?= Yii::t('app', 'Чек-лист') ?>
                </a>
            </li>
        <?php endif; ?>
        <?php if ($model->analyze) : ?>
            <li role="presentation" class="nav-item">
                <a id="analyze-link" class="nav-link" href="#analyze" aria-controls="analyze" role="tab"
                   data-toggle="tab">
                    <?= Yii::t('app', 'Квалификация') ?>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <div class="tab-content">
        <div id="text" role="tabpanel" class="tab-pane bg-clear pt-0 pl-0 pr-0 in active">
            <?php if (!$model->text) : ?>
                <div class="alert alert-info js-disclaimer">
                    <?= Yii::t(
                        'app',
                        'У данной записи нет транскрибации. Вы можете транскрибировать запись нажав кнопку ниже.'
                    ) ?>
                </div>
                <div class="alert alert-danger js-transcription-error" style="display: none;"></div>
                <?= Html::button(Yii::t('app', 'Транскрибировать'), [
                'class' => 'btn btn-sm btn-default js-do-transcription',
                'data-entity-id' => $model->entity_id,
                'data-entity-type' => $model->entity_type,
                'data-url' => Url::to(['/transcription/default/create']),
                'data-complete-url' => Url::to(['/transcription/default/data-ai']),
            ]) ?>

                <div class="js-transcription-spinner spinner-parent-modal-wait-push" style="display: none;">
                    <div class="spinner-modal-wait-push"></div>
                </div>

                <div class="js-wrap-push" style="display:none"></div>
            <?php else: ?>
                <?= $this->render('_data_ai', ['model' => $model]) ?>
            <?php endif; ?>
        </div>
        <?php if ($checkLists || $model->checkList) : ?>
            <div id="check-list" role="tabpanel" class="tab-pane bg-clear pt-0 pl-0 pr-0 in">
                <?php if ($checkLists) : ?>
                    <div class="alert alert-danger js-data-ai-check-list-error" style="display: none;"></div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <?= Html::dropDownList('', null, $checkLists, [
                                    'id' => 'js-check-list',
                                    'class' => 'selectpicker form-control',
                                    'data-style' => 'btn-default',
                                    'prompt' => '',
                                ]) ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <?= Html::button(Yii::t('app', 'Обработать ИИ'), [
                                    'class' => 'btn btn-sm btn-default js-do-data-ai-check-list',
                                    'data-call-transcription-id' => $model->id,
                                    'data-url' => Url::to(['/transcription/data-ai/check-list']),
                                    'data-complete-url' => Url::to(['/transcription/default/check-list']),
                                ]) ?>
                            </div>
                        </div>
                    </div>
                    <div class="js-data-ai-check-list-spinner spinner-parent-modal-wait-push" style="display: none;">
                        <div class="spinner-modal-wait-push"></div>
                    </div>
                <?php endif; ?>
                <div id="js-call-transcription-check-list">
                    <?= $this->render('_check-list', ['model' => $model->checkList]) ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($model->analyze) : ?>
            <div id="analyze" role="tabpanel" class="tab-pane bg-clear pt-0 pl-0 pr-0 in">
                <p><?= $model->analyze->getAttributeLabel('is_successful') ?>: <?= Yii::$app->formatter->asYesNo($model->analyze->is_successful) ?></p>
                <p><?= $model->analyze->data->getAttributeLabel('is_dissatisfied') ?>: <?= Yii::$app->formatter->asYesNo($model->analyze->data->is_dissatisfied) ?></p>
                <p><?= $model->analyze->data->getAttributeLabel('dissatisfied_reason') ?>: <?= Html::encode($model->analyze->data->dissatisfied_reason) ?></p>
                <p><?= $model->analyze->data->getAttributeLabel('is_dialogue_with_operator') ?>: <?= Yii::$app->formatter->asYesNo($model->analyze->data->is_dialogue_with_operator) ?></p>
                <p><?= $model->analyze->data->getAttributeLabel('is_dialogue_with_employee') ?>: <?= Yii::$app->formatter->asYesNo($model->analyze->data->is_dialogue_with_employee) ?></p>
                <p><?= $model->analyze->data->getAttributeLabel('is_refused_without_transfer') ?>: <?= Yii::$app->formatter->asYesNo($model->analyze->data->is_refused_without_transfer) ?></p>
                <p><?= $model->analyze->data->getAttributeLabel('is_not_leave_request') ?>: <?= Yii::$app->formatter->asYesNo($model->analyze->data->is_not_leave_request) ?></p>
                <p><?= $model->analyze->data->getAttributeLabel('is_postpone_call') ?>: <?= Yii::$app->formatter->asYesNo($model->analyze->data->is_postpone_call) ?></p>
            </div>
        <?php endif; ?>
    </div>
<?php Modal::end();
