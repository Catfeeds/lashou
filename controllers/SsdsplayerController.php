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
use app\models\Setting;
use app\models\Share;
use app\models\Ssdsplayer;
use app\models\Store;
use app\models\ShareDetailed;
use app\models\UploadConfig;
use app\models\UploadForm;
use app\models\User;
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
use app\models\GoodsTest;

class SsdsplayerController extends Controller
{
    public $sort_order;
    public $page;
    public $limit ;
    public $keywords;
    public function actionJoin(){
        $uid = \Yii::$app->user->identity->id;
        $userinfo = User::findOne(['id'=>$uid]);
        $form = new Ssdsplayer();
        $res = Ssdsplayer::findOne(['uid'=>$uid]);
        if(!empty($res)){
            return json_encode(array('code'=>1,'msg'=>'已参加'));
        }
        $form->avatar = $userinfo->avatar_url;
        $form->username = $userinfo->nickname;
        $form->add_time = time();
        $form->uid = $uid;
        $res = $form->save();
        if($res){
            $mobile = $userinfo->mobile;
            $nickname = $userinfo->nickname;
            $str = "恭喜".'“'.$nickname.'”'."，您已成功报名参加安美拉二代变形记瘦身减肥大赛！";
            $in = 'UTF-8';
            $out = 'GBK';
            $str = preg_replace('# #', '', $str);
            $str = mb_convert_encoding($str, $out, $in);
            $content = str_replace('charset=utf-8','',$str);
            $url = 'http://202.91.244.252/qd/SMSSendYD?usr=7426&pwd=DYkk@1595z&mobile='.$mobile.'&sms='.$content.'&extdsrcid=';
            file_get_contents($url);
            return json_encode(array('code'=>0,'msg'=>'success'));
        }

    }
    function httpPost($url, $post_date){
        $curl = curl_init();
        //echo 'curl' . $curl . 'curl';
        //print_r(array('url'=>$url, 'post_date'=>$post_date));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/html;charset=gbk'));
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
    public function actionInfo(){
        $uid = \Yii::$app ->request->get('player_user_id',0);
        if($uid == 0){
            $uid = \Yii::$app->user->identity->id;
        }
        $player =  Ssdsplayer::find()->where(['uid'=>$uid]) ->asArray()->one();
        $playerImg = lsPlayerSource::find()->where(['user_id'=>$uid,'type'=>1])->asArray()->all();
        $playerVideo = lsPlayerSource::find()->where(['user_id'=>$uid,'type'=>0])->asArray()->all();
        for ($i=0; $i < count($playerImg); $i++) {
            $player['img'][$i] = $playerImg[$i]['val'];
        }
        for ($i=0; $i < count($playerVideo); $i++) {
            $player['video'][$i] = $playerVideo[$i]['val'];
        }

        $user = User::find()->select('id,nickname,username,is_distributor,is_partner,parent_id,level,ls_price')->where(['id'=>$uid]) ->asArray()->one();
        $res = [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'player_info' => $player,
                'user_info' => $user
            ],
        ];
        return $this->renderJson($res);
    }

    public function actionIndex(){
        $query = Ssdsplayer::find()->where([
        ]);
        $this->sort_order = \Yii::$app->request->get('sort_order',1);
        $this->keywords = \Yii::$app->request->get('keywords',1);
        if ($this->sort_order == 1) {//综合排序
            $orderby = 'vote_sum DESC';
        }
        if ($this->sort_order == 2) {//投票排序
            $orderby = 'vote_viewer DESC';
        }
        if ($this->sort_order == 3) {//体脂排序
           $orderby = 'vote_weight DESC';
        }
        if ($this->keywords)
            $query->andWhere(['LIKE', 'username', $this->keywords]);
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1, 'pageSize' => $this->limit]);
        $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy("$orderby")->asArray()->all();
        $res = [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'row_count' => $count,
                'page_count' => $pagination->pageCount,
                'list' => $list,
                'keywords'=>$this->keywords
            ],
        ];
        $this->renderJson($res);
    }

    public function actionQiandao(){
        $res = [
            'code' => 0,
            'msg' => '已签到',
            'data' => [
            ],
        ];
        $this->renderJson($res);
    }

    public function actionVote(){
        $uid = \Yii::$app->user->identity->id;
        //判断是否是投票者

        $time = time();
        $date = date('Y-m-d',$time);
        $start_time = strtotime($date.' 00:00:00');
        $end_time = strtotime($date.' 23:59:59');
        $vote_info =Vote::find()->where('add_time >= '.$start_time)->andWhere('add_time <='.$end_time)
            ->andWhere("uid =".$uid)->one();
        $player_user_id = \Yii::$app ->request->get('player_user_id',0);
        if(!empty($vote_info)){
           return $this->renderJson(array('code'=>1,'msg'=>'今日已经投过票'));
        }
        //投票日志
        $vote = new Vote();
        $vote->uid = $uid;
        $vote->player_id = $player_user_id;
        $vote->add_time = time();
        $vote->save();
        //选手票数加一
        $player = new Ssdsplayer();
        $player_info = Ssdsplayer::findOne(['uid'=>$player_user_id]);
        $player_info->vote_viewer = $player_info ->vote_viewer +1;
        $player_info->vote_sum = $player_info ->vote_sum +1;
        $player_info ->save();
        //投票得红包
        $hongbao_total =(float) LsBonusLog::find() -> where(['uid' => $uid, 'type' => 1 ])->sum('val');
        if($hongbao_total <1000) {
            $userinfo = User::findOne(['id' => $uid]);
            $ls_price = rand(10, 30);
            $userinfo->ls_price += $ls_price / 100;
            $userinfo->save();
            //红包日志
            $lsbonuslog = New LsBonusLog();
            $lsbonuslog->add_time = time();
            $lsbonuslog->type = 1;
            $lsbonuslog->extension_id = 3;
            $lsbonuslog->uid = $uid;
            $lsbonuslog->val = $ls_price;
            $lsbonuslog->log = '投票获得随机金额的红包';
            $lsbonuslog->save();
            $this->renderJson(array('code'=>0,'msg'=>'投票成功','data'=>['hongbao'=>['val'=>$ls_price/100]]));
        }else{
            $this->renderJson(array('code'=>0,'msg'=>'投票成功','data'=>['hongbao'=>['val'=>'']]));
        }


    }

    /*
    as array 会把公共属性删除？
    */

    public function actionTest(){
        $goods = GoodsTest::find()->all();
        foreach ($goods as $key => $value) {
            print_r($value);
            echo "hello33" . $value->wooran . "33hello";
        }

        /*$goods = new GoodsTest;
        print_r($goods);*/
        //print_r(json_decode(json_encode($goods), true));
    }

}