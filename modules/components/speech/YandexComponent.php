<?php

namespace app\modules\transcription\components\speech;

use app\components\exceptions\ValidationException;
use app\modules\transcription\components\dto\RecognizeDto;
use DateTimeImmutable;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Yii;
use yii\base\Component;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;

class YandexComponent extends Component implements SpeechRecognizerInterface
{
    public $url = 'https://stt.api.cloud.yandex.net/';
    public $apiKey = '....';
    public $loggingEnabled = false;

    public function recognize(string $content, $channels): ?string
    {
        try {
            $client = $this->getGuzzle();

            $data = [
                'content' => $content,
                'recognition_model' => [
                    'model' => 'general',
                    'audio_format' => [
                        "rawAudio" => [
                            'audioChannelCount' => $channels,
                        ],
                        'container_audio' => [
                            'container_audio_type' => 'MP3',
                        ],
                    ],
                    'text_normalization' => [
                        'text_normalization' => 'TEXT_NORMALIZATION_ENABLED',
                        'literature_text' => true,
                    ],
                    'audio_processing_type' => 'FULL_DATA',
                ],
                'speechAnalysis' => [
                    'enable_speaker_analysis' => true,
                    'enable_conversation_analysis' => true,
                ],
            ];

            if ($channels == 1) {
                $data['speaker_labeling'] = ['speaker_labeling' => 'SPEAKER_LABELING_ENABLED'];
            }

            $request = $client->createRequest()
                ->setMethod('POST')
                ->setUrl('stt/v3/recognizeFileAsync')
                ->setFormat(Client::FORMAT_JSON)
                ->addHeaders([
                    'Accept-Encoding' => 'gzip',
                    'Authorization' => "Api-Key {$this->apiKey}",
                    'x-data-logging-enabled' => $this->loggingEnabled,
                ])
                ->setData($data);

            $response = $request->send();

            if (!$response->isOk) {
                return null;
            }

            $response = $response->getData();

            return $response['id'] ?? null;
        } catch (Exception $e) {
            Yii::error($e);
        }

        return null;
    }

    public function recognition($id, $date): ?RecognizeDto
    {
        try {
            $client = $this->getGuzzle();

            $date = $date ? new DateTimeImmutable($date) : null;

            $request = $client->createRequest()
                ->setMethod('GET')
                ->setUrl('stt/v3/getRecognition?' . http_build_query(['operation_id' => $id]))
                ->setFormat(Client::FORMAT_JSON)
                ->addHeaders([
                    'Accept-Encoding' => 'gzip',
                    'Authorization' => "Api-Key {$this->apiKey}",
                    'x-data-logging-enabled' => $this->loggingEnabled,
                ]);

            $response = $request->send();
            if (!$response->isOk) {
                if (($data = $response->getData()) && isset($data['error']) && $data['error']['httpCode'] != 404) {
                    throw new ValidationException($data['error']['message']);
                }

                return null;
            }

            $response = json_decode('[' . str_replace("}\n{", "},\n{", $response->getContent()) . ']', true);

            $dto = new RecognizeDto();

            $dto->time = $response[0]['result']['audioCursors']['receivedDataMs'] ?? 0;

            foreach ($response as $item) {
                if (!isset($item['result'])) {
                    continue;
                }

                $resultBlock = $item['result'];
                $channelTag = $resultBlock['channelTag'] ?? null;
                $text = null;
                $finalIndex = null;

                if (isset($resultBlock['finalRefinement']['normalizedText']['alternatives'][0]['text'])) {
                    $text = $resultBlock['finalRefinement']['normalizedText']['alternatives'][0]['text'];
                    $finalIndex = $resultBlock['finalRefinement']['finalIndex'] ?? null;
                }

                if (null === $channelTag || !$text || null === $finalIndex) {
                    continue;
                }

                $row = [
                    'tag' => "Спикер {$channelTag}",
                    'text' => $text,
                    'startTimeMs' => $resultBlock['finalRefinement']['normalizedText']['alternatives'][0]['startTimeMs'] ?? 0,
                    'time' => null,
                ];

                if ($date) {
                    $time = $date->modify('+' . $row['startTimeMs'] . ' milliseconds');
                    $row['time'] = Yii::$app->formatter->asTime($time);
                }

                $dto->array[] = $row;
            }

            usort($dto->array, function ($a, $b) {
                return ($a['startTimeMs'] ?? 0) <=> ($b['startTimeMs'] ?? 0);
            });

            $formattedDialogue = [];
            foreach ($dto->array as $item) {
                $formattedDialogue[] = "{$item['time']} [{$item['tag']}]: {$item['text']}";
            }

            $dto->text = trim(implode(PHP_EOL, $formattedDialogue)) . PHP_EOL;

            return $dto;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                return null;
            }

            throw $e;
        } catch (ValidationException $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    protected function getGuzzle(): Client
    {
        static $client;

        if (null === $client) {
            $client = new Client([
                'baseUrl' => $this->url,
                'transport' => CurlTransport::class,
            ]);
        }

        return $client;
    }
}