<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/11
 * Time: 16:05
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class LsPartnerList
 * @package app\models
 *
 * @property integer user_id
 * @property integer total
 * @property integer active_num
 * @property float active_rate
 * @property string last_update
 *
 */
class LsPartnerList extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%ls_partner_list}}';
    }
}