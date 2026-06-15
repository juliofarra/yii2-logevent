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
| `entity` | string | Table name of the audited record (polymorphic reference, together with `entity_id`) |
| `entity_id` | bigint | ID of the audited record |
| `event` | string | `INSERT`, `UPDATE` or `DELETE` |
| `data` | json | Snapshot or diff |
| `user_id` | int, null | User who performed the action |
| `created_at` | timestamp | When it happened (DB default `CURRENT_TIMESTAMP`) |
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
    echo $log->event;         // INSERT | UPDATE | DELETE
    echo $log->created_at;    // timestamp
    print_r($log->dataArray); // payload decoded as PHP array, on any DB
}
```

Or query directly:

```php
use lab37\logevent\models\LogEvent;

$logs = LogEvent::find()
    ->forEntity(Order::tableName(), $order->id)
    ->ofEvent(LogEvent::EVENT_UPDATE)
    ->ordered()
    ->all();
```

## Display widget

The package ships an optional widget that renders a model's change log inside the
page where you call it — no popup, no Bootstrap, no jQuery. It uses a native
HTML5 `<details>` element so the log expands and collapses without JavaScript.

```php
use lab37\logevent\widgets\LogEventWidget;

echo LogEventWidget::widget(['model' => $order]);
```

This outputs a collapsible block listing every event for `$order`, each showing
its timestamp, IP, user and the changed fields (old → new for updates, the full
snapshot for inserts and deletes). Values are rendered through
`Yii::$app->formatter`, and field labels come from the model's
`attributeLabels()`.

### Widget reference

| Property | Default | Description |
|---|---|---|
| `model` | *(required)* | The audited ActiveRecord instance whose log is displayed |
| `initiallyOpen` | `false` | Whether the `<details>` block starts expanded |
| `buttonLabel` | `'Event Log'` | Text shown on the toggle |
| `customViewPath` | `null` | Path/alias of a custom view that takes over rendering |

### Styling

A minimal stylesheet is bundled as an asset and registered automatically. To
style the widget yourself, disable the bundle in your asset manager
configuration and target the `.log-event-*` classes:

```php
// config: components.assetManager
'bundles' => [
    'lab37\logevent\widgets\assets\LogEventWidgetAsset' => false,
],
```

### Custom view

For full control over the markup, point `customViewPath` to your own view. It
receives `$model` and `$logEvents` and is responsible for rendering the log:

```php
echo LogEventWidget::widget([
    'model'          => $order,
    'customViewPath' => '@app/views/audit/_log',
]);
```

```php
// @app/views/audit/_log.php
/** @var yii\db\ActiveRecord $model */
/** @var lab37\logevent\models\LogEvent[] $logEvents */

foreach ($logEvents as $log) {
    // render however you like
}
```

### Performance note

The default view resolves `$logEvent->user` per row to show the user's name
(falling back to `user_id`). For models with long histories, eager-load the
relation to avoid N+1 queries — or use a `customViewPath` that displays
`user_id` directly.

## Tests

```bash
composer install
composer test
```

The suite runs against an in-memory SQLite database and also exercises the package migration.

## License

MIT. See [LICENSE](LICENSE).
