<?php

namespace lab37\logevent\migrations;

use yii\db\Migration;

/**
 * Creates the `log_event` table used by [[\lab37\logevent\LogEventBehavior]].
 *
 * Compatible with PostgreSQL, MySQL/MariaDB and SQLite. On databases with
 * native JSON support the `log_info` column is created as JSON/JSONB; on the
 * rest it falls back to a plain text column transparently.
 */
class M260612000001CreateLogEventTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%log_event}}', [
            'id'        => $this->primaryKey(),
            'objeto'    => $this->string(255)->notNull(),
            'objeto_id' => $this->bigInteger()->notNull(),
            'evento'    => $this->string(10)->notNull(),
            'log_info'  => $this->json()->notNull(),
            'id_user'   => $this->integer()->null(),
            'ts'        => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'ip'        => $this->string(45)->null(),
        ], $tableOptions);

        $this->createIndex('idx_log_event_objeto', '{{%log_event}}', ['objeto', 'objeto_id']);
        $this->createIndex('idx_log_event_id_user', '{{%log_event}}', 'id_user');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%log_event}}');
    }
}
