# yii2-logevent

[![Tests](https://github.com/juliofarra/yii2-logevent/actions/workflows/tests.yml/badge.svg)](https://github.com/juliofarra/yii2-logevent/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/juliofarra/yii2-logevent.svg)](https://packagist.org/packages/juliofarra/yii2-logevent)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Automatic audit trail for Yii2 ActiveRecord models.

Attach a single behavior to any ActiveRecord model and every change is logged to a database table:

| Event | What gets logged |
|---|---|
| `INSERT` | Full record snapshot, as JSON |
| `UPDATE` | Only the diff — old and new values of changed attributes, as JSON |
| `DELETE` | Full record snapshot, as JSON |

Each log entry also records **who** (user ID), **when** (timestamp) and **from where** (client IP).

> Documentación en español: [README.es.md](README.es.md)

## Features

- One-line setup: declare the behavior and the model is audited.
- **Excluded attributes**: invisible to the log — never stored, and changes to them alone don't create a log entry.
- **Masked attributes**: the log records that the attribute was set or changed, but its value is always replaced with `*****` (ideal for passwords, tokens and other secrets).
- **Configurable log table**: different models can log to different tables.
- Logging failures never break the operation being logged.
- Works in web and console applications.
- Database agnostic: PostgreSQL, MySQL/MariaDB and SQLite covered by tests.

## Requirements

- PHP >= 8.0
- Yii2 >= 2.0.45
- For native JSON columns: PostgreSQL 9.4+, MySQL 5.7+ or MariaDB 10.2+. On other databases (e.g. SQLite) the JSON payload is stored in a text column transparently.

## Installation

```bash
composer require juliofarra/yii2-logevent
```

## Migration

The package ships a namespaced migration that creates the `log_event` table. Add the namespace to your console configuration:

```php
// console/config/main.php (or config/console.php)
'controllerMap' => [
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationNamespaces' => [
            'console\migrations',           // your own migrations
            'lab37\logevent\migrations',    // yii2-logevent
        ],
    ],
],
```

Then run:

```bash
php yii migrate
```

The table created is:

| Column | Type | Description |
|---|---|---|
| `id` | int, PK | |
| `objeto` | string | Table name of the audited record |
| `objeto_id` | bigint | ID of the audited record |
| `evento` | string | `INSERT`, `UPDATE` or `DELETE` |
| `log_info` | json | Snapshot or diff |
| `id_user` | int, null | User who performed the action |
| `ts` | timestamp | When it happened (DB default `CURRENT_TIMESTAMP`) |
| `ip` | string(45), null | Client IP (IPv4/IPv6) |

## Usage

Declare the behavior in any ActiveRecord model:

```php
use lab37\logevent\LogEventBehavior;

class Order extends \yii\db\ActiveRecord
{
    public function behaviors()
    {
        return [
            'logEvent' => [
                'class' => LogEventBehavior::class,
            ],
        ];
    }
}
```

That's it. Every insert, update and delete on `Order` is now logged.

### Excluding and masking attributes

```php
'logEvent' => [
    'class'   => LogEventBehavior::class,
    'exclude' => ['internal_token'],   // invisible to the log
    'mask'    => ['password'],         // logged, but value hidden
],
```

- **`exclude`** — the attribute never appears in the logged JSON. If an update only changes excluded attributes, no log entry is created at all.
- **`mask`** — the attribute appears in the logged JSON, so you know it was set or changed, but its value is always `*****` (configurable via `maskValue`). A `null` masked value is kept as `null`, so the log still shows whether the field was loaded.

Example of a logged `UPDATE` with a masked `password`:

```json
{
    "status":   {"old": "draft", "new": "sent"},
    "password": {"old": "*****", "new": "*****"}
}
```

### Behavior reference

| Property | Default | Description |
|---|---|---|
| `idAttribute` | `'id'` | Attribute that identifies the owner record |
| `logEventClass` | `LogEvent::class` | ActiveRecord class used to store log entries |
| `exclude` | `[]` | Attributes invisible to the log |
| `mask` | `[]` | Attributes logged with their value hidden |
| `maskValue` | `'*****'` | Replacement value for masked attributes |

### Logging different models to different tables

Create a subclass of `LogEvent` that points to another table (create that table with the same structure first):

```php
use lab37\logevent\models\LogEvent;

class OrderLogEvent extends LogEvent
{
    public static function tableName()
    {
        return '{{%order_log_event}}';
    }
}
```

And configure it in the behavior:

```php
'logEvent' => [
    'class'         => LogEventBehavior::class,
    'logEventClass' => OrderLogEvent::class,
],
```

### Querying the log

The behavior adds a `logEvents` relation to the owner model (newest first):

```php
foreach ($order->logEvents as $log) {
    echo $log->evento;           // INSERT | UPDATE | DELETE
    echo $log->ts;               // timestamp
    print_r($log->logInfoArray); // payload decoded as PHP array, on any DB
}
```

Or query directly:

```php
use lab37\logevent\models\LogEvent;

$logs = LogEvent::find()
    ->forObject(Order::tableName(), $order->id)
    ->ofEvent(LogEvent::EVENT_UPDATE)
    ->ordered()
    ->all();
```

## Tests

```bash
composer install
composer test
```

The suite runs against an in-memory SQLite database and also exercises the package migration.

## License

MIT. See [LICENSE](LICENSE).
