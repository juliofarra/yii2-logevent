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
 * The `entity` + `entity_id` pair is a polymorphic reference to the audited
 * record: `entity` holds its table name and `entity_id` its primary key.
 *
 * @property int $id
 * @property string $entity Table name of the audited record
 * @property int $entity_id ID of the audited record
 * @property string $event Logged SQL event: INSERT, UPDATE or DELETE
 * @property array|string $data Logged data, in JSON format
 * @property int|null $user_id ID of the user who performed the action
 * @property string $created_at Timestamp of the event
 * @property string|null $ip Client IP address
 *
 * @property-read array|null $dataArray `data` decoded as a PHP array
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
     * encoding/decoding of `data` automatically.
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
            [['entity', 'entity_id', 'event', 'data'], 'required'],
            [['entity'], 'string'],
            [['event'], 'in', 'range' => [self::EVENT_INSERT, self::EVENT_UPDATE, self::EVENT_DELETE]],
            [['entity_id', 'user_id'], 'integer'],
            [['ip'], 'string', 'max' => 45],
            [['data', 'created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'entity'     => 'Entity',
            'entity_id'  => 'Entity ID',
            'event'      => 'Event',
            'data'       => 'Data',
            'user_id'    => 'User',
            'created_at' => 'Created At',
            'ip'         => 'IP',
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
     * Encodes `data` manually for drivers without native JSON support
     * (e.g. SQLite). PostgreSQL and MySQL are handled by Yii itself.
     *
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (is_array($this->data)
            && !in_array(static::getDb()->driverName, self::JSON_NATIVE_DRIVERS, true)
        ) {
            $this->data = json_encode($this->data, JSON_UNESCAPED_UNICODE);
        }

        return true;
    }

    /**
     * Returns `data` decoded as a PHP array, regardless of the database
     * driver in use.
     *
     * @return array|null
     */
    public function getDataArray(): ?array
    {
        if (is_array($this->data)) {
            return $this->data;
        }

        if (is_string($this->data) && $this->data !== '') {
            $decoded = json_decode($this->data, true);

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

        return $this->hasOne(Yii::$app->user->identityClass, ['id' => 'user_id']);
    }
}
