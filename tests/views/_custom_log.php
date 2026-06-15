<?php

/** @var yii\web\View $this */
/** @var yii\db\ActiveRecord $model */
/** @var \lab37\logevent\models\LogEvent[] $logEvents */

/* Test fixture: a custom view passed to LogEventWidget via customViewPath.
   It must receive $model and $logEvents and render its own markup. */
?>
<div class="my-custom-log" data-count="<?= count($logEvents) ?>">
    CUSTOM VIEW for <?= $model->tableName() ?>
</div>
