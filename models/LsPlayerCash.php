<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/16
 * Time: 18:40
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class LsPlayerCash
 * @package app\models
 * @property int $id
 * @property int $user_id
 * @property int $add_time
 * @property int $stage
 *
 */

class LsPlayerCash extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%ls_player_cash}}';
    }
}