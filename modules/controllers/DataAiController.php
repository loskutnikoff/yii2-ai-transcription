<?php

namespace app\modules\transcription\controllers;

use app\components\Html;
use app\components\Perm;
use app\helpers\QueueHelper;
use app\modules\transcription\forms\DataAICallTranscriptionCheckListForm;
use app\modules\transcription\jobs\DataAISummaryCallProgressJob;
use app\modules\transcription\models\DataAI;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class DataAiController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => static fn() => Yii::$app->user->loginRequired(),
                'rules' => [
                    ['actions' => ['transcription'], 'allow' => true, 'roles' => [Perm::DATA_AI_CALL_TRANSCRIPTION]],
                    ['actions' => ['check-list'], 'allow' => true, 'roles' => [Perm::DATA_AI_CALL_TRANSCRIPTION_CHECK_LIST]],
                ],
            ],
        ];
    }

    public function actionTranscription()
    {
        $entityId = Yii::$app->request->post('entityId');
        $entityType = Yii::$app->request->post('entityType');
        $prompt = Yii::$app->request->post('prompt');

        $model = DataAI::find()->andWhere(['entity_id' => $entityId, 'entity_type' => $entityType])->one();
        if (!$model) {
            $model = new DataAI();
            $model->entity_id = $entityId;
            $model->entity_type = $entityType;
        }
        if (!$model->validate()) {
            return $this->asJson(['success' => false, 'error' => implode(', ', $model->getFirstErrors())]);
        }

        if (!($model->entity->text ?? null)) {
            return $this->asJson(['success' => false, 'error' => Yii::t('app', 'Не найдена транскрибация')]);
        }

        try {
            $job = new DataAISummaryCallProgressJob();
            $job->userId = Yii::$app->user->id;
            $job->entityId = $model->entity_id;
            $job->entityType = $model->entity_type;
            $job->prompt = $prompt;
            Yii::$app->queue->priority(QueueHelper::highPriority())->push($job);

            return $this->asJson(['success' => true]);
        } catch (Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);

            return $this->asJson(['success' => false, 'error' => Yii::t('app', 'Ошибка при обработке ИИ')]);
        }
    }

    public function actionCheckList()
    {
        $model = new DataAICallTranscriptionCheckListForm();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['success' => false, 'error' => Html::errorSummary($model)]);
    }
}
