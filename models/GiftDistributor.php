<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%fxhb_setting}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $uid
 * @property string $add_time
 * @property string $code

 */
class GiftDistributor extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ls_gift_distributor}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id','uid','code'], 'required'],
            [['store_id', 'add_time',], 'integer'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'Store ID',
            'user_num' => '拆红包所需用户数,最少2人',
            'coupon_total_money' => '红包总金额',
            'coupon_use_minimum' => '赠送的优惠券最低消费金额',
            'coupon_expire' => '红包优惠券有效期',
            'distribute_type' => '红包分配类型：0=随机，1=平分',
            'tpl_msg_id' => '红包到账通知模板消息id',
            'game_time' => '每个红包有效期,单位：小时',
            'game_open' => '是否开启活动，0=不开启，1=开启',
            'rule' => '规则',
            'share_pic' => 'Share Pic',
            'share_title' => 'Share Title',
        ];
    }
}
