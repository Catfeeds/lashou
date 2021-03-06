<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/19
 * Time: 9:57
 */

namespace app\controllers;


use app\extensions\printerPtOrder;
use app\extensions\SendMail;
use app\extensions\Sms;
use app\models\CardSendForm;
use app\models\FormId;
use app\models\MsOrder;
use app\models\MsWechatTplMsgSender;
use app\models\Option;
use app\models\Order;
use app\models\OrderMessage;
use app\models\OrderWarn;
use app\models\OrderUnion;
use app\models\PtGoods;
use app\models\PtNoticeSender;
use app\models\PtOrder;
use app\models\PtOrderDetail;
use app\models\PrinterSetting;
use app\models\Setting;
use app\models\Share;
use app\models\Ssdsplayer;
use app\models\Store;
use app\models\User;
use app\models\OrderDetail;
use app\models\Goods;
use app\models\WechatApp;
use app\models\WechatTplMsgSender;
use app\models\YyOrder;
use app\models\YyWechatTplMsgSender;
use app\modules\api\models\CouponPaySendForm;
use app\modules\api\models\PayComm;
use luweiss\wechat\DataTransform;
use luweiss\wechat\Wechat;
use app\extensions\PinterOrder;
use app\models\MiaoshaGoods;
use app\models\MsGoods;
use app\models\YyGoods;
use app\models\PtGoodsDetail;

class PayNotifyController extends Controller
{
    public $enableCsrfValidation = false;
    public $wechat;

    public function actionIndex()
    {
        file_put_contents("pay_notify_index.txt", date("y-m-d H:i:s"), FILE_APPEND);
        $xml = file_get_contents("php://input");
        $res = DataTransform::xmlToArray($xml);
        if ($res && !empty($res['out_trade_no'])) {//微信支付回调
            $this->wechatPayNotify($res);
        }
    }

    private function wechatPayNotify($res)
    {
        file_put_contents("pay_notify.txt", date("y-m-d H:i:s") . " " . json_encode($res), FILE_APPEND);
        if ($res['result_code'] != 'SUCCESS' && $res['return_code'] != 'SUCCESS')
            return;

        $orderNoHead = substr($res['out_trade_no'], 0, 1);

        switch ($orderNoHead) {
            case 'Y':
                // 预约订单回掉
                return $this->YyOrderNotify($res);
                break;
            case 'M':
                // 秒杀订单回掉
                return $this->MsOrderNotify($res);
                break;
            case 'U':
                //合并支付的订单
                return $this->unionOrderNotify($res);
                break;
        }

        $order = Order::findOne([
            'order_no' => $res['out_trade_no'],
        ]);
        if (!$order){
           return $this->ptOrderNotify($res);
        }

        $store = Store::findOne($order->store_id);
        if (!$store)
            return;
        $wechat_app = WechatApp::findOne($store->wechat_app_id);
        if (!$wechat_app)
            return;
        $wechat = new Wechat([
            'appId' => $wechat_app->app_id,
            'appSecret' => $wechat_app->app_secret,
            'mchId' => $wechat_app->mch_id,
            'apiKey' => $wechat_app->key,
            'cachePath' => \Yii::$app->runtimePath . '/cache',
        ]);

        \Yii::$app->controller->wechat = $wechat;
        $new_sign = $wechat->pay->makeSign($res);
        if ($new_sign != $res['sign']) {
            echo "Sign 错误";
            return;
        }
        if ($order->is_pay == 1) {
            echo "订单已支付";
            return;
        }


        //支付处理
        $payComm = new PayComm();
        $payComm->payGo($order->id,$order->user_id);


        $order->is_pay = 1;
        $order->pay_time = time();
        $order->pay_type = 1;
        if ($order->save()) {
            //支付完成之后，相关的操作
            $form = new OrderWarn();
            $form->order_id = $order->id;
            $form->order_type = 0;
            $form->notify();
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return;
        } else {
            echo "支付失败";
            return;
        }
    }

    /**
     * 购买成功首页提示 
     */
    private function buyData($order_no,$store_id,$type){
        switch($type){
            case 1:
                $order = Order::find()->select(['u.nickname','g.name','u.avatar_url','od.goods_id'])->alias('c')
                    ->where('c.order_no=:order',[':order'=>$order_no])
                    ->andwhere('c.store_id=:store_id',[':store_id'=>$store_id])
                    ->leftJoin(['u' => User::tableName()],'u.id=c.user_id')
                    ->leftJoin(['od'=>OrderDetail::tableName()],'od.order_id=c.id')
                    ->leftJoin(['g' => Goods::tableName()],'od.goods_id = g.id')
                    ->asArray()->one();
                break;
            case 2:
                $order = YyOrder::find()->select(['u.nickname','g.name','u.avatar_url','g.id as goods_id'])->alias('c')
                    ->where('c.order_no=:order',[':order'=>$order_no])
                    ->andwhere('c.store_id=:store_id',[':store_id'=>$store_id])
                    ->leftJoin(['u' => User::tableName()],'u.id=c.user_id')
                    ->leftJoin(['g' => YyGoods::tableName()],'c.goods_id = g.id')
                    ->asArray()->one();
                break;
            case 3:
                $order = MsOrder::find()->select(['u.nickname','g.name','u.avatar_url','c.goods_id'])->alias('c')
                    ->where('c.order_no=:order',[':order'=>$order_no])
                    ->andwhere('c.store_id=:store_id',[':store_id'=>$store_id])
                    ->leftJoin(['u' => User::tableName()],'u.id=c.user_id')
                    ->leftJoin(['g' => MsGoods::tableName()],'c.goods_id = g.id')
                    ->asArray()->one();

                $goods = MiaoshaGoods::find()->select(['*'])->where(['open_date'=>date("Y-m-d"),'is_delete'=>0,'goods_id'=>$order['goods_id']])
                    ->andwhere('store_id=:store_id',[':store_id'=>$store_id])
                    ->andWhere(['>','start_time', date("H")])
                    ->asArray()->one();
                $order['goods_id'] = $goods['id'];
                break;
            case 4:
                $order = PtOrder::find()->select(['u.nickname','g.name','u.avatar_url','od.goods_id'])->alias('c')
                    ->where('c.order_no=:order',[':order'=>$order_no])
                    ->andwhere('c.store_id=:store_id',[':store_id'=>$store_id])
                    ->leftJoin(['u' => User::tableName()],'u.id=c.user_id')
                    ->leftJoin(['od'=>PtOrderDetail::tableName()],'od.order_id=c.id')
                    ->leftJoin(['g' => PtGoods::tableName()],'od.goods_id = g.id')
                    ->asArray()->one();
                break;
                default:
                    return;
        }

        $key = "buy_data";
        $data = (object)null;
        $data->type = $type;
        $data->store_id = $store_id;
        $data->order_no = $order_no;
        $data->user = $order['nickname'];
        $data->goods = $order['goods_id'];
        $data->address = $order['name'];
        $data->avatar_url = $order['avatar_url'];
        $data->time = time();
        $new = json_encode($data);
        $cache = \Yii::$app->cache;
        $cache->set($key,$new,300);
    }

    /**
     * @param $res
     * 秒杀订单回掉
     */
    private function MsOrderNotify($res){
        $order = MsOrder::findOne([
            'order_no' => $res['out_trade_no'],
        ]);
        if (!$order){
            return;
        }
        $store = Store::findOne($order->store_id);
        if (!$store)
            return;
        $wechat_app = WechatApp::findOne($store->wechat_app_id);
        if (!$wechat_app)
            return;
        $wechat = new Wechat([
            'appId' => $wechat_app->app_id,
            'appSecret' => $wechat_app->app_secret,
            'mchId' => $wechat_app->mch_id,
            'apiKey' => $wechat_app->key,
            'cachePath' => \Yii::$app->runtimePath . '/cache',
        ]);
        $new_sign = $wechat->pay->makeSign($res);
        if ($new_sign != $res['sign']) {
            echo "Sign 错误";
            return;
        }
        if ($order->is_pay == 1) {
            echo "订单已支付";
            return;
        }
        $order->is_pay = 1;
        $order->pay_time = time();
        $order->pay_type = 1;
        if ($order->save()) {
            //支付完成之后，相关的操作
            $form = new OrderWarn();
            $form->order_id = $order->id;
            $form->order_type = 1;
            $form->notify();
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return;
        } else {
            echo "支付失败";
            return;
        }
    }

    /**
     * @param $res
     * 预约订单回掉
     */
    private function YyOrderNotify($res){
        $order = YyOrder::findOne([
            'order_no' => $res['out_trade_no'],
        ]);
        if (!$order){
            return;
        }
        $store = Store::findOne($order->store_id);
        if (!$store)
            return;
        $wechat_app = WechatApp::findOne($store->wechat_app_id);
        if (!$wechat_app)
            return;
        $wechat = new Wechat([
            'appId' => $wechat_app->app_id,
            'appSecret' => $wechat_app->app_secret,
            'mchId' => $wechat_app->mch_id,
            'apiKey' => $wechat_app->key,
            'cachePath' => \Yii::$app->runtimePath . '/cache',
        ]);
        $new_sign = $wechat->pay->makeSign($res);
        if ($new_sign != $res['sign']) {
            echo "Sign 错误";
            return;
        }
        if ($order->is_pay == 1) {
            echo "订单已支付";
            return;
        }


        $order->is_pay = 1;
        $order->pay_time = time();
        $order->pay_type = 1;

        if ($order->save()) {
            //支付完成之后，相关的操作
            $form = new OrderWarn();
            $form->order_id = $order->id;
            $form->order_type = 3;
            $form->notify();
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return;
        }else{
            echo "支付失败";
            return;
        }
    }

    /**
     * @param $order
     * 拼团订单回调
     */
    private function ptOrderNotify($res)
    {
        $order = PtOrder::findOne([
            'order_no' => $res['out_trade_no'],
        ]);
        if (!$order){
            return;
        }
        $store = Store::findOne($order->store_id);
        if (!$store)
            return;
        $wechat_app = WechatApp::findOne($store->wechat_app_id);
        if (!$wechat_app)
            return;
        $wechat = new Wechat([
            'appId' => $wechat_app->app_id,
            'appSecret' => $wechat_app->app_secret,
            'mchId' => $wechat_app->mch_id,
            'apiKey' => $wechat_app->key,
            'cachePath' => \Yii::$app->runtimePath . '/cache',
        ]);
        $new_sign = $wechat->pay->makeSign($res);
        if ($new_sign != $res['sign']) {
            echo "Sign 错误";
            return;
        }
        if ($order->is_pay == 1) {
            echo "订单已支付";
            return;
        }

//        if ($order->getSurplusGruop())

        $order->is_pay = 1;
        $order->pay_time = time();
        $order->pay_type = 1;
        $order->status = 2;
        $order_detail = PtOrderDetail::find()
            ->andWhere(['order_id'=>$order->id,'is_delete'=>0])
            ->one();
        $goods = PtGoods::findOne(['id'=>$order_detail->goods_id]);

        if ($order->parent_id ==0 && $order->is_group==1){
            // 团购-团长
            $pid = $order->id;
            if($order->class_group){
                $group = PtGoodsDetail::findOne(['id'=>$order->class_group,'store_id'=>$order->store_id]);
                $order->limit_time = (time() + (int)$group->group_time*3600); 
            }else{
                $order->limit_time = (time() + (int)$goods->grouptime*3600);  
            }
        }elseif($order->is_group==1){
            // 团购-参团
            $pid = $order->parent_id;
            $parentOrder = PtOrder::findOne([
                'id' => $pid,
                'is_delete' => 0,
                'store_id' => $order->store_id,
                'status' => 3,
                'is_success' => 1,
            ]);
            if($parentOrder){
                // 该订单参与的团已经成团
                $order->limit_time = time();
                $order->parent_id = 0;
            }
        }else{
            // 单独购买
            $order->status = 3;
            $order->is_success = 1;
            $order->success_time = time();
        }

        if ($order->save()) {
            //支付完成之后，相关的操作
            $form = new OrderWarn();
            $form->order_id = $order->id;
            $form->order_type = 2;
            $form->notify();
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return;
        }else{
            echo "支付失败";
            return;
        }

    }

    /**
     * @deprecated 已弃用
     * 设置佣金
     * @param Order $order
     */
    private function setReturnData($order)
    {
        $setting = Setting::findOne(['store_id' => $order->store_id]);
        if (!$setting || $setting->level == 0)
            return;
        $user = User::findOne($order->user_id);
        if (!$user)
            return;
        $order->parent_id = $user->parent_id;

        $first_price = $setting->first * $order->pay_price / 100;
        $second_price = $setting->second * $order->pay_price / 100;
        $third_price = $setting->third * $order->pay_price / 100;

        $order->first_price = $first_price < 0.01 ? 0 : $first_price;
        $order->second_price = $second_price < 0.01 ? 0 : $second_price;
        $order->third_price = $third_price < 0.01 ? 0 : $third_price;
        $order->save();
    }

    /**
     * 支付成功送优惠券
     */
    private function paySendCoupon($store_id, $user_id)
    {
        $form = new CouponPaySendForm();
        $form->store_id = $store_id;
        $form->user_id = $user_id;
        $form->save();
    }


    /**
     * 消费满指定金额自动成为分销商
     * @param $user_id integer 用户id
     */
    private function autoBecomeShare($user_id, $store_id)
    {
        $auto_share_val = floatval(Option::get('auto_share_val', $store_id, 'share', 0));
        if ($auto_share_val == 0)
            return;
//        $is_share = Share::find()->where(['user_id' => $user_id, 'is_delete' => 0 , 'status'=>1])->exists();
//        if ($is_share)
//            return;
        $share = Share::findOne(['user_id'=>$user_id,'is_delete'=>0 , 'store_id'=>$store_id]);
        if($share && $share->status == 1){
            return ;
        }
        $consumption_sum = Order::find()->where(['user_id' => $user_id, 'is_delete' => 0, 'is_pay' => 1])->sum('pay_price');
        $consumption_sum = floatval(($consumption_sum ? $consumption_sum : 0));
        if ($consumption_sum < $auto_share_val)
            return;
        if(!$share || $share->status == 2){
            $share = new Share();
            $share->user_id = $user_id;
            $share->mobile = '';
            $share->name = '';
            $share->is_delete = 0;
            $share->store_id = $store_id;
        }
        $share->status = 1;
        $share->addtime = time();
        $share->save();

        $user = User::findOne($user_id);
        $user->time = time();
        $user->is_distributor = 1;
        $user->save();
    }

    /**
     * 支付成功送卡券
     */
    private function paySendCard($store_id, $user_id,$order_id)
    {
        $form = new CardSendForm();
        $form->store_id = $store_id;
        $form->user_id = $user_id;
        $form->order_id = $order_id;
        $form->save();
    }

    /**
     * 合并订单支付回调
     */
    public function unionOrderNotify($res)
    {
        $order_union = OrderUnion::findOne([
            'order_no' => $res['out_trade_no'],
        ]);
        $store = Store::findOne($order_union->store_id);
        if (!$store)
            return;
        $wechat_app = WechatApp::findOne($store->wechat_app_id);
        if (!$wechat_app)
            return;
        $wechat = new Wechat([
            'appId' => $wechat_app->app_id,
            'appSecret' => $wechat_app->app_secret,
            'mchId' => $wechat_app->mch_id,
            'apiKey' => $wechat_app->key,
            'cachePath' => \Yii::$app->runtimePath . '/cache',
        ]);
        $new_sign = $wechat->pay->makeSign($res);
        if ($new_sign != $res['sign']) {
            echo "Sign 错误";
            return;
        }
        if ($order_union->is_pay == 1) {
            echo "订单已支付";
            return;
        }
        $order_id_list = json_decode($order_union->order_id_list, true);
        if (!$order_id_list) {
            echo "订单数据错误";
            return;
        }
        $form_id = FormId::find()->where(['order_no' => $order_union->order_no])->orderBy('id DESC')->one();
        foreach ($order_id_list as $order_id) {
            $order = Order::findOne([
                'id' => $order_id,
                'is_pay' => 0,
            ]);
            if (!$order)
                continue;
            $order->is_pay = 1;
            $order->pay_time = time();
            $order->pay_type = 1;

            //支付处理
            $payComm = new PayComm();
            $payComm->payGo($order->id,$order->user_id);

            if ($order->save()) {
                try {
                    $this->buyData($order->order_no, $order->store_id, 1);
                    $this->paySendCoupon($order->store_id, $order->user_id);
                    $this->autoBecomeShare($order->user_id, $order->store_id);
                    $wechat_tpl_meg_sender = new WechatTplMsgSender($order->store_id, $order->id, $wechat);
                    $wechat_tpl_meg_sender->form_id = $form_id;
                    $wechat_tpl_meg_sender->payMsg();
                    Sms::send($order->store_id, $order->order_no);
                    OrderMessage::set($order->id, $order->store_id);
                    $this->paySendCard($order->store_id, $order->user_id, $order->id);
                    $printer_setting = PrinterSetting::findOne(['store_id' => $order->store_id, 'is_delete' => 0]);
                    $type = json_decode($printer_setting->type, true);
                    if ($type['pay'] && $type['pay'] == 1) {
                        $printer_order = new PinterOrder($order->store_id, $order->id);
                        $res = $printer_order->print_order();
                    }
                    $mail = new SendMail($order->store_id, $order->id, 0);
                    $mail->send();
                } catch (\Exception $e) {
                    \Yii::warning($e->getMessage());
                }
            }
        }
        $order_union->is_pay = 1;
        $order_union->save();
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        return;
    }

}