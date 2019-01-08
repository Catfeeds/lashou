<?php
/**
 * Created by PhpStorm.
 * User: wooran
 * Date: 2018/6/29
 * Time: 下午1:54
 */

namespace app\modules\api\controllers;


use app\models\Order;
use app\models\ShareDetailed;
use app\models\User;
use app\extensions\PinterOrder;
use app\extensions\SendMail;
use app\extensions\Sms;
use app\models\FormId;
use app\models\Goods;
use app\models\OrderDetail;
use app\models\OrderMessage;
use app\models\PrinterSetting;
use app\models\Setting;
use app\models\WechatTemplateMessage;
use app\models\WechatTplMsgSender;
use app\modules\api\models\PayComm;
use app\modules\api\models\PayCommTest;
use yii\helpers\VarDumper;
use app\models\Store;
use app\models\OrderWarn;
use app\models\PtOrder;
use app\models\PtOrderDetail;
use app\models\PtGoods;
use app\models\PtGoodsDetail;
use app\models\ReOrder;
use app\models\UserAccountLog;
use app\models\WechatApp;
use luweiss\wechat\DataTransform;
use luweiss\wechat\Wechat;
use app\models\Share;

class TestController extends Controller
{
    public function actionParams(){
        $this->renderJson([
            'data' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/pay-notify.php',
            'ip' => 14,
        ]);
    }

    public function actionPaydata(){
        $res = $this->unifiedOrder("测试商品");
        if (isset($res['code']) && $res['code'] == 1) {
            return $res;
        }

        $pay_data = [
            'appId' => $this->wechat->appId,
            'timeStamp' => '' . time(),
            'nonceStr' => md5(uniqid()),
            'package' => 'prepay_id=' . $res['prepay_id'],
            'signType' => 'MD5',
        ];
        $pay_data['paySign'] = $this->wechat->pay->makeSign($pay_data);
        $this->renderJson([
            'code' => 0,
            'msg' => 'success',
            'data' => (object)$pay_data,
            'res' => $res,
            'body' => "测试商品",
        ]);
    }

    private function unifiedOrder($goods_names)
    {
        $res = $this->wechat->pay->unifiedOrder([
            'body' => $goods_names,
            'out_trade_no' => time() . rand(1000,9999),
            'total_fee' => 1,
            'notify_url' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/pay-notify.php',
            'trade_type' => 'JSAPI',
            'openid' => "orHh35EqGNDBLR1HpqvEMj_S9gy0",
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
                'notify' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/pay-notify.php'
            ];
        }
        $res = array_merge($res, ['notify' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/pay-notify.php']);
        return $res;
    }

    //充值订单回调
    //12381 12383
    public function actionPayReOrder(){
        $store = Store::findOne(1);
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

        $re_orders = ReOrder::find()
            ->where(['>=', 'id', 12366])//12366
            ->andWhere(['<=', 'id', 12415])//12415
            ->all();

        foreach ($re_orders as $order){
            $order_query = $wechat->pay->orderQuery($order->order_no);
            //print_r($order_query);
            if(isset($order_query['result_code']) && $order_query['result_code'] == 'SUCCESS' && isset($order_query['trade_state']) && $order_query['trade_state'] == "SUCCESS"){
                echo "商户号查询结果：充值单号 " . $order->order_no . " 支付成功 " . $order_query['trade_state_desc'] . "\r\n";

                if($order->is_pay == 1){
                    echo "处理结果：无需处理 订单已付款 \r\n";
                }else{
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
                                    $mod_ShareDetailed->price = 50;
                                    $parent->total_price += 50;
                                    $parent->price += 50;
                                }

                                if($parent['is_distributor'] == 1 && $parent['level'] == 2){
                                    $mod_ShareDetailed->price = 80;
                                    $parent->total_price += 80;
                                    $parent->price += 80;
                                }
                                //file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/2.txt','11111');
                                $fens1 = 0; $fens2 = 0;

                                //计算全部一代
                                $parent1Member = User::find()->where('`level` >= 1 AND `parent_id` = '.$user->parent_id)->asArray()->all();
                                $fens1 = count($parent1Member);

                                foreach ($parent1Member as $k => $v){
                                    $parentMember = User::find()->where(['parent_id'=>$v->id])->asArray()->all();
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
                                echo "已处理 298充值业务\r\n";
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
                        echo "处理结果：成功\r\n";
                    } else {
                        echo "处理结果：失败 订单信息保存失败 \r\n";
                    }
                }

            }else{
                echo "商户号查询结果：充值单号 " . $order->order_no . " 支付失败 " . $order_query['trade_state_desc'] . $order_query['err_code_des'] . "\r\n";
            }
            echo "============================\r\n";
        }
        exit("充值通知异常订单处理完成\r\n");
        print_r($order_query);exit();

    }

    //普通订单支付
    public function actionPayOrder()
    {
        $res = [];
        $res['out_trade_no'] = trim($_REQUEST['order_sn']);
        $res['result_code'] = 'SUCCESS';

        $store = Store::findOne(1);
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

        $order_query = $wechat->pay->orderQuery($res['out_trade_no']);

        if(isset($order_query['result_code'])
            && $order_query['result_code'] == 'SUCCESS'
            && isset($order_query['trade_state'])
            && $order_query['trade_state'] == "SUCCESS"){
            echo "商户号查询已支付\r\n";
        }else{
            exit("商户号查询未支付 退出执行\r\n");
        }

        //print_r($order_query);exit();

        //$res
        file_put_contents("pay_notify.txt", date("y-m-d H:i:s") . " " . json_encode($res), FILE_APPEND);
        if ($res['result_code'] != 'SUCCESS' && $res['return_code'] != 'SUCCESS')
            return;


        $order = Order::findOne([
            'order_no' => $res['out_trade_no'],
        ]);
        if (!$order){
           return $this->ptOrderNotify($res);
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
            echo '支付成功';
            return;
        } else {
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

    public function actionTest(){
        $type = intval($_REQUEST['type']);
        $from = trim($_REQUEST['from']);

        $time = date("Y-m-d H:i:s");

        \Yii::$app->db->createCommand()->insert("hjmallind_test", [
            'type' => $type,
            'f' => $from,
            'add_time' => $time,
        ])->execute();

        $this->renderJson([
            'data' => 'ok',
            'date' => $time
        ]);
    }

    public function actionCui(){
        $query = Order::find()->alias('o')
            ->leftJoin(User::tableName() . ' as u', 'u.id = o.partner_id')
            ->where([
                'o.is_send' => 0,
                'o.is_pay' => 1,
                'o.is_delete' => 0,
                'o.is_cancel' => 0,
                'o.apply_delete' => 0,
            ])
            ->andWhere([">", 'o.partner_id', 0])
            ->select('o.order_no, o.user_id, u.mobile')
            ->asArray()
            ->all();
        $query[] = [
            'mobile' => "15868928372",
        ];
        foreach ($query as $item){
            $mobile = $item['mobile'];
            $str = '您好！您有一个下级订单，请到"我-这页面-合伙人-发货中心", 今天务必将货发出,并且在发货中心 点发货。谢谢！';
            $str = preg_replace('# #', '', $str);
            $str = mb_convert_encoding($str, 'GBK', 'UTF-8');
            $content = str_replace('charset=utf-8','',$str);
            $url = 'http://202.91.244.252/qd/SMSSendYD?usr=7426&pwd=DYkk@1595z&mobile='.$mobile.'&sms='.$content.'&extdsrcid=';
            file_get_contents($url);
        }


    }

    public function actionFanli(){
        $order = Order::findOne([
            'order_no' => '20180617230723761572'
        ]);
        //20180620105652911011，
        //20180623131447731951
        //20180618170318522356

        $log = [];

        $setting = Setting::findOne(['store_id' => $order->store_id]);
        $log['setting'] = [
            'setting' => $setting,
            'setting_level' => $setting->level
        ];
        if (!$setting || $setting->level == 0)
            return;
        $user = User::findOne($order->user_id);//订单本人
        $log['user'] = $user;
        if (!$user)
            return;
        $order->parent_id = $user->parent_id;
        $parent = User::findOne($user->parent_id);//上级
        if ($parent->parent_id) {
            $order->parent_id_1 = $parent->parent_id;
            $parent_1 = User::findOne($parent->parent_id);//上上级
            if ($parent_1->parent_id) {
                $order->parent_id_2 = $parent_1->parent_id;
            } else {
                $order->parent_id_2 = -1;
            }
        } else {
            $order->parent_id_1 = -1;
            $order->parent_id_2 = -1;
        }
        $order_total = doubleval($order->total_price - $order->express_price);
        $pay_price = doubleval($order->pay_price - $order->express_price);

        $order_detail_list = OrderDetail::find()->alias('od')->leftJoin(['g' => Goods::tableName()], 'od.goods_id=g.id')
            ->where(['od.is_delete' => 0, 'od.order_id' => $order->id])
            ->asArray()
            ->select(['od.*', 'g.share_commission_first','g.share_commission_second','g.share_commission_third','g.rebate'])
            ->all();
        $share_commission_money_first = 0;//一级分销总佣金
        $share_commission_money_second = 0;//二级分销总佣金
        $share_commission_money_third = 0;//三级分销总佣金
        $rebate = 0;//自购返利
        foreach ($order_detail_list as $item) {
            $item_price = doubleval($item['total_price']);
            if ($item['individual_share'] == 1) {
                $rate_first = doubleval($item['share_commission_first']);
                $rate_second = doubleval($item['share_commission_second']);
                $rate_third = doubleval($item['share_commission_third']);
                $rate_rebate = doubleval($item['rebate']);
                if ($item['share_type'] == 1) {
                    $share_commission_money_first += $rate_first * $item['num'];
                    $share_commission_money_second += $rate_second * $item['num'];
                    $share_commission_money_third += $rate_third * $item['num'];
                    $rebate += $rate_rebate * $item['num'];
                    // $pt_amount += $rate_rebate * $item['num'];
                } else {
                    $share_commission_money_first += $item_price * $rate_first / 100;
                    $share_commission_money_second += $item_price * $rate_second / 100;
                    $share_commission_money_third += $item_price * $rate_third / 100;
                    $rebate += $item_price * $rate_rebate / 100;
                    //$pt_amount  += $item_price * $rate_rebate / 100;
                }
            } else {
                $rate_first = doubleval($setting->first);
                $rate_second = doubleval($setting->second);
                $rate_third = doubleval($setting->third);
                $rate_rebate = doubleval($setting->rebate);
                if ($setting->price_type == 1) {
                    $share_commission_money_first += $rate_first * $item['num'];
                    $share_commission_money_second += $rate_second * $item['num'];
                    $share_commission_money_third += $rate_third * $item['num'];
                    $rebate += $rate_rebate * $item['num'];
                    // $pt_amount += $rate_rebate * $item['num'];

                } else {
                    $share_commission_money_first += $item_price * $rate_first / 100;
                    $share_commission_money_second += $item_price * $rate_second / 100;
                    $share_commission_money_third += $item_price * $rate_third / 100;
                    $rebate += $item_price * $rate_rebate / 100;
                    // $pt_amount += $item_price * $rate_rebate / 100;
                }
            }
        }
        //下单用户不是分销商，则不参与自购返利
        if ($user->is_distributor == 0) {
            $rebate = 0;
        }
        if($setting->is_rebate == 0){
            $rebate = 0;
        }


        $order->first_price = $share_commission_money_first < 0.01 ? 0 : $share_commission_money_first;
        $order->second_price = $share_commission_money_second < 0.01 ? 0 : $share_commission_money_second;
        $order->third_price = $share_commission_money_third < 0.01 ? 0 : $share_commission_money_third;

        $log['price'] = [
          'first' => $order->first_price,
          'second' => $order->second_price,
          'third' => $order->third_price,
          'rebate' => $rebate
        ];

        $payComm = new PayCommTest();


        $rebateData = $payComm->autoRebate($order->user_id,$order_total,$rebate,['is_order'=>1,'id'=>$order->id]);
        $rebate =  $rebateData->rebate;
        $order->rebate = $rebate < 0.01 ? 0 : $rebate;
        $log['autoRebate'] = [
            'autoRebate' => $rebateData,
            'rebate' => $rebateData->rebate
        ];

        //print_r($log);exit();

        $user = User::findOne($order->user_id);
        $rebateCopy  = $order_total * $rate_rebate / 100;
        if($user->level > 0)
            $rebateCopy = $order->rebate;
        $log['rebateCopy'] = [
            'order' => $rebateCopy,
            'user' => $order->rebate
        ];

        //分销商收益返利
        $parent1Rebate = 0;
        $parent2Rebate = 0;
        $parent3Rebate = 0;
        $order->parent_id = -1;
        $order->parent_id_1 = -1;
        $order->parent_id_2 = -1;
        $pt_amount = 0;//返利给平台
        // $is_tjpt = 0;
        $member = User::findOne($order->user_id);

        if(!empty($member->parent_id)){


            if($member->level <= 0){

                //如果星级 =  0;
                $parent_id = $this->getAutoParentInfo($member->parent_id);

                // print_r($parent_id."\r\n");

                //找到 有效一级
                if(!empty($parent_id)){
                    $order->parent_id = $parent_id;

                    $parent1Member = User::findOne($parent_id);



                    if(!empty($parent1Member)){

                        if($parent1Member->level >= 1)
                        {
                            $order->parent_id = $parent1Member->id;
                            $parent1Rebate =  $rebateCopy;
                        }




                        $parent2Member = User::findOne($parent1Member->parent_id);

                        if($parent2Member->level == 1){
                            $order->parent_id_1 = $parent2Member->id;
                            $parent2Rebate =  $rebateCopy * 0.2;
                        }

                        if($parent2Member->level == 2){
                            $order->parent_id_1 = $parent2Member->id;
                            $parent2Rebate =  $rebateCopy * 0.25;
                        }



                        //找到 有效二级
                        $parent3Member = User::findOne($parent2Member->parent_id);


                        if($parent3Member->level == 2){
                            $order->parent_id_2 = $parent3Member->id;
                            $parent3Rebate =  $rebateCopy * 0.2;
                        }
                    }else
                        $pt_amount += $rebateCopy;
                }

            }else{


                //获取一级
                $parent1Member = User::findOne($member->parent_id);
                //获取二级
                $parent2Member = User::findOne($parent1Member->parent_id);

                //自己的级别小于一级上级 则
                if( $member->level <= $parent1Member->level ){


                    if($parent1Member->level == 1){
                        $order->parent_id = $parent1Member->id;
                        $parent1Rebate =  $order->rebate * 0.2;
                    }

                    if($parent1Member->level == 2) {
                        $order->parent_id = $parent1Member->id;
                        $parent1Rebate = $order->rebate * 0.25;
                    }

                }

                //自己的级别小于二级上级 则
                if($member->level <= $parent2Member->level){
                    if($parent2Member->level == 2 ) {
                        $order->parent_id_1 = $parent2Member->id;
                        $parent2Rebate = $order->rebate * 0.2;
                    }

                }


            }


            $order->first_price = doubleval($parent1Rebate);
            //存在判断 是否取得佣金
            if($rebateData->is_auto == 1){

                if(!empty($order->first_price)){

                    $shareParentInfo =  ShareDetailed::findOne(['from_id'=>$order->user_id,'status'=>0]);

                    if(!empty($shareParentInfo)) {
                        // $is_tjpt = 1;
                        if ($shareParentInfo->price == 50)
                            $pt_amount += $rebateData->autoRebate * 0.1678;
                        elseif ($shareParentInfo->price == 80)
                            $pt_amount += $rebateData->autoRebate * 0.2685;
                    }

                }




            }







            $order->second_price = doubleval($parent2Rebate);
            $order->third_price = doubleval($parent3Rebate);
        }else
            $pt_amount += $rebateCopy;



        $partner1Member = User::findOne($order->parent_id);
        $partner2Member = User::findOne($order->parent_id_1);
        //$partner3Member = User::findOne($parent_id_3);
        if($member->is_partner == 1){
            $order->first_price = 0;
            $order->second_price = 0;
            $order->third_price = 0;
        }elseif(!empty($partner1Member) && $partner1Member->is_partner == 1){
            $order->second_price = 0;
            $order->third_price = 0;
        }elseif(!empty($partner2Member) && $partner2Member->is_partner == 1)
            $order->third_price = 0;
        /*
        if($order->pt_amount  == 100 && $is_tjpt == 1)
            $order->pt_amount = 50;
        if($order->pt_amount  == 160 && $is_tjpt == 1)
            $order->pt_amount = 80;*/

        $goods_list = OrderDetail::find()->where(['order_id'=>$order->id])->all();
        $order->pt_amount = $pt_amount;
        foreach ($goods_list as $g_k => $g_v) {
            if($g_v->goods_id == 199 && !LsSsds::getIsSsdsV2()) {

                $order->first_price = 0;
                $order->pt_amount = 0;
                $order->second_price = 0;
                $order->third_price = 0;
                $order->rebate = 0;
                $order->parent_id = -1;
                $order->parent_id_1 = -1;
                $order->parent_id_2 = -1;
                $order->partner_id = 1;
                $order->partner_grab_time = 1;
            }
        }

        $log['order'] = json_decode(json_encode($order), true);

        print_r($log);

        //$order->save();
    }



    public function actionChoujiang(){
        /*for($i = 0; $i < 20000; $i++){
            $code = rand(100, 999) . uniqid();

            \Yii::$app->db->createCommand()->insert("hjmallind_choujiang", [
                'code' => $code,
            ])->execute();
        }
        echo "success";*/

        set_time_limit(0);
        define("APPID", "wxc836c1f6659cdca2");
        define("APPSECRET", "039a80ef430ff1da2694458c3046dedb");

        //echo filesize("ls//10022_8535b63a0d17d608.png");

        $page = empty($_REQUEST['page']) ? 1 : intval($_REQUEST['page']);
        $page_size = empty($_REQUEST['page_size']) ? 10 : intval($_REQUEST['page_size']);
        $start = ($page - 1) * $page_size;

        $token_res = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . APPID . "&secret=" . APPSECRET);
        $token_arr = json_decode($token_res, true);
        $token = $token_arr['access_token'];

        $choujiang_list = \Yii::$app->db->createCommand('SELECT * FROM hjmallind_choujiang order by id asc limit ' . $start . ', ' . $page_size)
            ->queryAll();
        foreach ($choujiang_list as $choujiang){
            $code = $choujiang['code'];
            //echo $i . "-" .$code ."<br>";

            if(filesize("ls//" . $choujiang['id'] . '_' . $code . ".png") > 5000){
                continue;
            }

            $data = [
                'page' => 'pages/choujiang/choujiang',
                'scene' => 'code=' . $code
            ];
            $data = json_encode($data);
            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $token;
            $qrcode_res = httpPost($url, $data);

            file_put_contents("ls//" . $choujiang['id'] . '_' . $code . ".png", $qrcode_res);
        }
        exit("success");
    }

    public  function actionLog(){
        $timestamp = $_REQUEST['t'];
        $log = $_REQUEST['log'];

        \Yii::$app->db->createCommand()
            ->insert('test_log', [
                'timestamp' => $timestamp,
                'log' => $log,
                'add_time' => date('Y-m-d H:i:s')
            ])
            ->execute();
    }
}

function httpPost($url, $post_date){
    $curl = curl_init();
    //echo 'curl' . $curl . 'curl';
    //print_r(array('url'=>$url, 'post_date'=>$post_date));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/html;charset=utf-8'));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true); // enable posting
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_date); // post
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
    curl_setopt($curl, CURLOPT_URL, $url);
    $res = curl_exec($curl);
    curl_close($curl);


    return $res;
}

function get_http_array($url,$post_data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $output = curl_exec($ch);
    curl_close($ch);
    $out = json_decode($output);
    return $out;
}