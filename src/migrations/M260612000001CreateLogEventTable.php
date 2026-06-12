<?php

namespace lab37\logevent\migrations;

use yii\db\Migration;

/**
 * Creates the `log_event` table used by [[\lab37\logevent\LogEventBehavior]].
 *
 * Compatible with PostgreSQL, MySQL/MariaDB and SQLite. On databases with
 * native JSON support the `data` column is created as JSON/JSONB; on the
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
            'id'         => $this->primaryKey(),
            'entity'     => $this->string(255)->notNull(),
            'entity_id'  => $this->bigInteger()->notNull(),
            'event'      => $this->string(10)->notNull(),
            'data'       => $this->json()->notNull(),
            'user_id'    => $this->integer()->null(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'ip'         => $this->string(45)->null(),
        ], $tableOptions);

        $this->createIndex('idx_log_event_entity', '{{%log_event}}', ['entity', 'entity_id']);
        $this->createIndex('idx_log_event_user_id', '{{%log_event}}', 'user_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%log_event}}');
    }
}
