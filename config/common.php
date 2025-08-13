<?php

//some use
use app\modules\transcription\components\gpt\YandexComponent as Gpt;
use app\modules\transcription\components\speech\YandexComponent as Speech;
//some use

return [
    //some code
    'modules' => [
        //some code
        'transcription' => app\modules\transcription\Module::class,
    ],
    'components' => [
        'ffmpeg' => [
            'class' => \app\components\FFMpegComponent::class,
            'ffmpegBinaries' => '/usr/bin/ffmpeg',
            'ffprobeBinaries' => '/usr/bin/ffprobe',
            'timeout' => 3600, // Максимальное время выполнения процесса FFmpeg (в секундах)
            'read_timeout' => 3600, // Максимальное время, в течение которого FFmpeg не должен выводить данные в stdout
            'ffmpegThreads' => 12, // Количество потоков, используемых FFmpeg. Можно оставить 1 для моно.
        ],
    ],
];
