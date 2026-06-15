# yii2-logevent

Auditoría automática de cambios para modelos ActiveRecord de Yii2.

Con solo declarar un behavior en cualquier modelo ActiveRecord, todos sus cambios quedan registrados en una tabla de log:

| Evento | Qué se registra |
|---|---|
| `INSERT` | El registro completo, en JSON |
| `UPDATE` | Solo el diff — valores anteriores y nuevos de los campos modificados, en JSON |
| `DELETE` | El registro completo, en JSON |

Cada entrada del log registra además **quién** (ID de usuario), **cuándo** (timestamp) y **desde dónde** (IP del cliente).

> English documentation: [README.md](README.md)

## Características

- Configuración en una línea: se declara el behavior y el modelo queda auditado.
- **Campos excluidos** (`exclude`): invisibles para el log — nunca se guardan, y si un update solo los modifica a ellos, no se genera entrada de log.
- **Campos enmascarados** (`mask`): el log registra que el campo fue cargado o modificado, pero su valor siempre se reemplaza por `*****` (ideal para passwords, tokens y otros secretos).
- **Tabla de log configurable**: distintos modelos pueden loguear en distintas tablas.
- Un fallo al guardar el log nunca interrumpe la operación que se está logueando.
- Funciona en aplicaciones web y de consola.
- Independiente del motor de base de datos: PostgreSQL, MySQL/MariaDB y SQLite cubiertos por tests.

## Requisitos

- PHP >= 8.0
- Yii2 >= 2.0.45
- Para columnas JSON nativas: PostgreSQL 9.4+, MySQL 5.7+ o MariaDB 10.2+. En otros motores (p. ej. SQLite) el JSON se guarda en una columna de texto de forma transparente.

## Instalación

```bash
composer require juliofarra/yii2-logevent
```

## Migración

El paquete incluye una migración con namespace que crea la tabla `log_event`. Agregá el namespace a la configuración de consola:

```php
// console/config/main.php (o config/console.php)
'controllerMap' => [
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationNamespaces' => [
            'console\migrations',           // tus propias migraciones
            'lab37\logevent\migrations',    // yii2-logevent
        ],
    ],
],
```

Y ejecutá:

```bash
php yii migrate
```

La tabla creada es:

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | int, PK | |
| `entity` | string | Nombre de tabla del registro auditado (referencia polimórfica, junto con `entity_id`) |
| `entity_id` | bigint | ID del registro auditado |
| `event` | string | `INSERT`, `UPDATE` o `DELETE` |
| `data` | json | Snapshot o diff |
| `user_id` | int, null | Usuario que realizó la acción |
| `created_at` | timestamp | Cuándo ocurrió (default de DB `CURRENT_TIMESTAMP`) |
| `ip` | string(45), null | IP del cliente (IPv4/IPv6) |

## Uso

Declará el behavior en cualquier modelo ActiveRecord:

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

Listo. Cada insert, update y delete sobre `Order` queda registrado.

### Excluir y enmascarar campos

```php
'logEvent' => [
    'class'   => LogEventBehavior::class,
    'exclude' => ['token_interno'],   // invisible para el log
    'mask'    => ['password'],        // se loguea, pero con el valor oculto
],
```

- **`exclude`** — el campo nunca aparece en el JSON registrado. Si un update solo modifica campos excluidos, no se crea ninguna entrada de log.
- **`mask`** — el campo aparece en el JSON, de modo que se sabe que fue cargado o modificado, pero su valor es siempre `*****` (configurable con `maskValue`). Si el valor enmascarado es `null` se conserva como `null`, para que el log siga mostrando si el campo fue cargado o no.

Ejemplo de un `UPDATE` registrado con `password` enmascarado:

```json
{
    "estado":   {"old": "borrador", "new": "enviado"},
    "password": {"old": "*****", "new": "*****"}
}
```

### Referencia del behavior

| Propiedad | Default | Descripción |
|---|---|---|
| `idAttribute` | `'id'` | Atributo que identifica al registro auditado |
| `logEventClass` | `LogEvent::class` | Clase ActiveRecord donde se guardan las entradas |
| `exclude` | `[]` | Campos invisibles para el log |
| `mask` | `[]` | Campos logueados con el valor oculto |
| `maskValue` | `'*****'` | Valor de reemplazo para campos enmascarados |

### Loguear distintos modelos en distintas tablas

Creá una subclase de `LogEvent` que apunte a otra tabla (antes hay que crear esa tabla con la misma estructura):

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

Y configurala en el behavior:

```php
'logEvent' => [
    'class'         => LogEventBehavior::class,
    'logEventClass' => OrderLogEvent::class,
],
```

### Consultar el log

El behavior agrega la relación `logEvents` al modelo auditado (más recientes primero):

```php
foreach ($order->logEvents as $log) {
    echo $log->event;         // INSERT | UPDATE | DELETE
    echo $log->created_at;    // timestamp
    print_r($log->dataArray); // payload decodificado como array PHP, en cualquier DB
}
```

O consultá directamente:

```php
use lab37\logevent\models\LogEvent;

$logs = LogEvent::find()
    ->forEntity(Order::tableName(), $order->id)
    ->ofEvent(LogEvent::EVENT_UPDATE)
    ->ordered()
    ->all();
```

## Widget de visualización

El paquete incluye un widget opcional que muestra el log de cambios de un modelo
dentro de la misma página donde lo invocás — sin popup, sin Bootstrap, sin
jQuery. Usa un elemento HTML5 `<details>` nativo, así que el log se expande y
contrae sin JavaScript.

```php
use lab37\logevent\widgets\LogEventWidget;

echo LogEventWidget::widget(['model' => $order]);
```

Esto genera un bloque colapsable que lista cada evento de `$order`, mostrando su
fecha/hora, IP, usuario y los campos cambiados (viejo → nuevo en updates, el
snapshot completo en inserts y deletes). Los valores se presentan con
`Yii::$app->formatter`, y las etiquetas de los campos vienen de los
`attributeLabels()` del modelo.

### Referencia del widget

| Propiedad | Default | Descripción |
|---|---|---|
| `model` | *(requerido)* | La instancia ActiveRecord auditada cuyo log se muestra |
| `initiallyOpen` | `false` | Si el bloque `<details>` arranca expandido |
| `buttonLabel` | `'Event Log'` | Texto del botón que despliega |
| `customViewPath` | `null` | Ruta/alias de un view propio que toma el control del renderizado |

### Estilos

El paquete trae una hoja de estilos mínima como asset, registrada
automáticamente. Si querés aplicar tus propios estilos, deshabilitá el bundle en
la configuración de tu asset manager y apuntá a las clases `.log-event-*`:

```php
// config: components.assetManager
'bundles' => [
    'lab37\logevent\widgets\assets\LogEventWidgetAsset' => false,
],
```

### View personalizado

Para controlar el markup por completo, apuntá `customViewPath` a tu propio view.
Recibe `$model` y `$logEvents`, y es el responsable de renderizar el log:

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
    // renderizá como quieras
}
```

### Nota de rendimiento

El view por defecto resuelve `$logEvent->user` por fila para mostrar el nombre
del usuario (con fallback a `user_id`). Para modelos con historiales largos,
hacé eager-loading de la relación para evitar consultas N+1 — o usá un
`customViewPath` que muestre `user_id` directamente.

## Tests

```bash
composer install
composer test
```

La suite corre contra una base SQLite en memoria y también ejercita la migración del paquete.

## Licencia

MIT. Ver [LICENSE](LICENSE).
