<?php

namespace app\modules\transcription\components\gpt;

use app\modules\transcription\components\dto\SummaryAnswerDto;
use Exception;
use Yii;
use yii\base\Component;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;

class YandexComponent extends Component
{
    public $url = 'https://llm.api.cloud.yandex.net/';
    public $modelUri = 'gpt://....';
    public $apiKey = '....';

    public function request($question, $data, $jsonObject = true): ?SummaryAnswerDto
    {
        try {
            $client = $this->getGuzzle();

            $request = $client->createRequest()
                ->setMethod('POST')
                ->setUrl('foundationModels/v1/completion')
                ->setFormat(Client::FORMAT_JSON)
                ->addHeaders([
                    'Accept-Encoding' => 'gzip',
                    'Authorization' => "Api-Key {$this->apiKey}",
                ])
                ->setData([
                    'modelUri' => $this->modelUri,
                    'completionOptions' => [
                        'stream' => false,
                        'temperature' => 0.2,
                        'maxTokens' => '32000',
//                        'reasoningOptions' => ['mode' => 'DISABLED'],
                    ],
                    'messages' => array_merge(array_filter([
                        ['role' => 'system', 'text' => $question],
                        $data ? ['role' => 'user', 'text' => $data] : null,
                    ])),
                    'jsonObject' => $jsonObject,
                ]);

            $response = $request->send();
            if ($response->isOk && ($data = $response->getData())) {
                $dto = new SummaryAnswerDto();
                $dto->answer = $data['result']['alternatives'][0]['message']['text'] ?? null;

                return $dto;
            }
        } catch (Exception $e) {
            Yii::error($e);
        }

        return null;
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