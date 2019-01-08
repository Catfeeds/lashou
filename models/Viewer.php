<?php

namespace app\models;

use Yii;
use Codeception\PHPUnit\ResultPrinter\HTML;

/**
 * This is the model class for table "{{%video}}".
 *
 * @property integer $id
 * @property string $title
 * @property string $url
 * @property string $sort
 * @property integer $is_delete
 * @property integer $addtime
 * @property integer $store_id
 * @property string $pic_url
 * @property string $content
 * @property integer $type
 */
class Viewer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ls_viewer}}';
    }
}