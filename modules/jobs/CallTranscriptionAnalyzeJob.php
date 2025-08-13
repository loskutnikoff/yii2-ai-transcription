<?php

namespace app\modules\transcription\jobs;

use app\components\exceptions\ValidationException;
use app\components\queue\AbstractJob;
use app\helpers\QueueHelper;
use app\modules\transcription\models\CallTranscriptionAnalyze;
use app\modules\transcription\models\CallTranscriptionAnalyzeData;
use Exception;
use Yii;
use yii\helpers\VarDumper;
use yii\queue\Queue;
use yii\queue\RetryableJobInterface;

class CallTranscriptionAnalyzeJob extends AbstractJob implements RetryableJobInterface
{
    public const TTR = 5 * 3600;
    public const RETRY_ATTEMPTS = 5;

    public $id;
    public $checkListsIds;

    /**
     * @param Queue $queue
     * @return mixed|void
     */
    public function execute($queue)
    {
        parent::execute($queue);
        try {
            $model = CallTranscriptionAnalyze::findOne(['call_transcription_id' => $this->id]);
            if (!$model) {
                $model = new CallTranscriptionAnalyze();
                $model->call_transcription_id = $this->id;
            }

            if (!($model->callTranscription->text ?? null)) {
                return;
            }

            $prompt = 'Проанализируй транскрибацию звонка и определи, основные критерии звонка указанные ниже.
            Цель звонка "' . ($model->callTranscription->voximplantHistory->request->requestType->name ?? 'не указана') . '".
            Ответ нужно прислать в json, для каждого параметра указан тип string, boolean, integer.
            json с следующей структурой:
            {
                is_dissatisfied: (boolean) Клиент выразил недовольство,
                dissatisfied_reason: (string) Если клиент недоволен, укажи суть недовольства,
                is_dialogue_with_operator: (boolean) Состоялся диалог между оператором и клиентом,
                is_dialogue_with_employee: (boolean) Состоялся диалог между менеджером\сотрудником сервисного отдела и клиентом,
                is_refused_without_transfer: (boolean) Клиент отказался от общения и не удалось договориться о переносе звонка,
                is_not_leave_request: (boolean) Клиент сказал, что не оставлял заявку,
                is_postpone_call: (boolean) Клиент или менеджер не смоги говорить и договорились о переносе звонка,
            }';

            $dto = Yii::$app->gpt->request($prompt, $model->callTranscription->text);
            $data = new CallTranscriptionAnalyzeData(json_decode($dto->answer, true));
            $model->data = $data;
            $model->is_successful = $data->is_dialogue_with_operator && $data->is_dialogue_with_employee
                && !$data->is_refused_without_transfer && !$data->is_not_leave_request && !$data->is_postpone_call;
            if (!$model->save(false)) {
                Yii::error('Ошибка сохранения' . VarDumper::dumpAsString([$model->attributes, $model->errors]));
                throw new ValidationException('Ошибка при сохранении обработки ИИ', $model->getErrors());
            }

            if ($this->checkListsIds) {
                foreach ($this->checkListsIds as $checkListId) {
                    $job = new DataAICallTranscriptionCheckListJob();
                    $job->callTranscriptionId = $this->id;
                    $job->checkListId = $checkListId;
                    Yii::$app->queue->priority(QueueHelper::highPriority())->push($job);
                }
            }
        } catch (Exception $e) {
            Yii::error('Ошибка выполнения job: ' . $e->getMessage(), __METHOD__);
        }
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