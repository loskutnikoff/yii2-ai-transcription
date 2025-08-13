<?php

namespace app\modules\transcription\components;

use app\modules\transcription\models\CallTranscription;
use Yii;

trait CallTranscriptionJobTrait
{
    protected function sendCompletionPushNotification(?CallTranscription $model): void
    {
        Yii::$app->push->sendMessage(
            $this->userId,
            [
                'type' => 'call-transcription-complete',
                'entityId' => $model->entity_id,
                'entityType' => $model->entity_type,
            ]
        );
    }

    protected function sendErrorPushNotification(string $error, $entityId, $entityType): void
    {
        Yii::$app->push->sendMessage(
            $this->userId,
            [
                'type' => 'call-transcription-error',
                'entityId' => $entityId,
                'entityType' => $entityType,
                'error' => $error,
            ]
        );
    }
}