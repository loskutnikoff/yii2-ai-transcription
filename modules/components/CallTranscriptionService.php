<?php

namespace app\modules\transcription\components;

use app\helpers\QueueHelper;
use app\modules\lms\models\RequestType;
use app\modules\lms\models\VoximplantHistory;
use app\modules\transcription\jobs\CallTranscriptionProgressJob;
use app\modules\transcription\models\CallTranscription;
use Yii;

class CallTranscriptionService
{
    public static function transcript(VoximplantHistory $model)
    {
        $requestType = $model->request->requestType;
        if (
            ($requestType->ai_assistant_mode ?? null) == RequestType::AI_ASSISTANT_ENABLED
            || (
                ($requestType->ai_assistant_mode ?? null) == RequestType::AI_ASSISTANT_PARENT
                && ($requestTypeParent = $requestType->getTopLevelAiParent())
                && $requestTypeParent->ai_assistant_mode == RequestType::AI_ASSISTANT_ENABLED
            )
        ) {
            $checkLists = $requestType->ai_assistant_check_list_ids;
            $prompt = $requestType->ai_assistant_prompt;
            if ($requestType->ai_assistant_mode == RequestType::AI_ASSISTANT_PARENT) {
                $checkLists = $requestTypeParent->ai_assistant_check_list_ids ?? null;
                $prompt = $requestTypeParent->ai_assistant_prompt ?? null;
            }

            $transcriptionJob = new CallTranscriptionProgressJob();
            $transcriptionJob->entityId = $model->id;
            $transcriptionJob->entityType = CallTranscription::ENTITY_TYPE_VOXIMPLANT;
            $transcriptionJob->checkListsIds = array_filter((array)$checkLists);
            $transcriptionJob->prompt = $prompt;
            Yii::$app->queue->priority(QueueHelper::highPriority())->push($transcriptionJob);
        }
    }
}