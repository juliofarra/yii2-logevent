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
     * Filters by audited object (table name) and optionally by record ID.
     *
     * @param string $objeto Table name of the audited record, as returned by its `tableName()`.
     * @param int|string|null $objetoId ID of the audited record.
     * @return LogEventQuery
     */
    public function forObject(string $objeto, $objetoId = null): LogEventQuery
    {
        $this->andWhere(['objeto' => $objeto]);

        if ($objetoId !== null) {
            $this->andWhere(['objeto_id' => $objetoId]);
        }

        return $this;
    }

    /**
     * Filters by event type (one of the LogEvent::EVENT_* constants).
     *
     * @param string $evento
     * @return LogEventQuery
     */
    public function ofEvent(string $evento): LogEventQuery
    {
        return $this->andWhere(['evento' => $evento]);
    }

    /**
     * Orders the log entries from newest to oldest.
     *
     * @return LogEventQuery
     */
    public function ordered(): LogEventQuery
    {
        return $this->addOrderBy(['ts' => SORT_DESC, 'id' => SORT_DESC]);
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
