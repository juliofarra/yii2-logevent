<?php

namespace lab37\logevent\models;

use yii\db\ActiveQuery;

/**
 * ActiveQuery class for [[LogEvent]].
 *
 * @see LogEvent
 *
 * @author Julio Farra <juliofarra@gmail.com>
 */
class LogEventQuery extends ActiveQuery
{
    /**
     * Filters by audited entity (table name) and optionally by record ID.
     *
     * @param string $entity Table name of the audited record, as returned by its `tableName()`.
     * @param int|string|null $entityId ID of the audited record.
     * @return LogEventQuery
     */
    public function forEntity(string $entity, $entityId = null): LogEventQuery
    {
        $this->andWhere(['entity' => $entity]);

        if ($entityId !== null) {
            $this->andWhere(['entity_id' => $entityId]);
        }

        return $this;
    }

    /**
     * Filters by event type (one of the LogEvent::EVENT_* constants).
     *
     * @param string $event
     * @return LogEventQuery
     */
    public function ofEvent(string $event): LogEventQuery
    {
        return $this->andWhere(['event' => $event]);
    }

    /**
     * Orders the log entries from newest to oldest.
     *
     * @return LogEventQuery
     */
    public function ordered(): LogEventQuery
    {
        return $this->addOrderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
    }

    /**
     * {@inheritdoc}
     * @return LogEvent[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return LogEvent|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
