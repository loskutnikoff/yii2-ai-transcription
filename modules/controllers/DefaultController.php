<?php

namespace app\modules\transcription\controllers;

use app\components\Perm;
use app\modules\CheckList\models\CallTranscriptionCheckList;
use app\modules\transcription\components\CallTranscriptionService;
use app\modules\transcription\models\CallTranscription;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => static fn() => Yii::$app->user->loginRequired(),
                'rules' => [
                    ['actions' => ['text'], 'allow' => true, 'roles' => [Perm::CALL_TRANSCRIPTION_TEXT]],
                    ['actions' => ['create'], 'allow' => true, 'roles' => [Perm::CALL_TRANSCRIPTION_CREATE]],

                    ['actions' => ['data-ai'], 'allow' => true, 'roles' => [Perm::CALL_TRANSCRIPTION_CREATE]],
                    ['actions' => ['summary'], 'allow' => true, 'roles' => [Perm::DATA_AI_CALL_TRANSCRIPTION]],
                    ['actions' => ['check-list'], 'allow' => true, 'roles' => [Perm::DATA_AI_CALL_TRANSCRIPTION_CHECK_LIST]],
                ],
            ],
        ];
    }

    public function actionText($entityId, $entityType)
    {
        $model = CallTranscription::find()->andWhere(['entity_id' => $entityId, 'entity_type' => $entityType])->one();

        if (!$model) {
            $model = new CallTranscription();
            $model->entity_id = $entityId;
            $model->entity_type = $entityType;
            if (!$model->validate()) {
                throw new BadRequestHttpException();
            }
        }

        if (!$model->getRecord()) {
            return $this->asJson(['success' => false, 'error' => Yii::t('app', 'Запись не найдена')]);
        }

        $checkListId = Yii::$app->request->getQueryParam('checkListId');
        if ($checkListId) {
            $checkList = CallTranscriptionCheckList::findOne($checkListId);
            if ($checkList) {
                return $this->asJson([
                    'success' => true,
                    'form' => $this->renderPartial('_modal-transcription-check-list', ['model' => $model, 'checkList' => $checkList]),
                ]);
            }
        }

        return $this->asJson([
            'success' => true,
            'form' => $this->renderPartial('_modal-transcription', ['model' => $model]),
        ]);
    }

    public function actionCreate()
    {
        $entityId = Yii::$app->request->post('entityId');
        $entityType = Yii::$app->request->post('entityType');

        $model = CallTranscription::findOne(['entity_id' => $entityId, 'entity_type' => $entityType]);
        if ($model->text ?? null) {
            throw new ForbiddenHttpException();
        }

        $model ??= new CallTranscription();
        $model->entity_id = $entityId;
        $model->entity_type = $entityType;
        if (!$model->validate()) {
            return $this->asJson(['success' => false, 'error' => implode(', ', $model->getFirstErrors())]);
        }

        if (!$model->getRecord()) {
            return $this->asJson(['success' => false, 'error' => Yii::t('app', 'Запись не найдена')]);
        }

        try {
            CallTranscriptionService::transcript($model->voximplantHistory);

            return $this->asJson(['success' => true]);
        } catch (Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);

            return $this->asJson(['success' => false, 'error' => Yii::t('app', 'Ошибка при запуске транскрибации')]);
        }
    }

    public function actionDataAi($id)
    {
        $model = CallTranscription::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException();
        }

        return $this->renderPartial('_data_ai', ['model' => $model]);
    }

    public function actionCheckList($id)
    {
        $model = CallTranscription::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException();
        }

        return $this->renderPartial('_check-list', ['model' => $model->checkList]);
    }

    public function actionSummary($id)
    {
        $model = CallTranscription::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException();
        }

        return $this->renderPartial('_summary_call', ['model' => $model]);
    }
}
