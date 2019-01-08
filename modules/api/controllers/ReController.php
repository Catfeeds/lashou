<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/8/29
 * Time: 下午1:14
 */

namespace app\modules\api\controllers;


use app\models\Order;
use app\models\Partner;
use app\models\PartnerShippingFee;
use app\models\ReOrder;
use app\models\ShareDetailed;
use app\models\User;

class AddressModel{
    public $province_id;
}

class ReController extends Controller
{
    //https://api.anmeila.com.cn/index.php?store_id=1&r=api/re/address-other&order_sn=20180803191205713432&province_id=1804&access_token=PbDy0cdKAdrAlmKx2Gi0MZtrYkfw8W8C&_uniacid=-1&_acid=-1&force=lashou123654&ssds=1
    public function actionAddressOther(){
        $order_sn = trim($_REQUEST['order_sn']);
        $province_id = intval($_REQUEST['province_id']);
        echo "订单号 " . $order_sn . " 省份 ID" . $province_id . "\r\n";

        $order = Order::find()->where([
            'order_no' => $order_sn
        ])->one();

        $log = json_decode($order->partner_shipping_fee_log, true);
        $weight = $log['weight'];
        echo "重量 " . $weight . "\r\n";

        $address = new AddressModel();
        $address->province_id = $province_id;

        $shipping_fee = PartnerShippingFee::genShippingFee($weight, $address);

        $order->partner_shipping_fee = $shipping_fee['shipping_fee'];
        echo "新的运费 " . $order->partner_shipping_fee . "\r\n";

        $order->partner_shipping_fee_log = json_encode($shipping_fee['log']);
        echo "相关参数 " . $order->partner_shipping_fee_log . "\r\n";
        $order->save();

        echo "success";
    }
    /*
     * 确保用户已经是一星代理商 并且share表有记录（status=1）
     *
     */
    public function actionPay298(){
        $order = ReOrder::findOne(['order_no' => $_REQUEST['order_sn']]);

        $log = [];

        $user = User::findOne($order->user_id);

        if($order->is_parent == 1){
            $log['type'] = "298充值订单";

            $parent = User::findOne($user->parent_id);

            $mod_ShareDetailed = new ShareDetailed;
            $mod_ShareDetailed->from_id = $order->user_id;
            $mod_ShareDetailed->user_id = $user->parent_id;
            $mod_ShareDetailed->store_id = $order->store_id;
            $mod_ShareDetailed->remarks = '推荐收益';

            if(!empty($parent)){

                if($parent['is_distributor'] == 1 && $parent['level'] == 1){
                    $mod_ShareDetailed->price = 40;
                    $parent->total_price += 40;
                    $parent->price += 40;

                    $log['parent'] = "一级代理商 推荐奖 " . $mod_ShareDetailed->price;
                }

                if($parent['is_distributor'] == 1 && $parent['level'] == 2){
                    $mod_ShareDetailed->price = 65;
                    $parent->total_price += 65;
                    $parent->price += 65;

                    $log['parent'] = "二级代理商 推荐奖 " . $mod_ShareDetailed->price;
                }

                $fens1 = 0;
                $fens2 = 0;

                //计算全部一代
                $parent1Member = User::find()->where('`level` >= 1 AND `parent_id` = '.$user->parent_id)->asArray()->all();
                $fens1 = count($parent1Member);

                foreach ($parent1Member as $k => $v){
                    $parentMember = User::find()->where(['parent_id'=>$v->id])->asArray()->all();
                    $fens2 += count($parentMember);

                }



                $fensCount = $fens1 + $fens2;

                if($fens1 >= 15 && $fensCount >= 60){
                    $parent->level = 2;
                    $log['team'] = "团队人数 一级(" . $fens1 . ") 二级(" . $fensCount . ") 满足升级条件 ";
                }else{
                    $log['team'] = "团队人数 一级(" . $fens1 . ") 二级(" . $fensCount . ") 不满足升级条件 ";
                }

                $parent->save();

                $mod_ShareDetailed->saveS();

            }

        }
    }
}