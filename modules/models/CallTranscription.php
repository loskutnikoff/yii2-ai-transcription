<?php

namespace app\modules\transcription\models;

use app\components\behaviors\TimestampBehavior;
use app\models\User;
use app\modules\CheckList\components\CheckListCondition\ConditionEntityInterface as CheckListConditionEntityInterface;
use app\modules\CheckList\models\CallTranscriptionCheckList;
use app\modules\lms\models\VoximplantHistory;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $entity_id
 * @property int $entity_type
 * @property string $text
 * @property string $data_json
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read User $createdBy
 * @property-read User $updatedBy
 * @property-read ActiveRecord $entity
 * @property-read DataAI $dataAI
 * @property-read CallTranscriptionCheckList $checkList
 * @property-read VoximplantHistory $voximplantHistory
 * @property-read CallTranscriptionAnalyze $analyze
 */
class CallTranscription extends ActiveRecord implements CheckListConditionEntityInterface
{
    public const ENTITY_TYPE_VOXIMPLANT = 1;

    protected $_entity;

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return 'dsf_call_transcription';
    }

    public function rules(): array
    {
        return [
            [['entity_id', 'entity_type'], 'required'],
            [['entity_id', 'entity_type'], 'integer'],
            ['entity_type', 'in', 'range' => array_keys(self::entityTypeList())],
            ['text', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'entity_id' => Yii::t('app', 'ID сущности'),
            'entity_type' => Yii::t('app', 'Тип сущности'),
            'text' => Yii::t('app', 'Транскрибация'),
            'created_by' => Yii::t('app', 'Автор'),
            'updated_by' => Yii::t('app', 'Изменил'),
            'created_at' => Yii::t('app', 'Дата создания'),
            'updated_at' => Yii::t('app', 'Дата изменения'),
        ];
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
            BlameableBehavior::class => [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
        ];
    }

    public static function entityTypeList(): array
    {
        return [
            self::ENTITY_TYPE_VOXIMPLANT => 'Voximplant',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getDataAI(): ActiveQuery
    {
        return $this->hasOne(DataAI::class, ['entity_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCreatedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUpdatedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    /**
     * @return ActiveQuery
     */
    public function getAnalyze(): ActiveQuery
    {
        return $this->hasOne(CallTranscriptionAnalyze::class, ['call_transcription_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getVoximplantHistory(): ActiveQuery
    {
        return $this->hasOne(VoximplantHistory::class, ['id' => 'entity_id']);
    }

    public function getEntity(): array|ActiveRecord|null
    {
        if (null === $this->_entity) {
            /** @var ActiveRecord $model */
            $class = match ((int)$this->entity_type) {
                self::ENTITY_TYPE_VOXIMPLANT => VoximplantHistory::class,
                default => null,
            };

            $this->_entity = $class ? $class::find()->andWhere(['id' => $this->entity_id])->one() : null;
        }

        return $this->_entity;
    }

    public function getAttributeRecord(): ?string
    {
        return match ((int)$this->entity_type) {
            self::ENTITY_TYPE_VOXIMPLANT => 'voximplant_call_record_url',
            default => null,
        };
    }

    public function getRecord(): ?string
    {
        return ($entity = $this->getEntity()) ? $entity->{$this->getAttributeRecord()} : null;
    }

    /**
     * @return ActiveQuery
     */
    public function getCheckList(): ActiveQuery
    {
        return $this->hasOne(CallTranscriptionCheckList::class, ['call_transcription_id' => 'id']);
    }
}