<?php

namespace app\components;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use yii\base\Component;

class FFMpegComponent extends Component
{
    public $ffmpegBinaries = '/usr/bin/ffmpeg';
    public $ffprobeBinaries = '/usr/bin/ffprobe';
    public $timeout = 3600;
    public $ffmpegThreads = 12;
    public $read_timeout = 3600;

    private $_ffmpeg;
    private $_ffprobe;

    public function getFfmpeg(): FFMpeg
    {
        if ($this->_ffmpeg === null) {
            $this->_ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => $this->ffmpegBinaries,
                'ffprobe.binaries' => $this->ffprobeBinaries,
                'timeout'          => $this->timeout,
                'ffmpeg.threads'   => $this->ffmpegThreads,
                'read_timeout'     => $this->read_timeout,
            ]);
        }
        return $this->_ffmpeg;
    }

    public function getFfprobe(): FFProbe
    {
        if ($this->_ffprobe === null) {
            $this->_ffprobe = FFProbe::create([
                'ffprobe.binaries' => $this->ffprobeBinaries,
                'timeout'          => $this->timeout,
            ]);
        }
        return $this->_ffprobe;
    }
}
