<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8
 * Time: 14:15
 */

namespace app\modules\api\controllers;

use app\extensions\CreateQrcode;
use app\models\Cash;
use app\models\Color;
use app\models\LsBonusLog;
use app\models\lsPlayerSource;
use app\models\Option;
use app\models\Qrcode;
use app\models\Goods;
use app\models\Setting;
use app\models\Share;
use app\models\Ssdsplayer;
use app\models\Store;
use app\models\ShareDetailed;
use app\models\UploadConfig;
use app\models\UploadForm;
use app\models\User;
use app\models\Order;
use app\models\Express;
use app\models\Vote;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\BindForm;
use app\modules\api\models\CashForm;
use app\modules\api\models\CashListForm;
use app\modules\api\models\QrcodeForm;
use app\modules\api\models\ShareForm;
use app\modules\api\models\TeamForm;
use app\modules\mch\models\ShareCustomForm;
use yii\data\Pagination;
use yii\helpers\VarDumper;
use Curl\Curl;
use app\extensions\GrafikaHelper;
use Grafika\Grafika;

class ActController extends Controller
{
     public function actionSendsms()
    {
        $smsdata = \Yii::$app->request->post();
        $url = 'http://202.91.244.252/qd/SMSSendYD?usr=7426&pwd=DYkk@1595z&mobile='.$smsdata['phone'].'&sms=%D7%F0%BE%B4%B5%C4%B0%B2%C3%C0%C0%AD%B4%FA%C0%ED%2C%C0%AD%CA%D6%C6%BD%CC%A8%D1%FB%C4%FA%BC%A4%BB%EE%2C%C4%FA%B5%C4%D1%E9%D6%A4%C2%EB%CE%AA%3A'.$smsdata['mycode'].'&extdsrcid=';
        $issms = $this->http_post($url);
        if(!empty($issms)){
            $this->renderJson([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'user' => $issms,
                ],
            ]);
        }
    }
     public function actionGetphoneinfo()
    {
        $data = \Yii::$app->request->post();
        $isphone = User::findOne(['mobile' => $data['phone'], 'store_id' => $this->store_id]);
        if(!empty($isphone)){
            $this->renderJson([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'user' => $isphone,
                ],
            ]);
        }else{
            $this->renderJson([
                'code' => 1,
                'msg' => '您不符合激活政策可能是您输入的手机有误,qin',
            ]);
        }
    }
     public function actionGetuserinfo()
    {
        $data = \Yii::$app->request->post();
        $url = 'http://wx.anmeila.com.cn/lasou/CheckUserIsInAML?uid='.$data['user'].'&pwd='.$data['password'];
        $isuserinfo = $this->http_post($url,array('status'=>1));
        
        if(!empty($isuserinfo)){
            $this->renderJson([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'user' => $isuserinfo,
                ],
            ]);
        }else{
            $this->renderJson([
                'code' => 1,
                'msg' => '验证失败',
            ]);
        }
    }
    
    //请求
    function http_post($url,$post_data='',$header='',$agentip=''){
            $curl = curl_init();
            if(!empty($agentip)){
                    curl_setopt($curl, CURLOPT_PROXY, $agentip);
            }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            if(!empty($header)){
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $header); //设置头信息的地方
            }
            if(!empty($post_data)){
                    $json_post = json_encode($post_data);
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $json_post);
            }
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);
            return $res;
    }
    //获取用户的信息
    public function actionRemovebd()
    {
        $user = User::findOne(['id' => \Yii::$app->user->id]);
        if(!empty($user)){
            $user->wechat_open_id = '';
            if($user->id>=200000){
                $user->parent_id=0;
            }
            if($user->save()){
                $this->renderJson([
                    'code' => 0,
                    'msg' => '解绑成功',
                    'data' => [
                        'user' => $user,
                    ],
                ]);
            }else{
                $this->renderJson([
                    'code' => 1,
                    'msg' => '解绑失败',
                    'data' => [
                        'user' => $user,
                    ],
                ]);
            }
            
        }else{
            $this->renderJson([
                'code' =>1,
                'msg' => '未找到该用户信息',
                'data' => [
                    'user' => $user,
                ],
            ]);
        }
    }
    //获取用户的信息
    public function actionGetteams()
    {
        $data = \Yii::$app->request->get();
        $uid = $data['uid'];
        if($uid){
            $arr[] = $uid;
        }
        if(empty($uid)){
            $arr = array(102,2380,63,3392,8275,5290,10379,22170,29227,11917);
        
        }
        
        
        foreach ($arr as $vv){
            $total = 0;
            $activa = 0;
            $list = User::find()->where(['parent_id' => $vv, 'store_id' => $this->store_id])->select('id,wechat_open_id')->asArray()->all();
            foreach ($list as $v){
                if($v['wechat_open_id']!=''){
                    $activa++;
                }
                $total++;
                if(!in_array($v['id'],$arr)){
                    $heji = $this->actionGetteam($v['id']);
                    $total+=$heji['total'];
                    $activa+=$heji['activa'];
                }
                
            }
            echo 'id：'.$vv.';'.$total.';'.$activa;
            echo '<br/>';
         }
    }
    //获取用户的信息
    public function actionGetteam($uid)
    {
//        $arr = array(102,2380,63,3392,8275,5290,10379,22170,29227,11917,26693,27079,34859,38611,11829,11862,
//            107,2589,82,65,2696,6111,6613,7463,212,91,805,2362,2563,2719,2800,7310,1886,122,3277,55,19,12342,35063,2650,7550,8036,30060);
        $arr = array(102,2380,63,3392,8275,5290,10379,22170,29227,11917,26693,27079,34859,38611,11829,11862,
            107,2589,82,65,2696,6111,6613,7463,212,91,805,2362,2563,2719,2800,7310,1886,122,3277,55,19,12342,35063,2650,7550,8036,30060);
        
        $list = User::find()->where(['parent_id' => $uid, 'store_id' => $this->store_id])->select('id,wechat_open_id')->asArray()->all();
        $total = 0;
        $activa = 0;
        foreach ($list as $v){
            if($v['wechat_open_id']!=''){
                $activa++;
            }
            $total++;
            if(!in_array($v['id'],$arr)){
                    $heji = $this->actionGetteam($v['id']);
                    $total+=$heji['total'];
                    $activa+=$heji['activa'];
                }
        }
        return array('total'=>$total,'activa'=>$activa);
    }
    public function actionUpdateuser(){
        $connection  = \Yii::$app->db;
        $sql     = "SELECT MAX(id) FROM `hjmallind_user` WHERE id <200000;";
        $command = $connection->createCommand($sql);
        $res     = $command->queryAll($sql); 
        print_r($res);
        die();
        $command = Yii::$app->db->creatCommand("
        //....Your Insert Sql statement
        ");

        return $command->execute();
    }
    
    //放弃订单
    //获取用户的信息
    public function actionGiveorder()
    {
        $data = \Yii::$app->request->get();
        $order = Order::findOne(['id'=> $data['order_id'],'partner_id'=> \Yii::$app->user->id]);
        if(!empty($order)){
            $order->partner_id = 0;
            if($order->save()){
                $this->renderJson([
                    'code' => 0,
                    'msg' => '放弃成功',
                    'data' => [
                        'user' => $user,
                    ],
                ]);
            }else{
                $this->renderJson([
                    'code' => 1,
                    'msg' => '放弃失败',
                    'data' => [
                        'user' => $user,
                    ],
                ]);
            }
        }else{
            $this->renderJson([
                'code' => 1,
                'msg' => '该订单不属于你',
                'data' => [
                    'user' => $user,
                ],
            ]);
        }
    }
    //获取快递列表
    public function actionGetexpress()
    {
         $list = Express::find()->where(['type' => 'kdniao'])->select('name')->asArray()->all();
         $exp = array();
         foreach ($list as $s=>$v){
             $exp[]=$list[$s]['name'];
         }
         $this->renderJson([
                'code' => 0,
                'msg' => '获取成功',
                'data' => [
                    'list' => $exp,
                ],
            ]);
    }
    
    //查询这些人的团队有多少
    public function actionGetmyteam() {
        // SELECT * FROM `hjmallind_user`  WHERE  parent_id=82457 or parent_id IN (SELECT id FROM `hjmallind_user`  WHERE  parent_id=82457);
        //SELECT * FROM hjmallind_user WHERE `level` = 2 ;
        
        
        //$list = User::find()->where(['level'=>2])->select('id,parent_id,level')->asArray()->all();
        $list = array(['id'=>114228],['id'=>114403],['id'=>120151],);
        $connection  = \Yii::$app->db;
        foreach ($list as $v){
           $sql     = "SELECT id,parent_id,level FROM `hjmallind_user`  WHERE  parent_id=".$v['id']." or parent_id IN (SELECT id FROM `hjmallind_user`  WHERE  parent_id=".$v['id']." );";
            $command = $connection->createCommand($sql);
            $res = $command->queryAll();
           
                print_r($v['id'].':'.count($res));
                echo '<br/>';
          
            
        }
    }
    
    //订单错误矫正
    public function actionCorrectorder(){
        //第一步查询符合要求的订单
        //SELECT * FROM `hjmallind_order` WHERE is_pay=1 and  is_cancel=0 and  (apply_delete != 1 or (apply_delete = 1 and is_delete = 0) ); 
        $connection  = \Yii::$app->db;
        $sql = "SELECT id,user_id,order_no,total_price,pay_price,express_price,name,mobile,pay_type,pay_time,addtime,first_price,second_price,third_price,coupon_sub_price,rebate,pt_amount  FROM `hjmallind_order` WHERE is_pay=1 and  is_cancel=0 and  (apply_delete != 1 or (apply_delete = 1 and is_delete = 0))";
        $command = $connection->createCommand($sql);
        $res = $command->queryAll();
        print_r($res);
        foreach($res as $v){
            //校对每条数据是否正常
            $user = User::findOne(['id'=>$v['user_id']]);
            $user2 = User::findOne(['id'=>$user->parent_id]);
            print_r($user->id);
            
        }
        //第二步查找
    }
    
    //充值298没有成为星级
    public function actionSetshare() {
        //第一步查询用户星级身份 以及充值了298的用户
        $connection  = \Yii::$app->db;
        $sql = "SELECT u.id,u.parent_id,u.is_distributor,ro.id rid,ro.pay_price,ro.is_pay FROM hjmallind_user as u LEFT JOIN hjmallind_re_order as ro ON u.id = ro.user_id WHERE u.id>200000  and u.parent_id>0 AND ro.is_pay = 1 AND ro.pay_price=298 AND ro.is_parent=0 ";
        $command = $connection->createCommand($sql);
        $res = $command->queryAll();
        print_r($res);
        foreach($res as $v){
            $user = User::findOne(['id'=>$v['id'],'store_id'=>$this->store->id]);
            $partner = User::findOne(['id'=>$v['parent_id'],'store_id'=>$this->store->id]);
            
            $reorder = \app\models\ReOrder::find()->where(['id'=>$v['rid'],'is_pay'=>1,'store_id'=>$this->store->id])->andWhere(['>=','pay_price',298])->one();
            //$reorder = \Yii::$app->db->createCommand("SELECT * FROM hjmallind_re_order  WHERE user_id=".$v['id']." AND is_pay=1 AND store_id=".$this->store->id." AND pay_price>=298")->queryAll();
            $msg = '';
            if(!empty($reorder)){
                                //修改用户为代理
                                $user->is_distributor = 1;
                                $user->level = 1;
                                if($user->save()){
                                    //添加用户推荐人记录表
                                    $myshare = \app\models\Share::findOne(['user_id' => $user->id, 'store_id' => $this->store_id]);
                                    if(!$myshare){
                                       $myshare = new \app\models\Share();
                                    }
                                    $myshare->user_id=$user->id;
                                    $myshare->mobile=$user->mobile;
                                    $myshare->name=!empty($user->nickname)?$user->nickname:$user->username;
                                    $myshare->status=$user->is_distributor;
                                    $myshare->addtime = time();
                                    $myshare->store_id=1;
                                    $myshare->save();
                                    $msg.= '设置用户为代理成功;';
                                }

                                $share_detailed = \app\models\ShareDetailed::findOne(['from_id'=>$user->id]);
                                if(!$share_detailed){
                                    $share_detailed = new \app\models\ShareDetailed();
                                }
                                if(!empty($partner) && $partner->is_distributor==1){
                                    if($partner->level==2){
                                        $myprice = 80;
                                    }else{
                                        $myprice = 50;
                                    }
                                    $share_detailed->store_id = $this->store->id;
                                    $share_detailed->user_id = $user->parent_id;
                                    $share_detailed->from_id = $user->id;
                                    $share_detailed->price = $myprice;
                                    $share_detailed->remarks = '推荐收益';
                                    $share_detailed->add_time = time();
                                    if($share_detailed->save()){
                                        $msg.='修改上级收益成功;';
                                    }else{
                                        
                                            $msg.='修改上级收益失败';
                                    }
                                    //这里加入一个逻辑
                                    if($reorder->is_parent==0){
                                        //加一个判断该用户在充值表中有没有大于0的is_parent的记录如果有了就不执行了。
                                        $isreorder = \app\models\ReOrder::find()->where(['user_id'=>$v['id'],'is_pay'=>1,'store_id'=>$this->store->id])->andWhere(['>=','is_parent',1])->one();
                                        if($isreorder){
                                           
                                        }else{
                                            if(1){
                                                            //给上级加钱
                                                           if($partner){
                                                               $partner->price = $myprice+$parentuser->price;
                                                               $partner->total_price = $myprice+$parentuser->total_price;
                                                               $partner->save();
                                                           }
                                            }
                                            //加完钱之后要走这里
                                            $reorder->is_parent =2;
                                            $reorder->save();
                                            $msg.='推荐奖发送成功';
                                        }
                                    }
                                }
                                
                                
                            } else {
                                $msg='无充值298记录,不要瞎点;';
                            }
                            echo $msg . $v['id'];
        }
                            
    }
    public function actionSetnickname() {
        $nickname = \Yii::$app->db->createCommand("SELECT s.*,u.id uid,u.nickname FROM `hjmallind_share` s LEFT JOIN hjmallind_user u ON s.user_id=u.id  WHERE  s.name like '%orHh35%';")->queryAll();
        foreach ($nickname as $v){
            $nickname = \Yii::$app->db->createCommand("update hjmallind_share set name = '".$v['nickname']."' where id = ".$v['id'].";")->execute();
            if($nickname){
                echo $v['user_id'].'修改成功<br/>';
            }else{
                echo $v['user_id'].'修改失败？<br/>';
            }
        }
        
    }
    //重新寻找订单的上级
    public function actionGetup() {
        $orderlist = \Yii::$app->db->createCommand("SELECT * FROM `hjmallind_order` where partner_id=0  AND is_delete = 0  AND is_price=0 AND is_send=0 AND is_confirm=0")->queryAll();
        $i=0;
        foreach ($orderlist as $v){
            $partner = $this->getCopartner($v['user_id']);
            if(!empty($partner)){
                //找到合伙人
                $isorder = Order::findOne($v['id']);
                if(!empty($isorder)){
                    $isorder->from_partner_id=$partner;
                    $isorder->partner_id=$partner;
                    $isorder->save();
                }
                echo '修改成功'.$isorder->id.'**'.$isorder->order_no.'**'.$isorder->user_id;
            }$i++;
            echo '我的id是：'.$v['user_id'].'合伙人id：'.$partner.'<br/>';
            if($i>=30){
                 die();
            }
           
        }
    }
    public function getCopartner($userid) 
        {
            if($userid>0){
                $member = User::findOne($userid);
                while($member){
                    if($member->is_partner==1){
                        return $member->id;
                    }else if($member->parent_id==0){
                        return 0;
                    }else{
                        $member = User::findOne($member->parent_id);;
                    }
                }
            }else{
                return 0;
            }
            return 0;
        }
    
    
    
}