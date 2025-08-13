<?php

/** @noinspection PhpUnhandledExceptionInspection */

use app\modules\transcription\models\CallTranscription;
use yii\bootstrap\Html;
use yii\web\View;

/**
 * @var $this View
 * @var $model CallTranscription
 */
?>

<?php if ($model->dataAI) : ?>
<div class="alert alert-default alert__attention mb-10">
    <div class="h4 mb-10"><?= Yii::t('app', 'Итог звонка') ?></div>
    <div><?= Html::encode($model->dataAI->getAnswerCallTranscription()) ?></div>
</div>
<?php endif; ?>