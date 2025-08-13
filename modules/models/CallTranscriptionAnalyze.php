<?php

namespace app\modules\transcription\models;

use app\components\ActiveRecord;
use app\components\behaviors\AttributeObjectBehavior;
use app\components\behaviors\TimestampBehavior;
use app\models\User;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveQuery;

/**
 * @property integer $id
 * @property integer $call_transcription_id
 * @property string $data_json
 * @property boolean $is_successful
 * @property integer $created_by
 * @property integer $updated_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read CallTranscription $callTranscription
 */
class CallTranscriptionAnalyze extends ActiveRecord
{
    public ?CallTranscriptionAnalyzeData $data;

    public static function tableName()
    {
        return 'dsf_call_transcription_analyze';
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'call_transcription_id' => Yii::t('app', 'Транскрибация звонка'),
            'is_successful' => Yii::t('app', 'Успешный контакт'),
            'created_by' => Yii::t('app', 'Автор'),
            'updated_by' => Yii::t('app', 'Изменил'),
            'created_at' => Yii::t('app', 'Дата создания'),
            'updated_at' => Yii::t('app', 'Изменен'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class => [
                'class' => TimestampBehavior::class,
            ],
            BlameableBehavior::class => [
                'class' => BlameableBehavior::class,
            ],
            AttributeObjectBehavior::class => [
                'class' => AttributeObjectBehavior::class,
                'jsonAttribute' => 'data_json',
                'objectAttribute' => 'data',
                'objectClass' => CallTranscriptionAnalyzeData::class,
            ],
        ];
    }

    public function getCallTranscription(): ActiveQuery
    {
        return $this->hasOne(CallTranscription::class, ['id' => 'call_transcription_id']);
    }

    public function getCreatedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getUpdatedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }
}