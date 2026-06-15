<?php

namespace lab37\logevent\widgets\assets;

use yii\web\AssetBundle;

/**
 * Minimal stylesheet for [[\lab37\logevent\widgets\LogEventWidget]].
 *
 * It carries no framework dependencies (no Bootstrap, no jQuery): just a small
 * set of rules scoped to the `.log-event-*` classes. Applications that prefer to
 * style the widget themselves can skip this bundle entirely.
 *
 * @author Julio Farra <juliofarra@gmail.com>
 */
class LogEventWidgetAsset extends AssetBundle
{
    /**
     * {@inheritdoc}
     */
    public $sourcePath = __DIR__ . '/css';

    /**
     * {@inheritdoc}
     */
    public $css = [
        'log-event-widget.css',
    ];
}
