<?php
/**
 * Created by PhpStorm.
 * User: wooran
 * Date: 2018/6/29
 * Time: 下午1:54
 */

namespace app\modules\api\controllers;

use app\models\ReOrder;
use app\models\PtOrder;
use app\models\MsOrder;
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
use app\modules\mch\models\OrderListForm;

class TongjiController extends Controller
{
    public function actionTongji(){
        $form = new OrderListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->limit = 10;
        $date_start = $form->attributes['date_start'];
        $date_end = $form->attributes['date_end'];
        if(empty($date_end)){
            $date_end = date('Y-m-d',time());
        }
        if(empty($date_start)){
            $date_start = date('Y-m-d',time()-30*24*3600);
        }
        $date = $this->getDateRange($date_start,$date_end);
        foreach ($date as $k=>$v){
            if(empty($form->status)){
                $query = Order::find()->alias('o')->where([
                    'o.store_id' => 1,
                    'o.mch_id' => 0,
                    'o.apply_delete'=>0,
                    'o.is_delete'=>0
                ]);
                $query->andWhere(['>=', 'o.pay_time', strtotime($v)]);
                $query->andWhere(['<=', 'o.pay_time', strtotime($v)+24*3600]);
            }

            $query1 =  ReOrder::find()->alias('r')->where([
                'r.is_pay'=>1,
                'r.is_delete'=>0
            ]);
            //拼团
            if($form->status == 1){
                $query = PtOrder::find()->where([
                    'is_pay'=>1,
                    'apply_delete'=>0,
                    'is_delete'=>0
                ]);
                $query->andWhere("pay_time >=". strtotime($v));
                $query->andWhere("pay_time<= ".(strtotime($v)+24*3600));
            }
            //秒杀
            if($form->status == 2){
                $query = MsOrder::find()->where([
                    'is_pay'=>1,
                    'apply_delete'=>0,
                    'is_delete'=>0
                ]);
                $query->andWhere("pay_time >=". strtotime($v));
                $query->andWhere("pay_time<= ".(strtotime($v)+24*3600));
            }


            $query1->andWhere("r.pay_time >=". strtotime($v));
            $query1->andWhere("r.pay_time<= ".(strtotime($v)+24*3600));

            //$pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);
            $list[$k] = $query ->select("sum(total_price) as price_sum, count(id) as id_sum")->asArray()->one();
            $pay = $query1->select("sum(pay_price) as pay_price ")->asArray()->one();
            $pay = implode(",", $pay);
            //$list[$k] += $v;
            array_push($list[$k],$v,$pay);

        }
        return $this->render('tongji', [
            'list' => array_reverse($list),
        ]);
    }
    function getDateRange($startdate, $enddate) {
        $stime = strtotime($startdate);
        $etime = strtotime($enddate);
        $datearr = [];
        while ($stime <= $etime) {
            $datearr[] = date('Y-m-d', $stime);//得到dataarr的日期数组。
            $stime = $stime + 86400;
        }
        return $datearr;
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
//160000修复合伙人代理，之后不修改
    public function actionPartner1(){
        set_time_limit(0);
        $query = User::find()->where(['and',['>','id',154999],['<','id',160000]])->select('is_partner,id,mobile')->asArray()->all();
        foreach ($query as $k=>$v){
            $url = "http://wx.anmeila.com.cn/appapi/checkuserishhr?phone=".$v['mobile'];
            $res = file_get_contents($url);
            $res = json_decode($res,true);
            if($v['is_partner'] != $res['result'] ){
                if($res['result'] == 0){
                    echo '代理'.$v['id'];
                    echo "<br/>";
                }
                $user = User::findOne(['id'=>$v['id']]);
               $user->is_partner = $res['result'];
               $r=$user->save();
               if(empty($r)){

                   echo $v['id'].'----'.$v['mobile'];
                   echo "<br/>";
               }else{
                   echo $v['id'];
                   echo "<br/>";
               }

            }
        }

    }
    //160000修复 合伙人改成代理
    public function actionPartner()
    {
        set_time_limit(0);
        $query = User::find()->where(['and', ['>', 'id', 189999], ['<', 'id', 200000]])->andWhere(['is_partner' => 1])->asArray()->all();

        foreach ($query as $k => $v) {
            $url = "http://wx.anmeila.com.cn/appapi/checkuserishhr?phone=" . $v['mobile'];
            $res = file_get_contents($url);
            $res = json_decode($res, true);
            if ($res['result'] == 0) {
                if ($res['result'] == 0) {
                    echo '代理' . $v['id'];
                    echo "<br/>";
                }
                $user = User::findOne(['id' => $v['id']]);
                $user->is_partner = $res['result'];
                $r = $user->save();
                if (empty($r)) {

                    echo $v['id'] . '----' . $v['mobile'];
                    echo "<br/>";
                } else {
                    echo $v['id'];
                    echo "<br/>";
                }

            }
        }
    }
//160000 修复 代理变合伙人
        public function actionPartner2(){
            set_time_limit(0);
            $query = User::find()->where(['and',['>','id',159999],['<','id',165000]])->andWhere(['is_partner'=>0])->asArray()->all();

            foreach ($query as $k=>$v){
                $url = "http://wx.anmeila.com.cn/appapi/checkuserishhr?phone=".$v['mobile'];
                $res = file_get_contents($url);
                $res = json_decode($res,true);
                if($res['result'] == 1){
                    if($res['result'] == 0){
                        echo '代理'.$v['id'];
                        echo "<br/>";
                    }
                    $user = User::findOne(['id'=>$v['id']]);
                    $user->is_partner = $res['result'];
                    $r=$user->save();
                    if(empty($r)){

                        echo $v['id'].'----'.$v['mobile'];
                        echo "<br/>";
                    }else{
                        echo $v['id'];
                        echo "<br/>";
                    }

                }
            }


    }
}