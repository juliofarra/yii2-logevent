<?php

namespace lab37\logevent\tests;

use lab37\logevent\migrations\M260612000001CreateLogEventTable;
use Yii;
use yii\console\Application;
use yii\db\Connection;

/**
 * Base test case: boots a minimal Yii console application against an
 * in-memory SQLite database and creates the required tables.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        new Application([
            'id'         => 'logevent-tests',
            'basePath'   => __DIR__,
            'components' => [
                'db' => [
                    'class' => Connection::class,
                    'dsn'   => 'sqlite::memory:',
                ],
            ],
        ]);

        // The package migration creates the default log table; this also
        // verifies that the migration itself runs.
        ob_start();
        (new M260612000001CreateLogEventTable())->up();
        ob_end_clean();

        $this->createLogTable('custom_log_event');

        Yii::$app->db->createCommand()->createTable('item', [
            'id'       => 'pk',
            'name'     => 'text NOT NULL',
            'price'    => 'float',
            'password' => 'text',
            'token'    => 'text',
        ])->execute();
    }

    protected function tearDown(): void
    {
        Yii::$app->db->close();
        Yii::$app = null;

        parent::tearDown();
    }

    /**
     * Creates an additional log table with the same structure as the default
     * one, to test storing logs in alternative tables.
     */
    protected function createLogTable(string $name): void
    {
        Yii::$app->db->createCommand()->createTable($name, [
            'id'        => 'pk',
            'objeto'    => 'text NOT NULL',
            'objeto_id' => 'integer NOT NULL',
            'evento'    => 'text NOT NULL',
            'log_info'  => 'text NOT NULL',
            'id_user'   => 'integer',
            'ts'        => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'ip'        => 'text',
        ])->execute();
    }
}
