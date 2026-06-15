<?php

namespace lab37\logevent\tests;

use lab37\logevent\tests\models\Item;
use lab37\logevent\widgets\assets\LogEventWidgetAsset;
use lab37\logevent\widgets\LogEventWidget;
use Yii;
use yii\base\InvalidConfigException;

class LogEventWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The asset manager needs a valid basePath; in a console app @webroot is
        // undefined, so we point it at a temp dir. The widget's bundle is mapped
        // to `false` so register() resolves to a dummy bundle and never publishes
        // files.
        $webroot = sys_get_temp_dir() . '/logevent-test-webroot';
        @mkdir($webroot . '/assets', 0777, true);
        Yii::setAlias('@webroot', $webroot);
        Yii::setAlias('@web', '/');

        Yii::$app->set('assetManager', [
            'class'    => \yii\web\AssetManager::class,
            'basePath' => $webroot . '/assets',
            'baseUrl'  => '/assets',
            'bundles'  => [
                LogEventWidgetAsset::class => false,
            ],
        ]);
    }

    public function testThrowsWhenModelIsMissing(): void
    {
        $this->expectException(InvalidConfigException::class);
        LogEventWidget::widget([]);
    }

    public function testThrowsWhenModelIsNotActiveRecord(): void
    {
        $this->expectException(InvalidConfigException::class);
        LogEventWidget::widget(['model' => new \stdClass()]);
    }

    public function testRendersDetailsWithEventCount(): void
    {
        $item = $this->createItem();
        $item->name = 'Gadget';
        $this->assertTrue($item->save());

        $html = LogEventWidget::widget(['model' => $item]);

        $this->assertStringContainsString('<details', $html);
        $this->assertStringContainsString('log-event-widget', $html);
        // Two events: INSERT + UPDATE.
        $this->assertStringContainsString('(2)', $html);
    }

    public function testInitiallyOpenAddsOpenAttribute(): void
    {
        $item = $this->createItem();

        $closed = LogEventWidget::widget(['model' => $item]);
        $open   = LogEventWidget::widget(['model' => $item, 'initiallyOpen' => true]);

        $this->assertStringNotContainsString('<details class="log-event-widget" open>', $closed);
        $this->assertStringContainsString('<details class="log-event-widget" open>', $open);
    }

    public function testButtonLabelIsRendered(): void
    {
        $item = $this->createItem();

        $html = LogEventWidget::widget(['model' => $item, 'buttonLabel' => 'Audit Trail']);

        $this->assertStringContainsString('Audit Trail', $html);
    }

    public function testInsertShowsFieldLabelsAndValues(): void
    {
        $item = $this->createItem();

        $html = LogEventWidget::widget(['model' => $item]);

        // Field label comes from the model; value is formatted as text.
        $this->assertStringContainsString($item->getAttributeLabel('name'), $html);
        $this->assertStringContainsString('Widget', $html);
        // The event token is shown raw (no translated prose).
        $this->assertStringContainsString('INSERT', $html);
    }

    public function testUpdateShowsOldAndNewValues(): void
    {
        $item = $this->createItem();
        $item->name = 'Gadget';
        $this->assertTrue($item->save());

        $html = LogEventWidget::widget(['model' => $item]);

        $this->assertStringContainsString('Widget', $html);   // old value
        $this->assertStringContainsString('Gadget', $html);   // new value
        $this->assertStringContainsString('UPDATE', $html);
        $this->assertStringContainsString('log-event-old', $html);
        $this->assertStringContainsString('log-event-new', $html);
    }

    public function testDeleteShowsSnapshot(): void
    {
        $item = $this->createItem();
        $id   = $item->id;
        $this->assertEquals(1, $item->delete());

        // The record is gone from its own table, but its log entries remain.
        // A proxy instance carrying the same id lets the logEvents relation
        // (keyed by entity + entity_id) resolve the deleted record's history.
        $proxy = new Item(['name' => 'x']);
        $proxy->id = $id;

        $html = LogEventWidget::widget(['model' => $proxy]);

        $this->assertStringContainsString('DELETE', $html);
        $this->assertStringContainsString('Widget', $html);
    }

    public function testCustomViewPathOverridesDefault(): void
    {
        $item = $this->createItem();

        $html = LogEventWidget::widget([
            'model'          => $item,
            'customViewPath' => '@app/views/_custom_log',
        ]);

        $this->assertStringContainsString('CUSTOM VIEW for item', $html);
        $this->assertStringContainsString('my-custom-log', $html);
        // The default content table must NOT be rendered.
        $this->assertStringNotContainsString('log-event-table', $html);
        // Still wrapped in the <details> shell.
        $this->assertStringContainsString('<details', $html);
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
