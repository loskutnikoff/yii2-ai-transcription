<?php

namespace app\modules\transcription\jobs;

use app\components\exceptions\ValidationException;
use app\components\queue\AbstractJob;
use app\helpers\QueueHelper;
use app\modules\transcription\components\CallTranscriptionJobTrait;
use app\modules\transcription\models\CallTranscription;
use Exception;
use FFMpeg\Format\Audio\Mp3;
use Yii;
use yii\helpers\VarDumper;
use yii\queue\Queue;

class CallTranscriptionProgressJob extends AbstractJob
{
    use CallTranscriptionJobTrait;

    public $entityId;
    public $entityType;
    public $checkListsIds;
    public $prompt;

    /**
     * @param Queue $queue
     *
     * @return mixed|void
     */
    public function execute($queue)
    {
        parent::execute($queue);

        $originalAudioPath = null;
        $monoAudioPath = null;

        try {
            $model = CallTranscription::findOne(['entity_id' => $this->entityId, 'entity_type' => $this->entityType]);
            if (!$model) {
                $model = new CallTranscription();
                $model->entity_id = $this->entityId;
                $model->entity_type = $this->entityType;
            }
            if (!$recordUrl = $model->getRecord()) {
                $this->sendErrorPushNotification('Запись не найдена', $this->entityId, $this->entityType);

                return;
            }
            try {
                $originalAudioContent = @file_get_contents($recordUrl);
                if ($originalAudioContent === false) {
                    throw new Exception('Не удалось скачать аудиофайл по URL: ' . $recordUrl);
                }

                $originalAudioPath = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . uniqid('original_audio_', true) . '.mp3';
                file_put_contents($originalAudioPath, $originalAudioContent);
                $channels = 1;

                if (Yii::$app->ffmpeg) {
                    $ffmpeg = Yii::$app->ffmpeg->getFfmpeg();
                    $ffprobe = Yii::$app->ffmpeg->getFfprobe();

                    $audioStream = null;
                    foreach ($ffprobe->streams($originalAudioPath) as $stream) {
                        if ($stream->isAudio()) {
                            $audioStream = $stream;
                            break;
                        }
                    }

                    if ($audioStream) {
                        $channels = $audioStream->get('channels');
                    }

                    Yii::info('Определено количество каналов аудио: ' . ($channels ?? 'неизвестно'), __METHOD__);

                    if ($channels > 2) {
                        Yii::info('Аудиофайл не моно (' . ($channels ?? 'неизвестно') . ' каналов), выполняется конвертация в моно.', __METHOD__);

                        $monoAudioPath = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . uniqid('mono_audio_', true) . '.mp3';

                        $audio = $ffmpeg->open($originalAudioPath);
//                    $audio->filters()->setAudioChannels(1);
//                    $audio
//                        ->filters()
//                        ->custom('[0:a]pan=mono|c0=0[aout]')
//                        ->map(['[aout]'])
//                        ->save(new Mp3(), $monoAudioPath);
//                    $audio->save(new Mp3(), $monoAudioPath);
                        $format = new Mp3();
//                    $audio->addFilter(new CustomFilter('-ac 1'));
                        $format->setAudioChannels(1);
                        $audio->save($format, $monoAudioPath);
                        $audioToProcessPath = $monoAudioPath;
                        Yii::info('Аудиофайл успешно конвертирован в моно.', __METHOD__);
                    } else {
                        $audioToProcessPath = $originalAudioPath;
                        Yii::info('Аудиофайл уже моно, конвертация не требуется.', __METHOD__);
                    }
                } else {
                    $audioToProcessPath = $originalAudioPath;
                }

                $audioContent = @file_get_contents($audioToProcessPath);
                if ($audioContent === false) {
                    throw new Exception('Не удалось прочитать подготовленный аудиофайл: ' . $audioToProcessPath);
                }
                $encodedAudio = base64_encode($audioContent);

                $id = Yii::$app->speech->recognize($encodedAudio, $channels);
                if ($id) {
                    if (!$model->save(false)) {
                        Yii::error('Ошибка сохранения транскрибации: ' . VarDumper::dumpAsString([$model->attributes, $model->errors]), __METHOD__);
                        throw new ValidationException('Ошибка при сохранении распознавания записи', $model->getErrors());
                    }

                    $job = new CallRecognitionResultJob();
                    $job->callTranscriptionId = $model->id;
                    $job->recognizeId = $id;
                    $job->checkListsIds = $this->checkListsIds;
                    $job->prompt = $this->prompt;
                    Yii::$app->queue->delay(10)->priority(QueueHelper::highPriority())->push($job);
                }
            } catch (ValidationException $e) {
                throw new ValidationException($e->getMessage());
            } catch (Exception $e) {
                Yii::error('Ошибка обработки аудио или распознавания: ' . $e->getMessage(), __METHOD__);
                $this->sendErrorPushNotification('Ошибка обработки аудио: ' . $e->getMessage(), $this->entityId, $this->entityType);
            } finally {
                if ($originalAudioPath && file_exists($originalAudioPath)) {
                    unlink($originalAudioPath);
                }
                if ($monoAudioPath && file_exists($monoAudioPath)) {
                    unlink($monoAudioPath);
                }
            }
        } catch (Exception $e) {
            Yii::error('Ошибка выполнения джобы: ' . $e->getMessage(), __METHOD__);
            $this->sendErrorPushNotification($e->getMessage(), $this->entityId, $this->entityType);
        }
    }
}
