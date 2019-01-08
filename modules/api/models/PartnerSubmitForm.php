<?php
namespace app\modules\api\models;
use app\models\Goods;
use app\models\Lashou;
use app\models\Option;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Store;
use app\modules\mch\models\OrderSendForm;
use yii\base\Exception;

/**
 * Created by PhpStorm.
 * User: fbi
 * Date: 2018/5/12 0012
 * Time: 13:42
 */

class PartnerSubmitForm extends Model
{
    public $page;
    public $store_id;
    public $user_id;
    public $order_id;
    public $express;
    public $express_no;

    public function getMarketOrders(){

        if(empty($this->page))
            $this->page = 0;

        //查询合伙人订单
        $partnerOrders =  Order::find()->select('`id`,`is_confirm`,`apply_delete`,`third_price`,`pt_amount`,`first_price`,`is_pay`,`is_send`,
        `is_confirm`,`pay_price`,`second_price`,`express_price`,`rebate`,`order_no`,
        `address`,`total_price`,addtime,is_delete')->where('`store_id` = '.$this->store_id.' AND apply_delete = 0 AND `is_send` = 0  AND `partner_id` = 0 AND `is_delete` = 0 AND `is_pay` = 1 AND `user_id` != '.$this->user_id)->limit(10)->offset($this->page)->asArray()->orderBy('id DESC')->all();
        $partnerOrders = $this->mergeOrdersAndGoods($partnerOrders);
        return null;
        return $partnerOrders;
    }

    private function mergeOrdersAndGoods ($partnerOrders){

        //print_r($partnerOrders);
        //合并 订单 与 商品
        foreach ($partnerOrders as $key => $value) {

            $goods = [];
            //$address = '';
            $order_id = $value['id'];
            $partnerOrders[$key]['addtime'] = date('Y-m-d H:i',$value['addtime']);
            //preg_match('/([\s\S]+)市/',$value['address'],$address);
            $address = json_decode($value['address_data'],true);
            $partnerOrders[$key]['address'] = $address['province'] . $address['city'];

            //共获得
            $partnerOrders[$key]['guanli'] = $value['first_price'] + $value['second_price'] + $value['third_price'];
            $partnerOrders[$key]['total_shouyi'] =  $value['pay_price'] - $value['pt_amount'] - $value['rebate'] - $partnerOrders[$key]['guanli'];
            $status = '';
            //echo $value->is_pay;
            $status = $apply_delete  ='';
            if($partnerOrders[$key]['apply_delete'] == 1)
                $apply_delete = '(售后处理中...)';
            if ($partnerOrders[$key]['is_pay'] == 0) {
                $status = '订单未付款'.$apply_delete;
            } elseif ($partnerOrders[$key]['is_pay'] == 1 && $partnerOrders[$key]['is_send'] == 0) {
                $status = '订单待发货'.$apply_delete;
            } elseif ($partnerOrders[$key]['is_send'] == 1 && $partnerOrders[$key]['is_confirm'] == 0) {
                $status = '订单已发货'.$apply_delete;
            } elseif ($partnerOrders[$key]['is_confirm'] == 1) {
                $status = '订单已完成'.$apply_delete;
            }
            $partnerOrders[$key]['status'] = $status;






            $orderDetail = OrderDetail::find()
                ->select('goods_id,attr,pic,num')
                ->where('`is_delete` = 0 AND `order_id` = ' . $order_id)
                ->asArray()
                ->all();

            $partnerOrders[$key]['cost_price'] = 0;
            foreach ($orderDetail as $ik => $iv) {
                $goods_id = $orderDetail[$ik]['goods_id'];
                $orderDetail[$ik]['attr'] = json_decode($orderDetail[$ik]['attr']);

                $goods[$ik] = Goods::find()->select('`name`,`cost_price`,`price`')->where('`id` ='.$goods_id)->asArray()->one();

                //成本
                $partnerOrders[$key]['cost_price'] += $goods[$ik]['cost_price'] * $iv['num'];


                $partnerOrders[$key]['goodInfo'][$ik] =  $goods[$ik];

                $partnerOrders[$key]['goodInfo'][$ik] = array_merge($partnerOrders[$key]['goodInfo'][$ik],$orderDetail[$ik]);

            }
            //shouyi
            $partnerOrders[$key]['shouyi'] =  $partnerOrders[$key]['total_shouyi'] - $partnerOrders[$key]['cost_price'] - $partnerOrders[$key]['express_price'];

            $partnerOrders[$key]['shipping_fee'] = 0;
            if($partnerOrders[$key]['pay_time'] >= strtotime(Lashou::DAI_FA_START_TIME) && $partnerOrders[$key]['is_offline'] == 0){
                $partnerOrders[$key]['shouyi'] = -1;

                $ls_shipping_fee = max($partnerOrders[$key]['partner_shipping_fee'], $partnerOrders[$key]['express_price']);
                $ls_goods_cost = $partnerOrders[$key]['pay_time'] > VERSION2_SART_TIME ? Lashou::getGoodsCost($order_id) : Lashou::getGoodsCostOld($order_id);
                $partnerOrders[$key]['total_shouyi'] -= $ls_shipping_fee + $ls_goods_cost;
                $partnerOrders[$key]['total_shouyi'] = number_format($partnerOrders[$key]['total_shouyi'], 2);
                $partnerOrders[$key]['shipping_fee'] = $ls_shipping_fee;
            }

            if ($partnerOrders[$key]['pay_time'] <= VERSION2_SART_TIME) {
                $partnerOrders[$key]['cost_price'] = Lashou::getGoodsCostOld($order_id);
            }
        }

        return $partnerOrders;

    }

    public function orderGrab(){


        try {
            throw new Exception('功能维护中....',102);
            if(empty($this->order_id))
                throw new Exception('获取订单号失败',101);
            //获取 订单信息
            $order = Order::findOne($this->order_id);
            //判断订单信息
            if(empty($order))
                throw new Exception('该订单信息不存在',102);

            //判断该订单是否符合抢单条件

            if($order->is_delete != 0 || $order->store_id != $this->store_id || $order->is_send != 0 || $order->is_pay != 1 || $order->is_send != 0)
                throw new Exception('该订单不符合抢单条件',103);

            if(!empty($order->partner_id))
                throw new Exception('该订单已被其他' . PARTNER_LABEL . '所占有',104);
            $order->partner_id = $this->user_id;
            $order->partner_grab_time = time();

            if($order->save())
                $result  = ['code'=>0,'msg'=>'抢单成功'];
            else
                throw new Exception('服务器繁忙,请稍后再试',104);


        } catch (Exception $e) {

            $result  = ['code'=>$e->getCode(),'msg'=>$e->getMessage()];

        }

        return $result;


    }

    public function getMyOrders(){

        if(empty($this->page))
            $this->page = 0;

        //查询合伙人订单
        $partnerOrders =  Order::find()
            ->where('`store_id` = '.$this->store_id.' AND `partner_id` = '.$this->user_id.' AND `is_delete` = 0 AND `is_pay` = 1')
            ->limit(10)
            ->offset($this->page * 10)
            ->asArray()
            ->orderBy('id DESC')
            ->all();

        return $this->mergeOrdersAndGoods($partnerOrders);
    }

    public function sendGoods(){
        
        try {

            if(empty($this->order_id))
                throw new Exception('获取订单号失败',101);
            //获取 订单信息
            $order = Order::findOne($this->order_id);
            //判断订单信息
            if(empty($order))
                throw new Exception('该订单信息不存在',102);

            /*if($order->pay_time >= strtotime(Lashou::DAI_FA_START_TIME)){
                throw new Exception('该订单不能发货',400);
            }*/

            //判断该订单是否符合抢单条件
            if($order->is_delete != 0 || $order->store_id != $this->store_id || $order->is_pay != 1 || $order->is_send != 0 || $order->apply_delete != 0)
                throw new Exception('该订单不符合发货条件',103);
            if(empty($this->express) || empty($this->express_no))
                throw new Exception('请输入物流信息',103);


            $form = new OrderSendForm();

            $form->express = $this->express;
            $form->express_no = $this->express_no;
            $form->order_id = $this->order_id;
            $form->store_id = $this->store_id;
            //return ['code' => 1, 'msg' => '请稍后再试', 'data' => $form->toArray()];
            $result  = $form->save();


        } catch (Exception $e) {

            $result  = ['code'=>$e->getCode(),'msg'=>$e->getMessage()];

        }

        return $result;

    }

    public function getData()
    {
        $data = Option::get('partner_custom_data', $this->store_id);
        $default_data = $this->getDefaultData();
        if (!$data) {
            $data = $default_data;
        } else {
            $data = json_decode($data, true);
            $data = $this->checkData($data, $default_data);
        }

        return [
            'code' => 0,
            'data' => $data,
        ];
    }

    //检查是否有新增的值
    public function checkData($list = array(), $default_list = array())
    {
        $ignore = ['menu'];
        $new_list = [];
        foreach ($default_list as $index => $value) {
            if (isset($list[$index])) {
                if (is_array($value) && !in_array($index, $ignore)) {
                    $new_list[$index] = $this->checkData($list[$index], $value);
                } else {
                    $new_list[$index] = $list[$index];
                }
            } else {
                $new_list[$index] = $value;
            }
        }
        return $new_list;
    }

    public function getDefaultData()
    {
        return [
            'menus' => [
                'money'=>[
                    'name' => '订单市场',
                    'icon' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/share-custom/img-share-price.png',
                    'open_type' => 'navigator',
                    'url' => '/pages/partner/market/market',
                    'tel' => '',
                ],
                'order'=>[
                    'name' => '我的订单',
                    'icon' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/share-custom/img-share-order.png',
                    'open_type' => 'navigator',
                    'url' => '/pages/partner/order/order',
                    'tel' => '',
                ],
                'cash'=>[
                    'name' => '提现明细',
                    'icon' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/share-custom/img-share-cash.png',
                    'open_type' => 'navigator',
                    'url' => '/pages/cash-detail/cash-detail',
                    'tel' => '',
                ],
                'profitDetails'=>[
                    'name' => '我的收益',
                    'icon' => \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/statics/images/share-custom/img-share-profitDetails.png',
                    'open_type' => 'navigator',
                    'url' => '/pages/share-qrcode/share-profitDetails',
                    'tel' => '',
                ],
            ],
            'words' => [
                'can_be_presented'=>[
                    'name' => '可提现收益',
                    'default' => '可提现收益',
                ],
                'already_presented'=>[
                    'name' => '已提现收益',
                    'default' => '已提现收益',
                ],
                'parent_name'=>[
                    'name' => '推荐人',
                    'default' => '推荐人',
                ],
                'pending_money'=>[
                    'name' => '待打款收益',
                    'default' => '待打款收益',
                ],
                'cash'=>[
                    'name' => '提现',
                    'default' => '提现',
                ],
                'user_instructions'=>[
                    'name' => '用户须知',
                    'default' => '用户须知',
                ],
                'apply_cash'=>[
                    'name' => '我要提现',
                    'default' => '我要提现',
                ],
                'cash_type'=>[
                    'name' => '提现方式',
                    'default' => '提现方式',
                ],
                'cash_money'=>[
                    'name' => '提现金额',
                    'default' => '提现金额',
                ],
                'order_money_un'=>[
                    'name' => '未结算收益',
                    'default' => '未结算收益',
                ],
            ]
        ];
    }

}