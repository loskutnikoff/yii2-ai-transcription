<?php

namespace app\modules\transcription\jobs;

use app\components\exceptions\RetryJobException;
use app\components\exceptions\ValidationException;
use app\components\queue\AbstractJob;
use app\helpers\QueueHelper;
use app\modules\transcription\components\CallTranscriptionJobTrait;
use app\modules\transcription\models\CallTranscription;
use app\modules\transcription\models\DataAI;
use Exception;
use Yii;
use yii\helpers\VarDumper;
use yii\queue\RetryableJobInterface;

class CallRecognitionResultJob extends AbstractJob implements RetryableJobInterface
{
    use CallTranscriptionJobTrait;

    public const TTR = 5 * 3600;
    public const MAX_ATTEMPTS = 50;
    public const RETRY_ATTEMPTS = 5;

    public $recognizeId;
    public $callTranscriptionId;
    public $checkListsIds;
    public $attempt = 1;
    public $prompt;

    protected ?CallTranscription $model;

    public function execute($queue)
    {
        parent::execute($queue);

        $this->model = CallTranscription::findOne($this->callTranscriptionId);
        if (!$this->model) {
            throw new RetryJobException();
        }

        try {
            $dto = Yii::$app->speech->recognition($this->recognizeId, $this->model->voximplantHistory->voximplant_call_date ?? null);

            if (!$dto) {
                if ($this->attempt <= self::MAX_ATTEMPTS) {
                    $job = new CallRecognitionResultJob();
                    $job->callTranscriptionId = $this->callTranscriptionId;
                    $job->recognizeId = $this->recognizeId;
                    $job->attempt = $this->attempt + 1;
                    Yii::$app->queue->delay(10)->priority(QueueHelper::highPriority())->push($job);
                } else {
                    Yii::error(
                        'Ошибка распознавания Yandex SpeechKit: ' . VarDumper::dumpAsString([$this->model->attributes, $this->model->errors]),
                        __METHOD__
                    );
                    throw new ValidationException('Ошибка распознавания');
                }

                return;
            }

            $this->model->text = $this->postProcessText($dto->text);
            $this->model->data_json = $this->postProcessAsJson($this->model->text);
            if (!$this->model->save(false)) {
                Yii::error('Ошибка сохранения транскрибации: ' . VarDumper::dumpAsString([$this->model->attributes, $this->model->errors]), __METHOD__);
                throw new ValidationException('Ошибка при сохранении распознавания записи', $this->model->getErrors());
            }

            $job = new CallTranscriptionAnalyzeJob();
            $job->id = $this->model->id;
            $job->checkListsIds = $this->checkListsIds;
            Yii::$app->queue->priority(QueueHelper::highPriority())->push($job);

            if ($this->prompt) {
                $job = new DataAISummaryCallProgressJob();
                $job->entityId = $this->model->id;
                $job->entityType = DataAI::ENTITY_TYPE_CALL_TRANSCRIPTION;
                $job->prompt = $this->prompt;
                Yii::$app->queue->priority(QueueHelper::highPriority())->push($job);
            }
            $this->sendCompletionPushNotification($this->model);
        } catch (Exception $e) {
            Yii::error('Ошибка выполнения джобы: ' . $e->getMessage(), __METHOD__);
            $this->sendErrorPushNotification($e->getMessage(), $this->model->entity_id, $this->model->entity_type);
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

    protected function postProcessText(string $text)
    {
        $prompt = 'Проанализируй следующий текст.
        Выполни две задачи:
        1. Разметь каждую реплику, указав роль говорящего, не обращай на внимание на разметку которая есть в тексте. Возможные роли:
        Робот — автоинформатор или автоматическая система;
        Оператор — сотрудник, принимающий звонок и соединяющий с отделом;
        Менеджер 1, {Менеджер 2} и т. д. — сотрудники отдела продаж, с которыми общается клиент после оператора;
        Клиент — если говорит клиент.
        Важно: внимательно отмечай автоматические системные голосовые вставки как [Робот]. Это ключевая часть задачи.
        2. В тексте каждой реплики исправь только искажения, связанные с тематикой автобизнеса. Например:
        "Тенг", "Тенк", "Тент" — заменить на "Tank";
        "подставка" заменить на "процентная ставка";
        "Трейд Ин" → "трейд-ин" и т.д.
        Не меняй структуру реплик. Не удаляй слова вроде "угу", "так", "ну".
        Формат ответа: Время [Роль]: исправленный текст реплики.';

        return Yii::$app->gpt->request($prompt, $text, false)?->answer;
    }

    public static function postProcessAsJson(string $text): array
    {
        preg_match_all('/(\d{2}:\d{2})\s+\[(.*)\]:\s*(.*)(?=\n)/m', $text, $matches, PREG_SET_ORDER);

        if ($matches) {
            return array_map(fn($item) => ['time' => $item[1] ?? null, 'role' => $item[2] ?? null, 'text' => $item[3] ?? null], $matches);
        }

        return [];
    }
}