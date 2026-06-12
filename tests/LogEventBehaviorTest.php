<?php

namespace lab37\logevent\tests;

use lab37\logevent\models\LogEvent;
use lab37\logevent\tests\models\CustomLogEvent;
use lab37\logevent\tests\models\CustomLogItem;
use lab37\logevent\tests\models\Item;

class LogEventBehaviorTest extends TestCase
{
    public function testInsertLogsFullSnapshot(): void
    {
        $item = $this->createItem();

        $log = LogEvent::find()->forEntity('item', $item->id)->one();

        $this->assertNotNull($log);
        $this->assertSame(LogEvent::EVENT_INSERT, $log->event);
        $this->assertSame('item', $log->entity);
        $this->assertEquals($item->id, $log->entity_id);

        $info = $log->dataArray;
        $this->assertSame('Widget', $info['name']);
        $this->assertEquals(9.99, $info['price']);
        $this->assertEquals($item->id, $info['id']);
    }

    public function testInsertExcludesAndMasksAttributes(): void
    {
        $item = $this->createItem();

        $info = LogEvent::find()->forEntity('item', $item->id)->one()->dataArray;

        $this->assertArrayNotHasKey('token', $info, 'Excluded attributes must not appear in the log');
        $this->assertArrayHasKey('password', $info, 'Masked attributes must appear in the log');
        $this->assertSame('*****', $info['password'], 'Masked attributes must be stored as the mask value');
    }

    public function testUpdateLogsOnlyChangedAttributes(): void
    {
        $item = $this->createItem();

        $item->name = 'Gadget';
        $this->assertTrue($item->save());

        $log = LogEvent::find()->forEntity('item', $item->id)->ofEvent(LogEvent::EVENT_UPDATE)->one();

        $this->assertNotNull($log);
        $this->assertSame(
            ['name' => ['old' => 'Widget', 'new' => 'Gadget']],
            $log->dataArray
        );
    }

    public function testUpdateWithoutChangesDoesNotLog(): void
    {
        $item = $this->createItem();

        $this->assertTrue($item->save());

        $count = LogEvent::find()->forEntity('item', $item->id)->ofEvent(LogEvent::EVENT_UPDATE)->count();
        $this->assertEquals(0, $count);
    }

    public function testUpdateOfOnlyExcludedAttributesDoesNotLog(): void
    {
        $item = $this->createItem();

        $item->token = 'new-token';
        $this->assertTrue($item->save());

        $count = LogEvent::find()->forEntity('item', $item->id)->ofEvent(LogEvent::EVENT_UPDATE)->count();
        $this->assertEquals(0, $count, 'Changes only to excluded attributes must not trigger a log entry');
    }

    public function testUpdateOfMaskedAttributeLogsMaskedValues(): void
    {
        $item = $this->createItem();

        $item->password = 'new-secret';
        $this->assertTrue($item->save());

        $log = LogEvent::find()->forEntity('item', $item->id)->ofEvent(LogEvent::EVENT_UPDATE)->one();

        $this->assertNotNull($log, 'Changes to masked attributes must trigger a log entry');
        $this->assertSame(
            ['password' => ['old' => '*****', 'new' => '*****']],
            $log->dataArray
        );
    }

    public function testDeleteLogsFullSnapshot(): void
    {
        $item = $this->createItem();
        $id   = $item->id;

        $this->assertEquals(1, $item->delete());

        $log = LogEvent::find()->forEntity('item', $id)->ofEvent(LogEvent::EVENT_DELETE)->one();

        $this->assertNotNull($log);
        $info = $log->dataArray;
        $this->assertSame('Widget', $info['name']);
        $this->assertSame('*****', $info['password']);
        $this->assertArrayNotHasKey('token', $info);
    }

    public function testCustomLogEventClassStoresInAlternativeTable(): void
    {
        $item = new CustomLogItem(['name' => 'Custom']);
        $this->assertTrue($item->save());

        $this->assertEquals(1, CustomLogEvent::find()->forEntity('item', $item->id)->count());
        $this->assertEquals(0, LogEvent::find()->forEntity('item', $item->id)->count());
    }

    public function testUserAndIpAreNullInConsoleApplications(): void
    {
        $item = $this->createItem();

        $log = LogEvent::find()->forEntity('item', $item->id)->one();

        $this->assertNull($log->user_id);
        $this->assertNull($log->ip);
    }

    public function testLogEventsRelationReturnsEntriesNewestFirst(): void
    {
        $item = $this->createItem();

        $item->name = 'Gadget';
        $this->assertTrue($item->save());

        $logs = $item->logEvents;

        $this->assertCount(2, $logs);
        $this->assertSame(LogEvent::EVENT_UPDATE, $logs[0]->event);
        $this->assertSame(LogEvent::EVENT_INSERT, $logs[1]->event);
    }

    public function testTimestampIsFilledByDatabaseDefault(): void
    {
        $item = $this->createItem();

        $log = LogEvent::find()->forEntity('item', $item->id)->one();

        $this->assertNotEmpty($log->created_at);
    }

    private function createItem(): Item
    {
        $item = new Item([
            'name'     => 'Widget',
            'price'    => 9.99,
            'password' => 'secret',
            'token'    => 'abc123',
        ]);

        $this->assertTrue($item->save(), 'Fixture item should save: ' . json_encode($item->errors));

        return $item;
    }
}
