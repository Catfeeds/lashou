<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/8/4
 * Time: 下午3:37
 */

namespace app\models;


use yii\db\ActiveRecord;

class ActivityUser extends ActiveRecord
{
public static function tableName()
{
    return '{{%activity_user}}';
}
}