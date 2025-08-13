<?php

namespace app\modules\transcription\models;

use app\components\AttributeObjectInterface;
use Yii;
use yii\base\Model;

class CallTranscriptionAnalyzeData extends Model implements AttributeObjectInterface
{
    public $is_dissatisfied;
    public $dissatisfied_reason;
    public $is_dialogue_with_operator;
    public $is_dialogue_with_employee;
    public $is_refused_without_transfer;
    public $is_not_leave_request;
    public $is_postpone_call;

    public function asArray()
    {
        return $this->attributes;
    }

    public function attributeLabels()
    {
        return [
            'is_dissatisfied' => Yii::t('app', 'Клиент выразил недовольство'),
            'dissatisfied_reason' => Yii::t('app', 'Причина недовольства'),
            'is_dialogue_with_operator' => Yii::t('app', 'Состоялся диалог между оператором и клиентом'),
            'is_dialogue_with_employee' => Yii::t('app', 'Состоялся диалог между менеджером\сотрудником сервисного отдела и клиентом'),
            'is_refused_without_transfer' => Yii::t('app', 'Клиент отказался от общения и не удалось договориться о переносе звонка'),
            'is_not_leave_request' => Yii::t('app', 'Клиент сказал, что не оставлял заявку'),
            'is_postpone_call' => Yii::t('app', 'Клиент или менеджер не смогли говорить и договорились о переносе звонка'),
        ];
    }
}