<?php

namespace app\models;

use Yii;
use Codeception\PHPUnit\ResultPrinter\HTML;

/**
 * This is the model class for table "{{%ls_cash}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $store_id
 * @property string $price
 * @property integer $status
 * @property integer $is_delete
 * @property integer $add_time
 * @property integer $pay_time
 * @property integer $type
 * @property string $mobile
 * @property string $name
 * @property string $bank_name
 * @property integer $pay_type
 * @property string $order_no
 *
 */
class LsCash extends \yii\db\ActiveRecord
{

    public static $status = [
        '待审核',
        '待打款',
        '已打款',
        '无效'
    ];
    public static $type = [
        '微信线下支付',
        '支付宝支付',
        '银行卡支付',
        '余额支付',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ls_player_cash}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'store_id', 'pay_time'], 'required'],
            [['user_id', 'store_id','status', 'is_delete', 'add_time', 'pay_time', 'type', 'pay_type'], 'integer'],
            [['price'], 'number'],
            [[ 'name', 'order_no'], 'string', 'max' => 255],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'store_id' => 'Store ID',
            'price' => '提现金额',
            'status' => '申请状态 0--申请中 1--确认申请 2--已打款 3--驳回',
            'is_delete' => 'Is Delete',
            'add_time' => 'Add_time',
            'pay_time' => '付款',
            'type' => '支付方式 0--微信支付  1--支付宝',
            'pay_type' => '打款方式 0--之前未统计的 1--微信自动打款 2--手动打款',
            'order_no' => '微信自动打款订单号',

        ];
    }

    public function beforeSave($insert)
    {
        $this->name = \yii\helpers\Html::encode($this->name);

        return parent::beforeSave($insert);
    }
}
