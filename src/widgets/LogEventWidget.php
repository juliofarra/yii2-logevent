<?php

namespace lab37\logevent\widgets;

use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\db\ActiveRecord;

/**
 * Widget that renders the change log of an audited ActiveRecord model.
 *
 * It displays the events recorded by [[\lab37\logevent\LogEventBehavior]] for a
 * given model, wrapped in a native HTML5 `<details>` element so the log can be
 * expanded and collapsed without JavaScript.
 *
 * The widget has no external UI dependencies: no Bootstrap, no jQuery, no icon
 * fonts. Its only styling comes from the bundled [[LogEventWidgetAsset]].
 *
 * Basic usage:
 *
 * ```php
 * echo LogEventWidget::widget(['model' => $model]);
 * ```
 *
 * To take full control of the presentation, point [[customViewPath]] to your
 * own view. When set, the widget renders *only* that view — nothing else, not
 * even the `<details>` shell or the bundled asset — so the view is responsible
 * for the entire output, including any toggle button it wants. It receives
 * `$model`, `$logEvents`, `$initiallyOpen` and `$buttonLabel`:
 *
 * ```php
 * echo LogEventWidget::widget([
 *     'model'          => $model,
 *     'customViewPath' => '@app/views/audit/_log',
 * ]);
 * ```
 *
 * @author Julio Farra <juliofarra@gmail.com>
 */
class LogEventWidget extends Widget
{
    /**
     * @var ActiveRecord The audited model whose log entries will be displayed.
     * Must have the [[\lab37\logevent\LogEventBehavior]] attached, which exposes
     * the `logEvents` relation.
     */
    public $model;

    /**
     * @var bool Whether the `<details>` element starts expanded.
     */
    public $initiallyOpen = false;

    /**
     * @var string Text shown on the `<summary>` toggle.
     */
    public $buttonLabel = 'Event Log';

    /**
     * @var string|null Path or alias of a custom view used to render the log.
     * When set, the widget renders only this view (the built-in `<details>`
     * shell and asset are skipped entirely) and passes it `$model`,
     * `$logEvents`, `$initiallyOpen` and `$buttonLabel`. When null, the default
     * view is used.
     */
    public $customViewPath;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException when [[model]] is not a valid ActiveRecord.
     */
    public function init()
    {
        parent::init();

        if (!$this->model instanceof ActiveRecord) {
            throw new InvalidConfigException(
                'LogEventWidget requires the "model" property to be an ActiveRecord instance.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $viewPath = $this->customViewPath ?? 'log_event';

        return $this->render($viewPath, [
            'model'         => $this->model,
            'logEvents'     => $this->model->logEvents,
            'initiallyOpen' => $this->initiallyOpen,
            'buttonLabel'   => $this->buttonLabel,
        ]);
    }
}
