<?php

namespace app\modules\transcription\components\speech;

use app\modules\transcription\components\dto\RecognizeDto;

interface SpeechRecognizerInterface
{
    /**
     * @param string $content base64_encode string
     *
     * @return string|null
     */
    public function recognize(string $content, $channels): ?string;
}