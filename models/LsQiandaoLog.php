<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/5/29
 * Time: 17:29
 */

namespace app\models;

use yii\db\ActiveRecord;

class LsQiandaoLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%ls_qiandao_log}}';
    }
}