<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\db\ActiveRecord $model The audited model, used for attribute labels */
/** @var \lab37\logevent\models\LogEvent $logEvent The event being rendered */
/** @var array $data Decoded payload: ['field' => value, ...] */

$formatter = \Yii::$app->formatter;

echo $this->render('_meta', ['logEvent' => $logEvent]);
?>
<div class="log-event-fields">
    <?php foreach ($data as $field => $value) { ?>
        <div class="log-event-field">
            <span class="log-event-label"><?= Html::encode($model->getAttributeLabel($field)) ?>:</span>
            <span class="log-event-value"><?= Html::encode($formatter->asText($value)) ?></span>
        </div>
    <?php } ?>
</div>
