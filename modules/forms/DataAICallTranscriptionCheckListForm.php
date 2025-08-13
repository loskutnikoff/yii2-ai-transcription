<?php

namespace app\modules\transcription\forms;

use app\helpers\QueueHelper;
use app\modules\CheckList\models\CheckList;
use app\modules\transcription\jobs\DataAICallTranscriptionCheckListJob;
use app\modules\transcription\models\CallTranscription;
use Yii;
use yii\base\Model;

class DataAICallTranscriptionCheckListForm extends Model
{
    public $callTranscriptionId;
    public $checkListId;

    public function formName()
    {
        return '';
    }

    public function rules()
    {
        return [
            [['callTranscriptionId', 'checkListId'], 'required'],
            [
                'callTranscriptionId',
                'exist',
                'targetClass' => CallTranscription::class,
                'targetAttribute' => 'id',
            ],
            [
                'checkListId',
                'exist',
                'targetClass' => CheckList::class,
                'targetAttribute' => 'id',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'callTranscriptionId' => Yii::t('app', 'Транскрибация звонка'),
            'checkListId' => Yii::t('app', 'Чек-лист'),
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $job = new DataAICallTranscriptionCheckListJob();
        $job->userId = Yii::$app->user->id;
        $job->callTranscriptionId = $this->callTranscriptionId;
        $job->checkListId = $this->checkListId;

        Yii::$app->queue->priority(QueueHelper::highPriority())->push($job);

        return true;
    }
}