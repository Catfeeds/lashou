<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/13
 * Time: 16:33
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class LsPlayerWeightLog
 * @package app\models
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $add_date
 * @property string $add_date_time
 * @property float $weight
 * @property string $video_url
 * @property int $status
 * @property string $remark
 *
 */
class LsPlayerWeightLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%ls_player_weight_log}}';
    }
}