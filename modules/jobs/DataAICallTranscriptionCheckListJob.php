<?php

namespace app\modules\transcription\jobs;

use app\components\exceptions\ValidationException;
use app\components\queue\AbstractJob;
use app\helpers\ArrayHelper;
use app\modules\CheckList\components\CallTranscriptionCheckListSurveyService;
use app\modules\CheckList\components\CheckListCondition\Service;
use app\modules\CheckList\models\CallTranscriptionCheckList;
use app\modules\CheckList\models\CallTranscriptionCheckListSurvey;
use app\modules\CheckList\models\CheckList;
use app\modules\CheckList\models\CheckListBlock;
use app\modules\CheckList\models\CheckListConditionRule;
use app\modules\CheckList\models\CheckListQuestion;
use app\modules\CheckList\models\VehicleCheckList;
use app\modules\transcription\models\CallTranscription;
use Exception;
use Yii;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\queue\Queue;
use yii\queue\RetryableJobInterface;

class DataAICallTranscriptionCheckListJob extends AbstractJob implements RetryableJobInterface
{
    public const TTR = 5 * 3600;
    public const RETRY_ATTEMPTS = 5;

    public $callTranscriptionId;
    public $checkListId;

    /** @var CallTranscriptionCheckList */
    protected $model;
    protected $questions;
    protected $excludedQuestions = [];

    /**
     * @param Queue $queue
     * @return mixed|void
     */
    public function execute($queue)
    {
        parent::execute($queue);
        try {
            $transcription = CallTranscription::findOne($this->callTranscriptionId);
            if (!($transcription->text ?? null)) {
                return;
            }
            $checkList = CheckList::findOne(['id' => $this->checkListId, 'status' => CheckList::STATUS_ACTIVE]);
            if (!$checkList) {
                return;
            }

            $this->model = CallTranscriptionCheckList::findOne([
                'check_list_id' => $this->checkListId,
                'call_transcription_id' => $this->callTranscriptionId,
            ]);
            if (!$this->model) {
                $this->model = new CallTranscriptionCheckList();
                $this->model->check_list_id = $this->checkListId;
                $this->model->call_transcription_id = $this->callTranscriptionId;
            }
            $this->model->finished_at = date('Y-m-d H:i:s');
            $this->model->status = VehicleCheckList::STATUS_FINISHED;

            $this->findQuestions($checkList, $transcription);
            if (!$this->questions) {
                return;
            }

            $prompt = 'Проверь запись разговора на соответствие чек-листу в формате json следующей структуры [{"id": идентификатор вопроса, "text": текст вопроса, "description": дополнительное описание}].'
                . PHP_EOL . Json::encode($this->questions)
                . 'Ответ пришли в виде json следующей структуры [{"id": идентификатор вопроса, "answer": 1 - да, 0 - нет}].';

            $dto = Yii::$app->gpt->request($prompt, $transcription->text, false);

            $answer = json_decode(str_replace(["\n", '```'], "", $dto->answer), true);

            Yii::$app->db->transaction(function () use ($answer) {
                if (!$this->model->save()) {
                    throw new ValidationException(VarDumper::dumpAsString($this->model->errors));
                }

                /** @var CallTranscriptionCheckListSurvey[] $surveys */
                $surveys = ArrayHelper::index($this->model->surveys, fn(CallTranscriptionCheckListSurvey $item) => $item->question_id);

                foreach ($answer as $item) {
                    $survey = $surveys[$item['id']] ?? new CallTranscriptionCheckListSurvey();
                    $survey->check_list_id = $this->model->id;
                    $survey->question_id = $item['id'];
                    $survey->kpi_answer = $item['answer'];
                    if (!$survey->save()) {
                        throw new ValidationException(VarDumper::dumpAsString($survey->errors));
                    }
                    unset($surveys[$item['id']]);
                }

                foreach ($this->excludedQuestions as $questionId) {
                    $survey = $surveys[$item['id']] ?? new CallTranscriptionCheckListSurvey();
                    $survey->check_list_id = $this->model->id;
                    $survey->question_id = $questionId;
                    $survey->kpi_answer = null;
                    $survey->is_excluded = true;
                    if (!$survey->save()) {
                        throw new ValidationException(VarDumper::dumpAsString($survey->errors));
                    }
                    unset($surveys[$questionId]);
                }

                foreach ($surveys as $survey) {
                    if (!$survey->delete()) {
                        throw new ValidationException(VarDumper::dumpAsString($survey->errors));
                    }
                }

                CallTranscriptionCheckListSurveyService::updateModelTotalEarnedPoints();
                $this->model->refresh();
            });
            sleep(2);
            $this->sendCompletionPushNotification();
        } catch (Exception $e) {
            Yii::error('Ошибка выполнения задания: ' . $e->getMessage(), __METHOD__);
            $this->sendErrorPushNotification($e->getMessage());
        }
    }

    private function sendCompletionPushNotification(): void
    {
        Yii::$app->push->sendMessage(
            $this->userId,
            [
                'type' => 'data-ai-check-list-complete',
                'callTranscriptionId' => $this->callTranscriptionId,
                'checkListId' => $this->checkListId,
            ]
        );
    }

    private function sendErrorPushNotification(string $error): void
    {
        Yii::$app->push->sendMessage(
            $this->userId,
            [
                'type' => 'data-ai-check-list-error',
                'callTranscriptionId' => $this->callTranscriptionId,
                'checkListId' => $this->checkListId,
                'error' => $error,
            ]
        );
    }

    private function findQuestions(CheckList $checkList, CallTranscription $transcription)
    {
        $questions = [];
        $analyze = $transcription->analyze;
        $points = ['blocks' => [], 'total' => 0];

        $incBlockPoint = function (CheckListBlock $block, $point) use (&$points, &$incBlockPoint) {
            ArrayHelper::inc($points, ['blocks', $block->id], max($point, 0));

            if ($block->parent) {
                $incBlockPoint($block->parent, $point);
            }
        };

        if ($checkList->conditionRules && $analyze) {
            $condition = Service::getCondition($checkList->template->type);
            if (!$condition) {
                return;
            }
            $condition = new $condition($transcription);
            /** @var CheckListConditionRule[][] $blocksConditionRules */
            $blocksConditionRules = [];
            /** @var CheckListConditionRule[][] $questionsConditionRules */
            $questionsConditionRules = [];
            foreach ($checkList->conditionRules as $conditionRule) {
                if ($conditionRule->block_id) {
                    $blocksConditionRules[$conditionRule->block_id][$conditionRule->rule] = $conditionRule;
                }
                if ($conditionRule->question_id) {
                    $questionsConditionRules[$conditionRule->question_id][$conditionRule->rule] = $conditionRule;
                }
            }

            foreach ($checkList->blocks as $block) {
                if ($condition->checkRules($blocksConditionRules[$block->id] ?? [])) {
                    foreach ($block->questions as $question) {
                        if ($condition->checkRules($questionsConditionRules[$question->id] ?? [])) {
                            $point = (float)$question->point;
                            $incBlockPoint($block, $point);
                            ArrayHelper::inc($points, ['total'], max($point, 0));
                            $questions[] = $question;
                        } else {
                            $this->excludedQuestions[] = $question->id;
                        }
                    }
                } else {
                    $this->excludedQuestions = array_merge(
                        $this->excludedQuestions,
                        ArrayHelper::getColumn($block->questions, fn(CheckListQuestion $item) => $item->id)
                    );
                }
            }
        } else {
            foreach ($checkList->blocks as $block) {
                foreach ($block->questions as $question) {
                    $point = (float)$question->point;
                    $incBlockPoint($block, $point);
                    ArrayHelper::inc($points, ['total'], max($point, 0));
                    $questions[] = $question;
                }
            }
        }

        $this->model->max_available_points = $points['total'];
        $this->model->max_available_blocks_points = $points['blocks'];

        $this->questions = array_map(fn(CheckListQuestion $item) => [
            'id' => $item->id,
            'text' => $item->name,
            'description' => $item->description,
        ], $questions);
    }

    public function getTtr()
    {
        return self::TTR;
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < self::RETRY_ATTEMPTS;
    }
}