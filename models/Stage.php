<?php

namespace app\models;

use Yii;
use Codeception\PHPUnit\ResultPrinter\HTML;

/**
 * This is the model class for table "{{%ls_stage}}".
 *
 * @property integer $id
 * @property integer $stage

 */
class Stage extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ls_stage}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            [['user_id', 'store_id', 'pay_time'], 'required'],
//            [['user_id', 'store_id','is_partner' ,'status', 'is_delete', 'addtime', 'pay_time', 'type', 'pay_type','hongbao'], 'integer'],
//            [['price'], 'number'],
//            [['mobile', 'name', 'order_no'], 'string', 'max' => 255],
//            [['bank_name'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
//        return [
//            'id' => 'ID',
//            'user_id' => 'User ID',
//            'store_id' => 'Store ID',
//            'price' => '提现金额',
//            'status' => '申请状态 0--申请中 1--确认申请 2--已打款 3--驳回',
//            'is_delete' => 'Is Delete',
//            'addtime' => 'Addtime',
//            'pay_time' => '付款',
//            'type' => '支付方式 0--微信支付  1--支付宝',
//            'mobile' => '支付宝账号',
//            'is_partner'=>'1 是否合伙人提现 0不是',
//            'name' => '支付宝姓名',
//            'bank'=>'银行卡选中状态',
//            'bank_name'=>'开户行',
//            'pay_type' => '打款方式 0--之前未统计的 1--微信自动打款 2--手动打款',
//            'order_no' => '微信自动打款订单号',
//            'hongbao' => '是否红包打款',
//        ];
    }

//    public function beforeSave($insert)
//    {
//        $this->name = \yii\helpers\Html::encode($this->name);
//        $this->mobile = \yii\helpers\Html::encode($this->mobile);
//        $this->bank_name = \yii\helpers\Html::encode($this->bank_name);
//        return parent::beforeSave($insert);
//    }
}
