<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/8/4
 * Time: 下午9:24
 */

namespace app\models;


use yii\db\ActiveRecord;

class ActivityShare extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%activity_share}}';
    }
}