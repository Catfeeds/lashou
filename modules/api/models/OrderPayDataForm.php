<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/18
 * Time: 12:11
 */

namespace app\modules\api\models;


use app\extensions\PinterOrder;
use app\extensions\SendMail;
use app\extensions\Sms;
use app\models\FormId;
use app\models\Goods;
use app\models\LsSsds;
use app\models\Order;
use app\models\OrderDetail;
use app\models\OrderMessage;
use app\models\OrderWarn;
use app\models\OrderUnion;
use app\models\PrinterSetting;
use app\models\Setting;
use app\models\Share;
use app\models\ShareDetailed;
use app\models\Ssdsplayer;
use app\models\User;
use app\models\UserAccountLog;
use app\models\WechatTemplateMessage;
use app\models\WechatTplMsgSender;
use luweiss\wechat\Wechat;
use yii\helpers\VarDumper;

/**
 * @property User $user
 * @property Order $order
 */
class OrderPayDataForm extends Model
{
    public $store_id;
    public $order_id;
    public $order_id_list;
    public $pay_type;
    public $user;
    public $form_id;

    /** @var  Wechat $wechat */
    private $wechat;
    private $order;

    public function rules()
    {
        return [
            [['pay_type',], 'required'],
            [['pay_type'], 'in', 'range' => ['ALIPAY', 'WECHAT_PAY', 'HUODAO_PAY', 'BALANCE_PAY']],
            [['form_id', 'order_id_list'], 'string'],
            [['order_id'], 'integer'],
        ];
    }

    /**
     * @return array
     */
    public function search()
    {
        $this->wechat = $this->getWechat();
        if (!$this->validate())
            return $this->getModelError();
        $this->user->money = doubleval($this->user->money);
        if ($this->order_id_list) {
            $order_id_list = json_decode($this->order_id_list, true);
            if (is_array($order_id_list) && count($order_id_list) == 1) {
                $this->order_id = $order_id_list[0];
                $this->order_id_list = '';
            }
        }
        $is_216 = false;
        if ($this->order_id) {//单个订单付款
            $this->order = Order::findOne([
                'store_id' => $this->store_id,
                'id' => $this->order_id,
            ]);
            if (!$this->order)
                return [
                    'code' => 1,
                    'msg' => '订单不存在',
                ];

            $goods_names = '';
            $goods_list = OrderDetail::find()->alias('od')->leftJoin(['g' => Goods::tableName()], 'g.id=od.goods_id')->where([
                'od.order_id' => $this->order->id,
                'od.is_delete' => 0,
            ])->select('g.name,g.id,num')->asArray()->all();
            foreach ($goods_list as $goods)
                $goods_names .= $goods['name'] . ';';
            foreach ($goods_list as $goods){
                if($goods['id'] == 199 || $goods['id'] == 205){
                    $is_216 = true;
                }
            }
            $goods_names = mb_substr($goods_names, 0, 32, 'utf-8');
            $this->setReturnData($this->order);
            $this->order->order_union_id = 0;
            $this->order->save();
            if ($this->pay_type == 'WECHAT_PAY') {
                $res = $this->unifiedOrder($goods_names);
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
                    'body' => $goods_names,
                    'is_216' => $is_216,
                ];
            }

            if ($this->pay_type == 'BALANCE_PAY' && !LsSsds::getIsSsdsV2()){
                foreach ($goods_list as $goods){
                    if($goods['id'] == 199){
                        return [
                            'code' => 800,
                            'msg' => '该订单不能使用余额支付',
                        ];
                    }
                }
            }
            //货到付款和余额支付数据处理
            if ($this->pay_type == 'HUODAO_PAY' || $this->pay_type == 'BALANCE_PAY') {
                $order = Order::findOne(['id' => $this->order_id, 'store_id' => $this->store_id]);
                //记录prepay_id发送模板消息用到
                FormId::addFormId([
                    'store_id' => $this->store_id,
                    'user_id' => $this->user->id,
                    'wechat_open_id' => $this->user->wechat_open_id,
                    'form_id' => $this->form_id,
                    'type' => 'form_id',
                    'order_no' => $this->order->order_no,
                ]);
                //余额支付  用户余额变动
                if ($this->pay_type == 'BALANCE_PAY') {
                    $user = User::findOne(['id' => $order->user_id]);
                    if ($user->money < $order->pay_price) {
                        return [
                            'code' => 1,
                            'msg' => '支付失败，余额不足'
                        ];
                    }
                    $user->money -= floatval($order->pay_price);
                    $user->save();

                    //支付处理
                    $payComm = new PayComm();
                    $payComm->payGo($order->id,$order->user_id);



                    $order->is_pay = 1;
                    $order->pay_type = 3;
                    $order->pay_time = time();
                    $order->save();
                }
                //支付完成后，相关操作
                $form = new OrderWarn();
                $form->order_id = $order->id;
                $form->order_type = 0;
                $form->notify();
//            //发送短信提醒
//            Sms::send($order->store_id, $order->order_no);
//            //发送后台消息
//            OrderMessage::set($order->id, $order->store_id);
//            //打印订单
//            $printer_setting = PrinterSetting::findOne(['store_id' => $order->store_id, 'is_delete' => 0]);
//            $type = json_decode($printer_setting->type, true);
//            if ($type['pay'] && $type['pay'] == 1) {
//                $printer_order = new PinterOrder($order->store_id, $order->id);
//                $res = $printer_order->print_order();
//            }
//            //发送成功购买提示
//            $this->buyData($order->order_no,$order->store_id,1);
//
//            //发送邮件提醒
//            $mail = new SendMail($order->store_id, $order->id, 0);
//            $mail->send();
                return [
                    'code' => 0,
                    'msg' => 'success',
                    'data' => '',
                    'is_216' => $is_216,
                ];
            }
        } elseif ($this->order_id_list) {//多个订单合并付款
            $order_id_list = json_decode($this->order_id_list, true);
            if (!$order_id_list) {
                return [
                    'code' => 1,
                    'msg' => '数据错误：订单格式不正确。',
                ];
            }
            $order_list = [];
            $total_pay_price = 0;
            foreach ($order_id_list as $order_id) {
                $order = Order::findOne([
                    'store_id' => $this->store_id,
                    'id' => $order_id,
                    'is_delete' => 0,
                ]);
                if (!$order)
                    return [
                        'code' => 1,
                        'msg' => '订单不存在',
                    ];
                if ($order->is_pay == 1) {
                    return [
                        'code' => 1,
                        'msg' => '存在已付款的订单，订单合并支付失败，请到我的订单重新支付。',
                    ];
                }
                $order_list[] = $order;
                $total_pay_price += doubleval($order->pay_price);
              /*  if ($order->mch_id == 0)//入驻商户的订单不需要设置佣金
                    $this->setReturnData($order);*/
            }
            //微信支付
            if ($this->pay_type == 'WECHAT_PAY') {
                $res = $this->unifiedUnionOrder($order_list, $total_pay_price);
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
                    'order_no' => $res['order_no'],
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
                    'body' => $res['body'],
                ];
            }
            //货到付款和余额支付数据处理
            if ($this->pay_type == 'HUODAO_PAY' || $this->pay_type == 'BALANCE_PAY') {
                //余额支付  用户余额变动
                if ($this->pay_type == 'BALANCE_PAY') {
                    if ($this->user->money < $total_pay_price) {
                        return [
                            'code' => 1,
                            'msg' => '支付失败，余额不足'
                        ];
                    }
                    $this->user->money = $this->user->money - $total_pay_price;
                    $this->user->save();
                    foreach ($order_list as $order) {
                        $order->is_pay = 1;
                        $order->pay_type = 3;
                        $order->pay_time = time();
                        $order->save();
                    }
                }
                foreach ($order_list as $order) {
//                    //发送短信提醒
//                    Sms::send($order->store_id, $order->order_no);
//                    //发送后台消息
//                    OrderMessage::set($order->id, $order->store_id);
//                    //打印订单
//                    $printer_setting = PrinterSetting::findOne(['store_id' => $order->store_id, 'is_delete' => 0]);
//                    $type = json_decode($printer_setting->type, true);
//                    if ($type['pay'] && $type['pay'] == 1) {
//                        $printer_order = new PinterOrder($order->store_id, $order->id);
//                        $res = $printer_order->print_order();
//                    }
//                    //发送邮件提醒
//                    $mail = new SendMail($order->store_id, $order->id, 0);
//                    $mail->send();

                    //支付完成后，相关操作
                    $form = new OrderWarn();
                    $form->order_id = $order->id;
                    $form->order_type = 0;
                    $form->notify();
                }
                return [
                    'code' => 0,
                    'msg' => 'success',
                    'data' => '',
                ];
            }
        }
    }

    /**
     * 购买成功首页提示
     */
    private function buyData($order_no, $store_id, $type)
    {
        $order = Order::find()->select(['u.nickname', 'g.name', 'u.avatar_url', 'od.goods_id'])->alias('c')
            ->where('c.order_no=:order', [':order' => $order_no])
            ->andwhere('c.store_id=:store_id', [':store_id' => $store_id])
            ->leftJoin(['u' => User::tableName()], 'u.id=c.user_id')
            ->leftJoin(['od' => OrderDetail::tableName()], 'od.order_id=c.id')
            ->leftJoin(['g' => Goods::tableName()], 'od.goods_id = g.id')
            ->asArray()->one();


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
        $cache->set($key, $new, 300);
    }

    /**
     * 设置佣金
     * @param Order $order
     */
    private function setReturnData($order)
    {

        $setting = Setting::findOne(['store_id' => $order->store_id]);
        if (!$setting || $setting->level == 0)
            return;
        $user = User::findOne($order->user_id);//订单本人
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
   

        $payComm = new PayComm();


        $rebateData = $payComm->autoRebate($order->user_id,$order_total,$rebate,['is_order'=>1,'id'=>$order->id]);
        $rebate =  $rebateData->rebate;
        $order->rebate = $rebate < 0.01 ? 0 : $rebate;

        $user = User::findOne($order->user_id);
        $rebateCopy  = $order_total * $rate_rebate / 100;
        if($user->level > 0)
            $rebateCopy = $order->rebate;




        //分销商收益返利
      /*  if($order->rebate > 0) {

            $parent1Rebate = 0;
            $parent2Rebate = 0;
            if (!empty($user['parent_id'])) {


               /* $parent1Member = User::findOne($user['parent_id']);
                if (!empty($parent1Member)) {
                    if ($parent1Member['level'] == 1) {
                        $parent1Rebate = $order->rebate * 0.2;
                        if (!empty($parent1Member['parent_id'])) {
                            $parent2Member = User::findOne($parent1Member['parent_id']);
                            if (!empty($parent2Member) && $parent2Member['level'] == 2)
                                $parent2Rebate = $parent1Rebate * 0.25;

                        }
                    } elseif ($parent1Member['level'] == 2)
                        $parent1Rebate = $order->rebate * 0.25;
                }



                //一級 与 二級 收益
                $order->parent_s1_rebate = doubleval($parent1Rebate);
                $order->parent_s2_rebate = doubleval($parent2Rebate);

            }

        }*/

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
                            $parent2Rebate =  $rebateCopy * 0.16;
                        }

                     if($parent2Member->level == 2){
                         $order->parent_id_1 = $parent2Member->id;
                         $parent2Rebate =  $rebateCopy * 0.20;
                     }



                        //找到 有效二级
                        $parent3Member = User::findOne($parent2Member->parent_id);


                        if($parent3Member->level == 2){
                            $order->parent_id_2 = $parent3Member->id;
                            $parent3Rebate =  $rebateCopy * 0.16;
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
                        $parent1Rebate =  $order->rebate * 0.16;
                    }

                    if($parent1Member->level == 2) {
                        $order->parent_id = $parent1Member->id;
                        $parent1Rebate = $order->rebate * 0.20;
                    }

                }

                //自己的级别小于二级上级 则
                if($member->level <= $parent2Member->level){
                    if($parent2Member->level == 2 ) {
                        $order->parent_id_1 = $parent2Member->id;
                        $parent2Rebate = $order->rebate * 0.16;
                    }

                }


            }

            
            $order->first_price = doubleval($parent1Rebate);
            //存在判断 是否取得佣金
            if($rebateData->is_auto == 1){

                if(!empty($order->first_price)){

                   $shareParentInfo =  ShareDetailed::findOne(['from_id'=>$order->user_id,'status'=>0]);

                  if(!empty($shareParentInfo)) {
                    $pt_amount += $rebateData->autoRebate * $shareParentInfo->price / 298;
                     /*// $is_tjpt = 1;
                      if ($shareParentInfo->price == 40)
                          $pt_amount += $rebateData->autoRebate * 40 / 298;
                      elseif ($shareParentInfo->price == 65)
                          $pt_amount += $rebateData->autoRebate * 65 / 298;*/
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

      	  if($g_v->goods_id == LsSsds::SHIPPING_FEE_GOODS || $g_v->goods_id ==210){
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

        $order->save();
    }

    /**
     * @param $parent_id
     */
    private function getAutoParentInfo($parent_id){

            $name = null;
            $parentMember = User::findOne($parent_id);

            //print_r($parentMember);
            if(empty($parentMember)) return $name;

                if($parentMember->level > 0)
                    $name = $parentMember->id;
                else
                    return  $this->getAutoParentInfo($parentMember->parent_id);

        return $name;


    }

    //单个订单微信支付下单
    private function unifiedOrder($goods_names)
    {
        $res = $this->wechat->pay->unifiedOrder([
            'body' => $goods_names,
            'out_trade_no' => $this->order->order_no,
            'total_fee' => $this->order->pay_price * 100,
            'notify_url' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/pay-notify.php',
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
                $this->order->order_no = (new OrderSubmitForm())->getOrderNo();
                $this->order->save();
                return $this->unifiedOrder($goods_names);
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

    //合并订单微信支付下单
    private function unifiedUnionOrder($order_list, $total_pay_price)
    {
        $data = [
            'body' => count($order_list) . '笔订单合并支付',
            'out_trade_no' => $this->getOrderUnionNo(),
            'total_fee' => $total_pay_price * 100,
            'notify_url' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/pay-notify.php',
            'trade_type' => 'JSAPI',
            'openid' => $this->user->wechat_open_id,
        ];
        $order_union = new OrderUnion();
        $order_union->store_id = $this->store_id;
        $order_union->user_id = $this->user->id;
        $order_union->order_no = $data['out_trade_no'];
        $order_union->price = $total_pay_price;
        $order_union->is_pay = 0;
        $order_union->addtime = time();
        $order_union->is_delete = 0;
        $order_id_list = [];
        foreach ($order_list as $order) {
            $order_id_list[] = $order->id;
        }
        $order_union->order_id_list = json_encode($order_id_list);
        if (!$order_union->save()) {
            return $this->getModelError($order_union);
        }
        $res = $this->wechat->pay->unifiedOrder($data);
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
                return $this->unifiedUnionOrder($order_list, $total_pay_price);
            } else {
                return [
                    'code' => 1,
                    'msg' => '支付失败，' . (isset($res['err_code_des']) ? $res['err_code_des'] : ''),
                    'res' => $res,
                ];
            }
        }
        foreach ($order_list as $order) {
            $order->order_union_id = $order_union->id;
            $order->save();
        }
        $res['order_no'] = $data['out_trade_no'];
        $res['body'] = $data['body'];
        return $res;
    }

    public function getOrderUnionNo()
    {
        $order_no = null;
        while (true) {
            $order_no = 'U' . date('YmdHis') . rand(10000, 99999);
            $exist_order_no = OrderUnion::find()->where(['order_no' => $order_no])->exists();
            if (!$exist_order_no)
                break;
        }
        return $order_no;
    }
}