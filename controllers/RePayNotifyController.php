<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/15
 * Time: 14:53
 */

namespace app\controllers;


use app\models\ReOrder;
use app\models\Share;
use app\models\Store;
use app\models\User;
use app\models\UserAccountLog;

use app\models\ShareDetailed;

use app\models\WechatApp;
use app\modules\api\models\PayComm;
use luweiss\wechat\DataTransform;
use luweiss\wechat\Wechat;
use app\models\LevelOrder;

class RePayNotifyController extends Controller
{
    public $enableCsrfValidation = false;


    public function actionIndex()
    {
        $xml = file_get_contents("php://input");
        $res = DataTransform::xmlToArray($xml);
        if ($res && !empty($res['out_trade_no'])) {//微信支付回调
            $this->wechatPayNotify($res);
        }
    }

    private function wechatPayNotify($res)
    {
        if ($res['result_code'] != 'SUCCESS' && $res['return_code'] != 'SUCCESS')
            return;
        $orderNoHead = substr($res['out_trade_no'],0,1);
        if ($orderNoHead == 'L'){
            // 预约订单回掉
            return $this->hyOrderNotify($res);
        }

        $order = ReOrder::findOne(['order_no' => $res['out_trade_no']]);
        $store = Store::findOne($order->store_id);
        if (!$store) {
            return;
        }
        $wechat_app = WechatApp::findOne($store->wechat_app_id);
        if (!$wechat_app) {
            return;
        }
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
            //金额充值

            $user = User::findOne($order->user_id);
            $money = floatval($order->pay_price) + floatval($order->send_price);
/*
            $payComm = new PayComm();
            $payComm->autoToShare($order->user_id);
*/



            $user->money += $money;
            $user->save();

                $user = User::findOne($order->user_id);



             if($order->is_parent == 1){

                 $user->is_distributor = 1;
                 $user->level = 1;

                 $parent = User::findOne($user->parent_id);

                 $mod_ShareDetailed = new ShareDetailed;
                 $mod_ShareDetailed->from_id = $order->user_id;
                 $mod_ShareDetailed->user_id = $user->parent_id;
                 $mod_ShareDetailed->store_id = $order->store_id;
                 $mod_ShareDetailed->remarks = '推荐收益';

                 $share = Share::findOne(['user_id' => $order->user_id, 'store_id' => $order->store_id, 'is_delete' => 0]);

                 $share->addtime = time();
                 $share->status = 1;
                 $share->save();

                 //file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/1.txt','11111');
                 if(!empty($parent)){

                    if($parent['is_distributor'] == 1 && $parent['level'] == 1){
                         $mod_ShareDetailed->price = 40;
                         $parent->total_price += 40;
                         $parent->price += 40;
                     }

                     if($parent['is_distributor'] == 1 && $parent['level'] == 2){
                         $mod_ShareDetailed->price = 65;
                         $parent->total_price += 65;
                         $parent->price += 65;
                     }
                     //file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/2.txt','11111');
                     $fens1 = 0; $fens2 = 0;

                     //计算全部一代
                     $parent1Member = User::find()->where('`level` >= 1 AND `parent_id` = '.$user->parent_id)->asArray()->all();
                     $fens1 = count($parent1Member);

                     foreach ($parent1Member as $k => $v){
                         $parentMember = User::find()->where(['parent_id'=>$v['id']])->asArray()->all();
                         $fens2 += count($parentMember);

                     }



                     $fensCount = $fens1 + $fens2;
                    // file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/3-'.$fens1.'-'.$fensCount.'.txt','11111');

                     if($fens1 >= 15 && $fensCount >= 60)
                         $parent->level = 2;

                  //   file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/4-'.$fens1.'-'.$fensCount.'.txt','11111');
                    // file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/4.txt','11111');
                     $parent->save();
                     //file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/5.txt','11111');
                     $mod_ShareDetailed->saveS();

                 }

             }
             //file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/6.txt','11111');
            $user->save();
            $log = new UserAccountLog();
            $log->user_id = $order->user_id;
            $log->price = $money;
            $log->type = 1;
            $log->desc = "余额充值，付款金额：{$order->pay_price}元，赠送金额：{$order->send_price}元。";
            $log->addtime = time();
            $log->order_id = $order->id;
            $log->order_type = 0;
            $log->save();
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return;
        } else {
            echo "支付失败";
            return;
        }
    }
    private function hyOrderNotify($res){
        $order = LevelOrder::findOne([
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
            //会员升级
            $user = User::findOne($order->user_id);
            $user->level = $order->after_level;
            $user->save();
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return;
        } else {
            echo "支付失败";
            return;
        }
    }

}