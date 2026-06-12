<?php

namespace lab37\logevent\tests\models;

use lab37\logevent\LogEventBehavior;

/**
 * Test fixture: same `item` table, but logging into an alternative table
 * through a custom LogEvent subclass.
 */
class CustomLogItem extends Item
{
    public function behaviors()
    {
        return [
            'logEvent' => [
                'class'         => LogEventBehavior::class,
                'logEventClass' => CustomLogEvent::class,
            ],
        ];
    }
}
