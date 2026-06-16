<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var \lab37\logevent\models\LogEvent $logEvent The event being rendered */

$formatter = \Yii::$app->formatter;

$datetime = $formatter->asDatetime($logEvent->created_at);
// Prefer the related user's name; fall back to the numeric id when the relation
// is null or the identity class has no `username` attribute. The `??` chain uses
// isset() semantics, so a null `user` relation never raises a warning. The
// `has('user')` guard keeps the widget working when no user component exists
// (e.g. console apps), where resolving the relation would throw.
$user = $logEvent->user_id;
if (\Yii::$app->has('user')) {
    $user = $logEvent->user->username ?? $logEvent->user_id;
}
$user = $formatter->asText($user);
$ip   = $logEvent->ip !== null ? $formatter->asText($logEvent->ip) : '';
?>
<small class="log-event-meta">
    <span class="log-event-datetime"><?= Html::encode($datetime) ?></span>
    <?php if ($ip !== '') { ?>
        | IP: <span class="log-event-ip"><?= Html::encode($ip) ?></span>
    <?php } ?>
    | <span class="log-event-user"><?= Html::encode($user) ?></span>
    | <span class="log-event-action"><?= Html::encode($logEvent->event) ?></span>
</small>
