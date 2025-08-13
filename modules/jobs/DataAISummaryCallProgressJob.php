<?php

namespace app\modules\transcription\jobs;

use app\components\exceptions\ValidationException;
use app\components\queue\AbstractJob;
use app\modules\transcription\models\CallTranscription;
use app\modules\transcription\models\DataAI;
use Yii;
use yii\helpers\VarDumper;
use yii\queue\RetryableJobInterface;

class DataAISummaryCallProgressJob extends AbstractJob implements RetryableJobInterface
{
    public const TTR = 5 * 3600;
    public const RETRY_ATTEMPTS = 5;

    public $entityId;
    public $entityType;
    public $prompt;

    /**
     * @param \yii\queue\Queue $queue
     * @return mixed|void
     */
    public function execute($queue)
    {
        parent::execute($queue);
        try {
            $model = DataAI::find()->andWhere(['entity_id' => $this->entityId, 'entity_type' => $this->entityType])->one();
            if (!$model) {
                $model = new DataAI();
                $model->entity_id = $this->entityId;
                $model->entity_type = $this->entityType;
            }

            if (!($text = ($model->getEntity()->text ?? null))) {
                return;
            }

            $prompt = $this->prompt ? urldecode($this->prompt) : DataAI::TMP_PROMPT;

            $dto = Yii::$app->gpt->request($prompt, $text, false);
            $model->data_json = json_encode(['summary_answer' => $dto->answer]);
            $model->prompt = $prompt;
            if (!$model->save(false)) {
                Yii::error('Ошибка сохранения' . VarDumper::dumpAsString([$model->attributes, $model->errors]));
                throw new ValidationException('Ошибка при сохранении обработки ИИ', $model->getErrors());
            }
            sleep(2);
            $this->sendCompletionPushNotification();
        } catch (\Exception $e) {
            Yii::error('Ошибка выполнения джобы: ' . $e->getMessage(), __METHOD__);
            $this->sendErrorPushNotification($e->getMessage());
        }
    }

    private function sendCompletionPushNotification(): void
    {
        Yii::$app->push->sendMessage(
            $this->userId,
            [
                'type' => 'call-summary-complete',
                'entityId' => $this->entityId,
                'entityType' => $this->entityType,
            ]
        );
    }

    private function sendErrorPushNotification(string $error): void
    {
        Yii::$app->push->sendMessage(
            $this->userId,
            [
                'type' => 'call-summary-error',
                'entityId' => $this->entityId,
                'entityType' => $this->entityType,
                'error' => $error,
            ]
        );
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
