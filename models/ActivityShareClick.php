<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/8/4
 * Time: 下午3:52
 */

namespace app\models;


use yii\db\ActiveRecord;

class ActivityShareClick extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%activity_share_click}}';
    }
}