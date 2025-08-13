<?php

namespace app\modules\transcription\components\dto;

class RecognizeDto
{
    public $text;
    public $array = [];
    /** How much audio was processed (milliseconds) */
    public $time;
}