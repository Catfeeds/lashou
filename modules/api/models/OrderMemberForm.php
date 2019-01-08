<?php
namespace app\modules\api\models;

use app\models\LevelOrder;
use app\models\Level;

use app\extensions\PinterOrder;
use app\extensions\SendMail;
use app\extensions\Sms;
use app\models\FormId;
use app\models\Goods;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderMessage;
use app\models\PrinterSetting;
use app\models\Setting;
use app\models\User;
use app\models\WechatTemplateMessage;
use app\models\WechatTplMsgSender;
use yii\helpers\VarDumper;
/**
 * @property User $user
 * @property ReOrder $order
 */
class OrderMemberForm extends Model
{
    public $store_id;

    public $pay_type;
    public $level_id;

    public $order;
    public $user;
    public $wechat;

    public function rules()
    {
        return [ 
            [['pay_type','level_id'],'number'],
            [['pay_type'],'in','range'=>['WECHAT_PAY']]
        ];
    }

    public function save()
    {
        $this->wechat = $this->getWechat();
        if(!$this->validate()){
            return $this->getModelError();
        }
 
        $id = Level::findone([
            'id' => $this->level_id,
            'is_delete' => 0,
            'store_id' =>$this->store_id
            ])->level;
        $level = Level::find()->select(['id','level','price'])
                    ->where(['store_id'=>$this->store_id,'is_delete'=>0])
                    ->andWhere(['>', 'level', $this->user->level])
                    ->andWhere(['<=', 'level', $id])
                    ->orderBy('level asc')->asArray()->all();
        if(!$level){
            return 1;
        }   
        $order = new LevelOrder();
        $order->store_id = $this->store_id; 
        $order->user_id = $this->user->id;
        $order->current_level = $this->user->level;
        $order->after_level = $id;
        $order->order_no = self::getOrderNo();
        $order->is_pay = 0;
        $order->is_delete = 0;
        $order->addtime = time();  

        $pay_price = 0;
        foreach($level as $v){
            $pay_price +=(float)$v['price'];
        }
        $order->pay_price = $pay_price;
        
        if($order->save()){
            $this->pay_type = 'WECHAT_PAY';
            $this->order = $order;
            if($this->pay_type == 'WECHAT_PAY'){
                $body = "充值";
                $res = $this->unifiedOrder($body);
                if (isset($res['code']) && $res['code'] == 1) {
                    return $res;
                }

                //记录prepay_id发送模板消息用到
                FormId::addFormId([
                    'store_id' => $this->store_id,
                    'user_id' => $this->user->id,
                    'wechat_open_id' => $this->user->wechat_open_id,
                    'form_id' => $res['prepay_id'],
                    'type' => 'prepay_id',
                    'order_no' => $this->order->order_no,
                ]);

                $pay_data = [
                    'appId' => $this->wechat->appId,
                    'timeStamp' => '' . time(),
                    'nonceStr' => md5(uniqid()),
                    'package' => 'prepay_id=' . $res['prepay_id'],
                    'signType' => 'MD5',
                ];
                $pay_data['paySign'] = $this->wechat->pay->makeSign($pay_data);
                return [
                    'code' => 0,
                    'msg' => 'success',
                    'data' => (object)$pay_data, 
                    'res' => $res,
                    'body' => $body,
                ];
            }
        }else{
            return $this->getModelError($order);
        }
    }

    public function getOrderNo()
    {
        $store_id = empty($this->store_id) ? 0 : $this->store_id;
        $order_no = null;
        while (true) {
            $order_no = 'L'.date('YmdHis') . rand(100000, 999999);
            $exist_order_no = LevelOrder::find()->where(['order_no' => $order_no])->exists();
            if (!$exist_order_no)
                break;
        }
        return $order_no;
    }
 
    private function unifiedOrder($body)
    {
        $res = $this->wechat->pay->unifiedOrder([
            'body' => $body,
            'out_trade_no' => $this->order->order_no,
            'total_fee' => $this->order->pay_price * 100,
            'notify_url' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/re-pay-notify.php',
            'trade_type' => 'JSAPI',
            'openid' => $this->user->wechat_open_id,
        ]);
        
        if (!$res)
            return [
                'code' => 1,
                'msg' => '支付失败',
            ];
        if ($res['return_code'] != 'SUCCESS') {
            return [
                'code' => 1,
                'msg' => '支付失败，' . (isset($res['return_msg']) ? $res['return_msg'] : ''),
                'res' => $res,
            ];
        }
        if ($res['result_code'] != 'SUCCESS') {
            if ($res['err_code'] == 'INVALID_REQUEST') {//商户订单号重复
                $this->order->order_no = $this->getOrderNo();
                $this->order->save();
                return $this->unifiedOrder($body);
            } else {
                return [
                    'code' => 1,
                    'msg' => '支付失败，' . (isset($res['err_code_des']) ? $res['err_code_des'] : ''),
                    'res' => $res,
                ];
            }
        }
        return $res;
    }
}