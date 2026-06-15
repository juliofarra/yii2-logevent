<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\db\ActiveRecord $model The audited model, used for attribute labels */
/** @var \lab37\logevent\models\LogEvent $logEvent The event being rendered */
/** @var array $data Decoded payload: ['field' => ['old' => ..., 'new' => ...], ...] */

$formatter = Yii::$app->formatter;

echo $this->render('_meta', ['logEvent' => $logEvent]);
?>
<div class="log-event-fields">
    <?php foreach ($data as $field => $values) {
        $old = array_key_exists('old', $values) ? $formatter->asText($values['old']) : '';
        $new = array_key_exists('new', $values) ? $formatter->asText($values['new']) : '';
        ?>
        <div class="log-event-field">
            <span class="log-event-label"><?= Html::encode($model->getAttributeLabel($field)) ?>:</span>
            <span class="log-event-change">
                <span class="log-event-old"><?= Html::encode($old) ?></span>
                <span class="log-event-arrow">&rarr;</span>
                <span class="log-event-new"><?= Html::encode($new) ?></span>
            </span>
        </div>
    <?php } ?>
</div>
