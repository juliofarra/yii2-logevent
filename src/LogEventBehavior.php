<?php

namespace lab37\logevent;

use lab37\logevent\models\LogEvent;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\Request as WebRequest;

/**
 * Behavior that automatically logs every change made to an ActiveRecord model.
 *
 * Attach it to any ActiveRecord and every INSERT, UPDATE and DELETE will be
 * recorded in a log table:
 *
 * - INSERT: stores the full record as JSON.
 * - UPDATE: stores only the changed attributes (old and new values) as JSON.
 * - DELETE: stores the full record as JSON.
 *
 * Usage:
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'logEvent' => [
 *             'class'   => LogEventBehavior::class,
 *             'exclude' => ['token'],    // invisible to the log
 *             'mask'    => ['password'], // logged as "*****"
 *         ],
 *     ];
 * }
 * ```
 *
 * To store the log in a different table, create a subclass of [[LogEvent]]
 * that overrides `tableName()` and set it in [[logEventClass]].
 *
 * @property-read LogEvent[] $logEvents Log entries of the owner record, newest first.
 *
 * @author Julio Farra <juliofarra@gmail.com>
 */
class LogEventBehavior extends Behavior
{
    /**
     * @var string Name of the attribute that identifies the owner record.
     */
    public $idAttribute = 'id';

    /**
     * @var string ActiveRecord class used to store the log entries.
     * Must be [[LogEvent]] or a subclass of it. Override it to store the log
     * of a given model in a different table.
     */
    public $logEventClass = LogEvent::class;

    /**
     * @var string[] Attributes invisible to the log: they are never included
     * in the logged JSON and changes to them alone do not trigger a log entry.
     */
    public $exclude = [];

    /**
     * @var string[] Attributes whose value is hidden: the log records that the
     * attribute exists, was set on insert or changed on update, but its value
     * is always replaced with [[maskValue]].
     */
    public $mask = [];

    /**
     * @var string Replacement value for masked attributes.
     */
    public $maskValue = '*****';

    /**
     * {@inheritdoc}
     */
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT  => 'afterInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_AFTER_DELETE  => 'afterDelete',
        ];
    }

    /**
     * Relation to the log entries of the owner record, newest first.
     *
     * Being a relation, it can be accessed as `$model->logEvents`.
     *
     * @return ActiveQuery
     */
    public function getLogEvents(): ActiveQuery
    {
        return $this->owner->hasMany($this->logEventClass, ['objeto_id' => $this->idAttribute])
            ->andWhere(['objeto' => $this->owner->tableName()])
            ->ordered();
    }

    /**
     * Logs the full record after an INSERT.
     */
    public function afterInsert($event): void
    {
        $this->saveLog(LogEvent::EVENT_INSERT, $this->snapshot());
    }

    /**
     * Logs only the changed attributes (old and new values) before an UPDATE.
     * If nothing relevant changed, no log entry is created.
     */
    public function beforeUpdate($event): void
    {
        $changes = $this->diff();

        if ($changes !== []) {
            $this->saveLog(LogEvent::EVENT_UPDATE, $changes);
        }
    }

    /**
     * Logs the full record after a DELETE.
     */
    public function afterDelete($event): void
    {
        $this->saveLog(LogEvent::EVENT_DELETE, $this->snapshot());
    }

    /**
     * Full snapshot of the owner attributes, honoring [[exclude]] and [[mask]].
     *
     * Masked attributes keep `null` values as `null`, so the log still shows
     * whether the attribute was loaded or not.
     *
     * @return array
     */
    protected function snapshot(): array
    {
        $attributes = $this->owner->attributes;

        foreach ($this->exclude as $name) {
            unset($attributes[$name]);
        }

        foreach ($this->mask as $name) {
            if (array_key_exists($name, $attributes) && $attributes[$name] !== null) {
                $attributes[$name] = $this->maskValue;
            }
        }

        return $attributes;
    }

    /**
     * Changed attributes as `['attribute' => ['old' => ..., 'new' => ...]]`,
     * honoring [[exclude]] and [[mask]].
     *
     * @return array
     */
    protected function diff(): array
    {
        $owner   = $this->owner;
        $changes = [];

        foreach ($owner->getDirtyAttributes() as $name => $value) {
            if (in_array($name, $this->exclude, true)) {
                continue;
            }

            $old = $owner->getOldAttribute($name);
            if ($old == $value) {
                continue;
            }

            if (in_array($name, $this->mask, true)) {
                $changes[$name] = ['old' => $this->maskValue, 'new' => $this->maskValue];
            } else {
                $changes[$name] = ['old' => $old, 'new' => $value];
            }
        }

        return $changes;
    }

    /**
     * Creates and saves a log entry.
     *
     * A failure to save the log never interrupts the operation being logged:
     * errors are reported through [[handleErrors()]].
     *
     * @param string $evento One of the LogEvent::EVENT_* constants.
     * @param array $info Data to store in the `log_info` JSON field.
     */
    protected function saveLog(string $evento, array $info): void
    {
        /** @var LogEvent $log */
        $log = new $this->logEventClass();

        $log->evento    = $evento;
        $log->objeto    = $this->owner->tableName();
        $log->objeto_id = $this->owner->getAttribute($this->idAttribute);
        $log->log_info  = $info;
        $log->id_user   = $this->resolveUserId();
        $log->ip        = $this->resolveIp();

        if (!$log->save()) {
            $this->handleErrors($log);
        }
    }

    /**
     * ID of the user performing the action, or null when there is no user
     * component (console applications) or the user is a guest.
     *
     * @return int|string|null
     */
    protected function resolveUserId()
    {
        if (Yii::$app !== null && Yii::$app->has('user')) {
            return Yii::$app->user->id;
        }

        return null;
    }

    /**
     * Client IP, or null when not running a web request.
     *
     * @return string|null
     */
    protected function resolveIp(): ?string
    {
        if (Yii::$app !== null && Yii::$app->request instanceof WebRequest) {
            $ip = Yii::$app->request->userIP;

            return $ip !== null && filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : null;
        }

        return null;
    }

    /**
     * Reports log saving errors without interrupting the logged operation:
     * always through Yii's error log, and as a flash message when a session
     * is available.
     */
    protected function handleErrors(LogEvent $log): void
    {
        $message = 'LogEvent failed for ' . $this->owner->tableName() . ': '
            . json_encode($log->errors, JSON_UNESCAPED_UNICODE);

        Yii::error($message, __METHOD__);

        if (Yii::$app !== null && Yii::$app->has('session')) {
            Yii::$app->session->setFlash('error', $message);
        }
    }
}
