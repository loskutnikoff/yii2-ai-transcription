<?php

namespace app\modules\transcription\traits;

use app\modules\transcription\models\DataAI;

trait ParseJsonTrait
{
    public function getAnswerCallTranscription(): ?string
    {
        return $this->entity_type == DataAI::ENTITY_TYPE_CALL_TRANSCRIPTION
            ? (json_decode($this->data_json ?? '{}', true)[DataAI::KEY_SUMMARY_ANSWER] ?? null)
            : null;
    }
}