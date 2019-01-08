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

class TestController extends Controller
{
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