<?php

namespace lab37\logevent\tests\models;

use lab37\logevent\models\LogEvent;

/**
 * Test fixture: log model that stores entries in an alternative table.
 */
class CustomLogEvent extends LogEvent
{
    public static function tableName()
    {
        return 'custom_log_event';
    }
}
