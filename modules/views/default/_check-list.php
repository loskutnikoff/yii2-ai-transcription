<?php

use app\components\Perm;
use app\helpers\Helper;
use app\helpers\Icons;
use app\modules\CheckList\models\CallTranscriptionCheckList;
use app\modules\CheckList\models\CheckListBlock;
use app\modules\CheckList\models\CheckListQuestion;
use yii\bootstrap\Html;
use yii\data\ArrayDataProvider;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/**
 * @var CallTranscriptionCheckList|null $model
 */

$surveys = $model ? $model->getSurveys()->indexBy('question_id')->all() : [];

?>
<?= GridView::widget(
    [
        'tableOptions' => ['class' => 'table table__striped table__border'],
        'dataProvider' => new ArrayDataProvider([
            'allModels' => array_filter([$model]),
            'modelClass' => CallTranscriptionCheckList::class,
            'key' => 'id'
        ]),
        'filterModel' => null,
        'columns' => [
            [
                'attribute' => 'check_list_id',
                'value' => 'checkList.name',
            ],
            'finished_at:date',
            [
                'label' => Yii::t('app', 'Заполнено'),
                'format' => 'DealerCheckListPercents',
                'value' => static function (CallTranscriptionCheckList $model) {
                    return $model;
                },
            ],
            [
                'label' => Yii::t('app', 'Баллов'),
                'value' => static function (CallTranscriptionCheckList $model) {
                    return (float)$model->total_earned_points
                        . ' ' . Yii::t('app', 'из')
                        . ' ' . (float)$model->checkList->max_available_points;
                },
            ],
            [
                'label' => Yii::t('app', 'Финальная оценка'),
                'format' => 'html',
                'value' => static function (CallTranscriptionCheckList $model) {
                    return \yii\helpers\Html::tag(
                        'span',
                        $model->is_final_check_complete ? Yii::t('app', 'Пройдено') : Yii::t('app', 'Не пройдено'),
                        [
                            'class' => [
                                'label',
                                $model->is_final_check_complete ? 'label-success' : 'label-danger',
                            ],
                        ]
                    );
                },
            ],
            [
                'class' => ActionColumn::class,
                'contentOptions' => ['class' => 'nowrap'],
                'template' => '{view} {details}',
                'visibleButtons' => [
                    'view' => static function (CallTranscriptionCheckList $model) {
                        return Yii::$app->user->can(Perm::CALL_TRANSCRIPTION_CHECK_LIST_SURVEY_VIEW, ['model' => $model]);
                    },
                ],
                'buttons' => [
                    'view' => static function ($url, CallTranscriptionCheckList $model) {
                        return Html::a(
                            Icons::wrapper('bicolors-eye'),
                            ['/check-list/call-transcription-survey/view', 'id' => $model->id],
                            ['title' => Yii::t('app', 'Просмотреть')]
                        );
                    },
                ],
            ],
        ],
    ]
) ?>

<?php if ($model): ?>
    <div class="border-bottom border-grey mt-10">
        <?php
        $renderRecursive = function (CheckListBlock $node, $parentId = 0, $level = 0) use (&$renderRecursive, $model, $surveys) {
            $children = '';
            foreach ($node->children as $i => $child) {
                $children .= $renderRecursive($child, $node->id, $level + 1);
            }
            ob_start();
            ?>
            <div class="checkList-questioning--item level_0<?= $level ?: '' ?>">
                <div class="iconModification collapsed" data-toggle="collapse" data-target="#target<?= $node->id ?>" aria-expanded="false">
                    <div class="d-flex pt-10 pb-10 pl-10 bg-light-grey2 cursor__pointer">
                        <div class="d-flex align-center">
                            <?= Icons::wrapper('bicolors-arrow_up') ?>
                            <span class="btn-text ml-10">
                                        <?= $node->localName ?>
                                    </span>
                        </div>
                    </div>
                </div>
                <div class="collapse mt-10" id="target<?= $node->id ?>">
                    <?php if (!$node->children): ?>
                        <?= GridView::widget([
                            'tableOptions' => ['class' => 'table table__border'],
                            'dataProvider' => new ArrayDataProvider([
                                'allModels' => $node->questions,
                                'modelClass' => CheckListQuestion::class,
                                'pagination' => false,
                            ]),
                            'layout' => '{items}',
                            'columns' => [
                                [
                                    'attribute' => 'name',
                                    'headerOptions' => ['class' => 'col-md-8 text-center'],
                                    'contentOptions' => ['class' => 'col-md-8'],
                                    'value' => 'localName',
                                ],
                                [
                                    'header' => Yii::t('app', ''),
                                    'headerOptions' => ['class' => 'text-center'],
                                    'contentOptions' => ['class' => 'text-center'],
                                    'format' => 'raw',
                                    'value' => function (CheckListQuestion $item) use ($surveys) {
                                        if (isset($surveys[$item->id]->kpi_answer)) {
                                            return $surveys[$item->id]->kpi_answer ? Icons::wrapper('bicolors-circle_check__24vb', ['class' => 'svg--icon svg--icon__24vb svg-success']) : Icons::wrapper('bicolors-circle_ignore', ['class' => 'svg--icon svg--icon__24vb svg-danger']);
                                        }

                                        return '&mdash;';
                                    },
                                ],
                                [
                                    'header' => Yii::t('app', 'Ответ'),
                                    'headerOptions' => ['class' => 'text-center'],
                                    'contentOptions' => ['class' => 'text-center'],
                                    'format' => 'raw',
                                    'value' => function (CheckListQuestion $item) use ($surveys) {
                                        if (isset($surveys[$item->id]->kpi_answer)) {
                                            return Helper::getYesNoList()[$surveys[$item->id]->kpi_answer] ?? null;
                                        }

                                        return '&mdash;';
                                    },
                                ],
                            ],
                        ]) ?>
                    <?php endif; ?>
                    <?= $children ?>
                </div>
            </div>
            <?php
            return ob_get_clean();
        };
        foreach ($model->checkList->parentsBlocks as $block) {
            echo $renderRecursive($block);
        }
        ?>
    </div>
<?php endif; ?>
