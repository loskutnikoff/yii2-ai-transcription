<?php

namespace app\modules\transcription\models;

use app\components\behaviors\TimestampBehavior;
use app\components\SortInterface;
use app\modules\transcription\traits\ParseJsonTrait;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $entity_id
 * @property int $entity_type
 * @property string $prompt
 * @property string $data_json
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read ActiveRecord $entity
 */
class DataAI extends ActiveRecord implements SortInterface
{
    use ParseJsonTrait;

    public const ENTITY_TYPE_CALL_TRANSCRIPTION = 1;

    public const TMP_PROMPT = 'Сделай резюме звонка в 200 символов';

    public const KEY_SUMMARY_ANSWER = 'summary_answer';

    private $entity;

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return 'dsf_data_ai';
    }

    public function rules(): array
    {
        return [
            [['entity_id', 'entity_type'], 'required'],
            [['entity_id', 'entity_type'], 'integer'],
            ['entity_type', 'in', 'range' => array_keys(self::entityTypeList())],
            [['data_json', 'prompt'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'entity_id' => Yii::t('app', 'ID сущности'),
            'entity_type' => Yii::t('app', 'Тип сущности'),
            'prompt' => Yii::t('app', 'Промпт'),
            'data_json' => Yii::t('app', 'Ответ от ИИ'),
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
        ];
    }

    public static function entityTypeList(): array
    {
        return [
            self::ENTITY_TYPE_CALL_TRANSCRIPTION => Yii::t('app', 'Транскрибация звонка'),
        ];
    }

    public function getEntity(): ActiveRecord|CallTranscription|null
    {
        if (null === $this->entity) {
            /** @var ActiveRecord $model */
            $model = match ((int)$this->entity_type) {
                self::ENTITY_TYPE_CALL_TRANSCRIPTION => CallTranscription::class,
                default => null,
            };

            $this->entity = $model ? $model::findOne(['id' => $this->entity_id]) : null;
        }

        return $this->entity;
    }

    public function getSortTimestamp()
    {
        return strtotime($this->created_at);
    }
}