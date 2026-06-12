<?php

namespace lab37\logevent\tests\models;

use lab37\logevent\LogEventBehavior;
use yii\db\ActiveRecord;

/**
 * Test fixture: a model audited with LogEventBehavior, with one excluded
 * attribute (`token`) and one masked attribute (`password`).
 *
 * @property int $id
 * @property string $name
 * @property float|null $price
 * @property string|null $password
 * @property string|null $token
 */
class Item extends ActiveRecord
{
    public static function tableName()
    {
        return 'item';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name', 'password', 'token'], 'string'],
            [['price'], 'number'],
        ];
    }

    public function behaviors()
    {
        return [
            'logEvent' => [
                'class'   => LogEventBehavior::class,
                'exclude' => ['token'],
                'mask'    => ['password'],
            ],
        ];
    }
}
