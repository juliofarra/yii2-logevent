<?php

namespace lab37\logevent\models;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * ActiveRecord model for the log table (`log_event` by default).
 *
 * Each row records one event (INSERT, UPDATE or DELETE) performed on a model
 * audited with [[\lab37\logevent\LogEventBehavior]].
 *
 * To store the log of certain models in a different table, create a subclass
 * that overrides `tableName()` and configure it in the behavior's
 * `logEventClass` property.
 *
 * @property int $id
 * @property string $objeto Table name of the audited record
 * @property int $objeto_id ID of the audited record
 * @property string $evento Logged SQL event: INSERT, UPDATE or DELETE
 * @property array|string $log_info Logged data, in JSON format
 * @property int|null $id_user ID of the user who performed the action
 * @property string $ts Timestamp of the event
 * @property string|null $ip Client IP address
 *
 * @property-read array|null $logInfoArray `log_info` decoded as a PHP array
 *
 * @author Julio Farra <juliofarra@gmail.com>
 */
class LogEvent extends ActiveRecord
{
    public const EVENT_INSERT = 'INSERT';
    public const EVENT_UPDATE = 'UPDATE';
    public const EVENT_DELETE = 'DELETE';

    /**
     * Drivers with native JSON column support, where Yii handles the
     * encoding/decoding of `log_info` automatically.
     */
    private const JSON_NATIVE_DRIVERS = ['pgsql', 'mysql'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%log_event}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['objeto', 'objeto_id', 'evento', 'log_info'], 'required'],
            [['objeto'], 'string'],
            [['evento'], 'in', 'range' => [self::EVENT_INSERT, self::EVENT_UPDATE, self::EVENT_DELETE]],
            [['objeto_id', 'id_user'], 'integer'],
            [['ip'], 'string', 'max' => 45],
            [['log_info', 'ts'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'        => 'ID',
            'objeto'    => 'Object',
            'objeto_id' => 'Object ID',
            'evento'    => 'Event',
            'log_info'  => 'Log Info',
            'id_user'   => 'User',
            'ts'        => 'Timestamp',
            'ip'        => 'IP',
        ];
    }

    /**
     * {@inheritdoc}
     * @return LogEventQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new LogEventQuery(get_called_class());
    }

    /**
     * Encodes `log_info` manually for drivers without native JSON support
     * (e.g. SQLite). PostgreSQL and MySQL are handled by Yii itself.
     *
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (is_array($this->log_info)
            && !in_array(static::getDb()->driverName, self::JSON_NATIVE_DRIVERS, true)
        ) {
            $this->log_info = json_encode($this->log_info, JSON_UNESCAPED_UNICODE);
        }

        return true;
    }

    /**
     * Returns `log_info` decoded as a PHP array, regardless of the database
     * driver in use.
     *
     * @return array|null
     */
    public function getLogInfoArray(): ?array
    {
        if (is_array($this->log_info)) {
            return $this->log_info;
        }

        if (is_string($this->log_info) && $this->log_info !== '') {
            $decoded = json_decode($this->log_info, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    /**
     * Relation to the user who performed the action, resolved through the
     * application's user component.
     *
     * @return ActiveQuery
     * @throws InvalidConfigException when no user component is configured.
     */
    public function getUser(): ActiveQuery
    {
        if (Yii::$app === null || !Yii::$app->has('user')) {
            throw new InvalidConfigException(
                'No "user" application component is configured; the "user" relation cannot be resolved.'
            );
        }

        return $this->hasOne(Yii::$app->user->identityClass, ['id' => 'id_user']);
    }
}
