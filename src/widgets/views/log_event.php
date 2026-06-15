<?php

use lab37\logevent\widgets\assets\LogEventWidgetAsset;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\db\ActiveRecord $model The audited model */
/** @var \lab37\logevent\models\LogEvent[] $logEvents Log entries, newest first */
/** @var bool $initiallyOpen Whether the <details> starts expanded */
/** @var string $buttonLabel Text shown on the <summary> toggle */
/** @var string|null $customViewPath Optional custom view that renders the log */

LogEventWidgetAsset::register($this);

$count = count($logEvents);
$open  = $initiallyOpen ? ' open' : '';

$viewPath = $customViewPath ?? '_content';
?>
<details class="log-event-widget"<?= $open ?>>
    <summary class="log-event-summary">
        <?= Html::encode($buttonLabel) ?> (<?= $count ?>)
    </summary>
    <div class="log-event-content">
        <?= $this->render($viewPath, [
                'model'     => $model,
                'logEvents' => $logEvents,
            ]);
        ?>
    </div>
</details>
