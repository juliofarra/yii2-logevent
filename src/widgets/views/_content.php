<?php

use lab37\logevent\models\LogEvent;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\db\ActiveRecord $model The audited model, used for attribute labels */
/** @var LogEvent[] $logEvents Log entries, newest first */

if ($logEvents === []) {
    return;
}
?>
<table class="log-event-table">
    <?php foreach ($logEvents as $logEvent) {
        $data = $logEvent->getDataArray();
        if (empty($data)) {
            continue;
        }

        switch ($logEvent->event) {
            case LogEvent::EVENT_INSERT:
            case LogEvent::EVENT_UPDATE:
            case LogEvent::EVENT_DELETE:
                $row = $this->render('_event_' . strtolower($logEvent->event), [
                    'model'     => $model,
                    'logEvent'  => $logEvent,
                    'data'      => $data,
                ]);
                break;

            default:
                $row = Html::encode(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        ?>
        <tr>
            <td><?= $row ?></td>
        </tr>
    <?php } ?>
</table>
