<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/11
 * Time: 16:07
 */

namespace app\modules\api\models;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Setting;
use app\models\Share;
use app\models\Ssdsplayer;
use app\modules\api\models\ShareForm;
use app\models\User;
use app\models\ShareDetailed;


/**
 * By Wynters
 * @property
 */
class PayComm extends Model
{


    public function payGo($order_id,$user_id)
    {
        //选手处理
        $goods_list = OrderDetail::find()->where('order_id = '.$order_id)->asArray()->all();
        $goods_num = 0;
        $is_bundle = false;
       // file_put_contents('/data/wwwroot/api.anmeila.com.cn/modules/api/models/123.txt','11111111111');
        foreach ($goods_list as $key => $goods){
            if($goods['goods_id'] == 199)
                $goods_num +=$goods['num'];

            if($goods['goods_id'] == 205){
               // file_put_contents('/data/wwwroot/api.anmeila.com.cn/modules/api/models/222.txt','11111111111');
                $this->autoToShare($user_id);
                $is_bundle = true;
            }
        }
        if($goods_num >= 2 || $is_bundle){
            $uid = $user_id;
            $userinfo = User::findOne(['id'=>$uid]);
            $form = new Ssdsplayer();
            $res = Ssdsplayer::findOne(['uid'=>$uid]);
            if(empty($res)){
                $form->avatar = $userinfo->avatar_url;
                $form->username = $userinfo->nickname;
                $form->add_time = time();
                $form->uid = $uid;
                $form->save();
                $mobile = $userinfo->mobile;
                $nickname = $userinfo->nickname;
                $in = 'UTF-8';
                $out = 'GBK';
                $str = "恭喜".'“'.$nickname.'”'."，您已成功报名参加安美拉二代变形记瘦身减肥大赛！";
                $str = preg_replace('# #', '', $str);
                $str = mb_convert_encoding($str, $out, $in);
                $content = str_replace('charset=utf-8','',$str);
                $url = 'http://202.91.244.252/qd/SMSSendYD?usr=7426&pwd=DYkk@1595z&mobile='.$mobile.'&sms='.$content.'&extdsrcid=';
                file_get_contents($url);
            }

            // return json_encode(array('code'=>0,'msg'=>'success'));
        }




    }

    public function autoRebate($user_id,$order_price,$rebate,$order = []){


        if($rebate == 0)
            return 0;

        $addtime = 0;
        $is_auto = 0;
       // $is_order = 0;
 
        if(!empty($share))
           $addtime = $share->addtime;

         $user = User::findOne($user_id);


        if($user->is_distributor == 1){

            $share = Share::findOne(['user_id'=>$user_id]);
            if(!empty($share))
                $addtime = $share->addtime;
            if(empty($order))
                $where = '';
            else
                $where = ' AND `id` < '.$order['id'];
            //计算历史累计金额
            $total_price = Order::find()
            ->where('is_pay = 1 AND `apply_delete` = 0 AND `is_cancel` = 0 AND `is_delete` = 0 AND `user_id` = '.$user_id.' AND `addtime` >= '.$addtime.$where)
            ->sum('total_price - express_price');
        }else{
            $rebate = 0;
            if(empty($order))
                return $rebate;
        }
          

        if($total_price < 298){

            $nowPrice = $total_price + $order_price;

            if($nowPrice > 298){
                //多余 = 现在累计 - 298
                $surplusPrice = $nowPrice - 298;
                //这次10% = 交易金额 - 多余
                $thisPrice = $order_price - $surplusPrice;
                //计算金额
                $rebate = ($thisPrice * 0.1) + ($surplusPrice * 0.16);

                $is_auto = 1;
                $is_autoRebate = $thisPrice;

            }else{
                $rebate = $order_price * 0.1;
                $is_auto = 1;
                $is_autoRebate = $order_price;
            }


        }else
          $is_autoRebate = $order_price;

        if($order['is_order'] == 1)
            return (object)['rebate' => $rebate,'is_auto' => $is_auto,'autoRebate' => $is_autoRebate];

        return $rebate;



    }

    /**
     * @param $user_id
     * @return string
     */
    public function autoToShare($user_id)
    {
        //file_put_contents('/data/wwwroot/api.anmeila.com.cn/modules/api/models/333.txt','111');
        //get this member info
        $member = User::findOne($user_id);
        if(!empty($member)){
            //file_get_contents('/data/wwwroot/api.anmeila.com.cn/modules/api/models/2.txt','12121');
            // and no empty  find share
            $share = Share::findOne(['user_id'=>$member->id, 'store_id' => $member->store_id]);
            if(empty($share) || $share->status != 1){


                $member->is_distributor = 1;
                $member->level = 1;
                $member->money += 298;
                if(empty($share))
                    $share = new Share();

                $share->store_id = $member->store_id;
                $share->user_id = $member->id;
                $share->status = 1;
               // $share->agree = 1;
                $share->name = $member->nickname;
                $share->addtime = time();


                if(!$share->save()){

                    return [
                        'code' => 1,
                        'msg' => '分销商数据插入出错',
                    ];
                }
               // file_get_contents('/data/wwwroot/api.anmeila.com.cn/modules/api/models/4.txt','12121');

                $parent = User::findOne($member->parent_id);

                if(!empty($parent)){

                    $mod_ShareDetailed = new ShareDetailed;
                    $mod_ShareDetailed->from_id = $member->id;
                    $mod_ShareDetailed->user_id = $member->parent_id;
                    $mod_ShareDetailed->store_id = $member->store_id;
                    $mod_ShareDetailed->remarks = '推荐收益';

                    if($parent['is_distributor'] == 1 && $parent['level'] == 1){
                        $mod_ShareDetailed->price = 40;
                        $parent->total_price += 40;
                        $parent->price += 40;
                    }
                   // file_get_contents('/data/wwwroot/api.anmeila.com.cn/modules/api/models/5.txt','12121');
                    if($parent['is_distributor'] == 1 && $parent['level'] == 2){
                        $mod_ShareDetailed->price = 65;
                        $parent->total_price += 65;
                        $parent->price += 65;
                    }
                   // file_put_contents('/data/wwwroot/api.anmeila.com.cn/controllers/2.txt','11111');
                    $fens1 = 0; $fens2 = 0;

                    //计算全部一代
                    $parent1Member = User::find()->where('`level` >= 1 AND `parent_id` = '.$member->parent_id)->asArray()->all();
                    $fens1 = count($parent1Member);

                    foreach ($parent1Member as $k => $v){
                        $parentMember = User::find()->where(['parent_id'=>$v->id])->asArray()->all();
                        $fens2 += count($parentMember);

                    }



                    $fensCount = $fens1 + $fens2;

                    if($fens1 >= 15 && $fensCount >= 60)
                        $parent->level = 2;




                    if(!$parent->save()){

                        return [
                            'code' => 2,
                            'msg' => '上级数据出错',
                        ];
                    }


                    if(!$mod_ShareDetailed->saveS()){

                        return [
                            'code' => 3,
                            'msg' => '返利数据出错',
                        ];
                    }


                }

                if($member->save()){

                    return [
                        'code' => 0,
                        'msg' => '成功!',
                    ];
                }






            }

            $member->save();
        }




    }

}