<?php

namespace app\modules\transcription\commands;

use app\components\exceptions\ValidationException;
use app\helpers\Helper;
use app\modules\lms\models\VoximplantHistory;
use yii\console\Controller;

class VoximplantController extends Controller
{
    public function actionFakeCall($requestId)
    {
        Helper::setSystemConsoleUser();
        $model = new VoximplantHistory();
        $model->voximplant_status = VoximplantHistory::VOXIMPLANT_STATUS_SUCCESS;
        $model->request_id = $requestId;
        $model->voximplant_call_record_url = 'https://api.selcdn.ru/v1/SEL_56486/autocrm-1/current/2025/06/04/a6b5584f5058d29137b51ab7045ad1a2e8e29cce.mp3';
        $model->voximplant_call_date = date('Y-m-d H:i:s');
        $model->voximplant_call_duration = 33;
        if (!$model->save()) {
            throw new ValidationException('Ошибка сохранения звонка', $model->errors);
        }
    }
}