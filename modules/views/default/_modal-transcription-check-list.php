<?php

/** @noinspection PhpUnhandledExceptionInspection */

use app\helpers\Icons;
use app\modules\CheckList\models\CallTranscriptionCheckList;
use app\modules\CheckList\models\CallTranscriptionCheckListSurvey;
use app\modules\CheckList\models\CheckListBlock;
use app\modules\transcription\models\CallTranscription;
use yii\bootstrap\Html;
use yii\bootstrap\Modal;
use yii\web\View;

/**
 * @var View $this
 * @var CallTranscription $model
 * @var CallTranscriptionCheckList $checkList
 */
/** @var CallTranscriptionCheckListSurvey[] $surveys */
$surveys = $checkList->getSurveys()->indexBy('question_id')->all();
?>
<?php Modal::begin(
    [
        'header' => $this->render('_modal-transcription-check-list-header', ['model' => $model, 'checkList' => $checkList]),
        'footer' => null,
        'size' => 'modal-lg modal-light-grey2 modal-svgClose',
        'closeButton' => [
            'tag' => 'button',
            'label' => Icons::wrapper('bicolors-close__24vb', ['class' => 'svg--icon svg--icon__24vb svg--icon__16 svg-dark-grey']),
        ],
    ]
); ?>
    <div class="row">
        <div class="col-sm-6">
            <div class="overflowY-auto scrollbar__thin pr-10 d-flex flex-column gap-10" style="max-height: 81vh;">
                <?php
                $renderRecursive = function (CheckListBlock $node, $parentId = 0, $level = 0) use (&$renderRecursive, $checkList, $surveys) {
                    $children = '';
                    foreach ($node->children as $i => $child) {
                        $children .= $renderRecursive($child, $node->id, $level + 1);
                    }
                    ob_start();
                    ?>
                    <div class="unityBlock level_0<?= $level ?: '' ?>">
                        <div class="unityBlock_header">
                            <div class="unityBlock_headerItem">
                                <h4 class="h4 unityBlock_headerTitle"><?= $node->localName ?></h4>
                            </div>
                            <div class="unityBlock_headerItem">
                            </div>
                        </div>
                        <div class="unityBlock_body">
                            <?php if (!array_key_exists($node->id, $checkList->max_available_blocks_points ?: [])) : ?>
                                <div class="d-flex align-center gap-10 mb-10">
                                    <?= Yii::t('app', 'Оценка не проводилась') ?>
                                </div>
                            <?php else : ?>
                                <?php if (!$node->children): ?>
                                    <?php foreach ($node->questions as $question): ?>
                                        <?php if ($surveys[$question->id]->is_excluded ?? null) {
                                            continue;
                                        } ?>
                                        <div class="d-flex align-center gap-10 mb-10">
                                            <div class="">
                                                <?php if (isset($surveys[$question->id]->kpi_answer)): ?>
                                                    <?= $surveys[$question->id]->kpi_answer
                                                        ? Icons::wrapper(
                                                            'bicolors-check__24vb',
                                                            ['class' => 'svg--icon svg--icon__24vb svg--icon__12 svg-dark-grey']
                                                        )
                                                        : Icons::wrapper(
                                                            'bicolors-close__24vb',
                                                            ['class' => 'svg--icon svg--icon__24vb svg--icon__12 svg-danger']
                                                        )
                                                    ?>
                                                <?php else: ?>
                                                    &mdash;
                                                <?php endif; ?>
                                            </div>
                                            <div class=""><?= $question->name ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?= $children ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    return ob_get_clean();
                };
                foreach ($checkList->checkList->parentsBlocks as $block) {
                    echo $renderRecursive($block);
                }
                ?>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="d-flex flex-column" style="max-height: 81vh;">
                <div class="mb-10 flex-grow-1 overflowY-auto scrollbar__thin bg-white border-white border-top border-right border-bottom"
                     style="--width_border-right:10px; --width_border-top:10px; --width_border-bottom:10px;">
                    <div class="unityBlock flex-grow-1">
                        <div class="commentBlock" style="max-height: 100%;">
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
                    </div>
                </div>
                <div class="unityBlock">
                    <div class="unityBlock_header">
                        <div class="unityBlock_headerItem">
                            <h4 class="h4 unityBlock_headerTitle">
                                <?= Yii::t('app', 'Итог звонка') ?>
                            </h4>
                        </div>
                    </div>
                    <div class="unityBlock_body overflowY-auto scrollbar__thin" style="max-height: calc(21vh - 37px);">
                        <div><?= Html::encode($model->dataAI?->getAnswerCallTranscription()) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php Modal::end();
