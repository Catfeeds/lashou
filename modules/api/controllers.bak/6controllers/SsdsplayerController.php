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
use app\models\Lashou;
use app\models\LsBonusLog;
use app\models\LsPlayerCash;
use app\models\lsPlayerSource;
use app\models\LsPlayerWeightLog;
use app\models\LsSsds;
use app\models\Option;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Qrcode;
use app\models\Setting;
use app\models\Share;
use app\models\Ssdsplayer;
use app\models\Store;
use app\models\ShareDetailed;
use app\models\UploadConfig;
use app\models\UploadForm;
use app\models\User;
use app\models\Viewer;
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
//            $mobile = $userinfo->mobile;
//            $nickname = $userinfo->nickname;
//            $in = 'UTF-8';
//            $out = 'GBK';
//            $str = "恭喜".'“'.$nickname.'”'."，您已成功报名参加安美拉二代变形记瘦身减肥大赛！";
//            $str = preg_replace('# #', '', $str);
//            $str = mb_convert_encoding($str, $out, $in);
//            $content = str_replace('charset=utf-8','',$str);
//            $url = 'http://202.91.244.252/qd/SMSSendYD?usr=7426&pwd=DYkk@1595z&mobile='.$mobile.'&sms='.$content.'&extdsrcid=';
//            file_get_contents($url);
            return json_encode(array('code'=>0,'msg'=>'success'));
        }

    }

    public function actionInfo(){
        $uid = \Yii::$app ->request->get('player_user_id',0);
        if($uid == 0){
            $uid = \Yii::$app->user->identity->id;
        }
        $player =  Ssdsplayer::find()->where(['uid'=>$uid]) ->asArray()->one();
        if($player){
            $playerImg = lsPlayerSource::find()->where(['user_id'=>$uid,'type'=>1])->asArray()->all();
            $playerVideo = lsPlayerSource::find()->where(['user_id'=>$uid,'type'=>0])->asArray()->all();
            $player['img'] = $playerImg;
            $player['video'] = $playerVideo;
        }


        $user = User::find()->select('id,nickname,username,is_distributor,is_partner,parent_id,level,ls_price')->where(['id'=>$uid]) ->asArray()->one();

        $player_count = Lashou::getSsdsPlayerCount();

        $current_player_info = Ssdsplayer::find()->where(['uid'=>\Yii::$app->user->identity->id]) ->asArray()->one();

        //是否已有围观手机号
        $has_viewer_mobile = Viewer::find()->where(['uid' => \Yii::$app->user->identity->id])->count();
        $has_viewer_mobile = $has_viewer_mobile > 0 ? true : false;

        //登录用户上传体脂
        $current_weight_logs = LsPlayerWeightLog::find()
            ->where(['user_id' => \Yii::$app->user->identity->id])
            ->orderBy('id desc')
            ->asArray()
            ->all();
        if($current_weight_logs){
            foreach ($current_weight_logs as $key => $value){
                $status = '';
                switch ($value['status']){
                    case 0:
                        $status = '未审核';
                        break;
                    case 1:
                        $status = '已通过';
                        break;
                    case 2:
                        $status = '已拒绝';
                        break;
                    case 3:
                        $status = '已覆盖';
                        break;
                }
                $value['status'] = $status;
                $value['show'] = false;

                $current_weight_logs[$key] = $value;
            }
        }


        $res = [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'player_info' => $player,
                'user_info' => $user,
                'player_count' => $player_count,
                'current_player_info' => $current_player_info,
                'has_viewer_mobile' => $has_viewer_mobile,
                'current_weight_logs' => $current_weight_logs,
                'current_share_result' => Lashou::getSsdsPlayerShareResult(),
            ],
        ];
        return $this->renderJson($res);
    }

    public function actionIndex(){
        $query = Ssdsplayer::find()->where([
        ]);
        $this->sort_order = \Yii::$app->request->get('vote_type',1);
        $this->keywords = \Yii::$app->request->get('keywords',1);
        $this->page = \Yii::$app->request->get('page',1);
        if ($this->sort_order == 0) {//综合排序
            $orderby = 'vote_sum DESC';
        }
        if ($this->sort_order == 1) {//投票排序
            $orderby = 'vote_viewer DESC';
        }
        if ($this->sort_order == 2) {//体脂排序
           $orderby = 'vote_weight DESC';
           $orderby = 'vote_viewer DESC';
        }
        if ($this->keywords){
            $query->andWhere(['LIKE', 'username', $this->keywords]);
            $query->orWhere(['uid' => intval($this->keywords)]);
        }

        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1, 'pageSize' => $this->limit]);
        $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy("$orderby")->asArray()->all();

        foreach ($list as $key => $item){
            $res_data = [];
            $res_data['url'] = $item['avatar'];
            $res_data['length'] = strlen($res_data['url']);
            $res_data['last'] = substr($res_data['url'], $res_data['length'] - 4, 4);
            if($res_data['last'] == '/132'){
                $res_data['new'] = substr($res_data['url'], 0, $res_data['length'] - 4);
                $res_data['new'] .= '/0';
                $list[$key]['avatar'] = $res_data['new'];
            }
        }

        $player_count = Lashou::getSsdsPlayerCount();

        $res = [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'row_count' => $count,
                'page_count' => $pagination->pageCount,
                'list' => $list,
                'keywords'=>$this->keywords,
                'player_count' => $player_count
            ],
            'sql' => $query->limit($pagination->limit)->offset($pagination->offset)->orderBy("$orderby")->createCommand()->getRawSql(),
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
        $time = time();
        $date = intval(date("Ymd", $time));

        $user_id = \Yii::$app->user->identity->id;
        $MUser = User::findOne($user_id);

        $player_user_id = \Yii::$app ->request->get('player_user_id',0);

        //判断是否是投票者

        $transaction = \Yii::$app->db->beginTransaction();

        try{
            //投票日志
            $vote = new Vote();
            $vote->uid = $user_id;
            $vote->player_id = $player_user_id;
            $vote->add_time = $time;
            $vote->add_date = $date;
            $vote->save();

            //选手票数加一
            $player = new Ssdsplayer();
            $player_info = Ssdsplayer::findOne(['uid'=>$player_user_id]);
            $player_info->updateCounters(['vote_viewer' => 1, 'vote_sum' => 1]);
            $player_info ->save();

            //投票得红包
            $hongbao_res = null;
            $hongbao_total =(float) LsBonusLog::find() -> where(['uid' => $user_id, 'type' => 1 ])->sum('val');
            if($hongbao_total <1000 && Lashou::canJoinActivity() && Lashou::hasNewerHongbao()) {
                $ls_price = rand(50, 80);

                if($ls_price + $hongbao_total > 1000){
                    $ls_price = 1000 - $hongbao_total;
                }

                $userinfo = User::findOne(['id' => $user_id]);
                $userinfo->updateCounters(['ls_price' => $ls_price / 100]);
                $userinfo->save();

                //红包日志
                $lsbonuslog = New LsBonusLog();
                $lsbonuslog->add_time = time();
                $lsbonuslog->type = 1;
                $lsbonuslog->extension_id = 3;
                $lsbonuslog->uid = $user_id;
                $lsbonuslog->val = $ls_price;
                $lsbonuslog->log = '投票获得随机金额的红包';
                $lsbonuslog->save();

                $hongbao_res = ['val'=>$ls_price/100];
            }

            $transaction->commit();
            $this->renderJson(array('code'=>0,'msg'=>'投票成功','data'=>['hongbao'=>$hongbao_res]));
        }catch(\Exception $e){
            $transaction->rollback();
            return $this->renderJson(["code" => $e->getCode(), "msg" => "今日已投票"]);
        }
    }

    /*
    as array 会把公共属性删除？
    */

    public function actionTest(){
        $goods = GoodsTest::find()->asArray()->all();
        foreach ($goods as $key => $value) {
            print_r($value);
            echo "hello33" . $value->wooran . "33hello";
        }

        /*$goods = new GoodsTest;
        print_r($goods);*/
        //print_r(json_decode(json_encode($goods), true));
    }

    public function actionUpdateWeight(){
        $time = time();
        $date = intval(date('Ymd', $time));
        $date_time = date('Y-m-d H:i:s', $time);

        $user_id = \Yii::$app->user->identity->id;
        $MUser = User::findOne($user_id);
        if(empty($MUser)){
            $this->renderJson([
                'code' => -1,
                'msg' => '请先登录'
            ]);
        }

        $video = empty($_REQUEST['video']) ? '' : htmlspecialchars(trim($_REQUEST['video']));
        if($video == ''){
            $this->renderJson([
                'code' => 922,
                'msg' => '视频地址不能为空'
            ]);
        }

        $weight = empty($_REQUEST['weight']) ? 0 : floatval($_REQUEST['weight']);
        if($weight <= 0){
            $this->renderJson([
                'code' => 923,
                'msg' => 'weight 必须大于 0'
            ]);
        }

        $transaction = \Yii::$app->db->beginTransaction();

        try{
            $weight_log = new LsPlayerWeightLog();
            $weight_log->user_id = $user_id;
            $weight_log->add_date_time = $date_time;
            $weight_log->add_date = $date;
            $weight_log->video_url = $video;
            $weight_log->weight = $weight;
            $weight_log->save();

            $transaction->commit();
            $this->renderJson([
                'data' => ['weight_log' => json_decode(json_encode($weight_log), true)]
            ]);
        }catch(\Exception $e){
            $transaction->rollBack();
            $this->renderJson([
                'code' => 921,
                'msg' => $e->getMessage()
            ]);
        }
    }

    public function actionRemoveImage(){
        $user_id = \Yii::$app->user->identity->id;
        $id = intval($_REQUEST['id']);
        $source = lsPlayerSource::find()->where(['user_id' => $user_id, 'id' => $id])->one();
        if($source->delete()){
            $this->renderJson([
                'msg' => 'success'
            ]);
        }else{
            $this->renderJson([
                'code' => 400,
                'msg' => '操作失败'
            ]);
        }
    
}


    public function actionDay(){
        $day = new Day();
        $user_id = \Yii::$app->user->identity->id;
        $day->user_id = $user_id;
        $day->status = 1;
        $day->day = time();
        $day->addtime = time();
        $day->sid = \Yii::$app ->request->get('sid',0);
        $day->store_id = $this->store_id;
        $res = $day->save();
        if($res){
            $this->renderJson([
                'code' => 0,
                'msg' => '成功'
            ]);
        }else{
            $this->renderJson([
                'code' => 1,
                'msg' => '失败'
            ]);
        }
    }

    public function actionDayindex(){
        $user_id = \Yii::$app->user->identity->id;
        $count = Day::find()->where(['user_id'=>$user_id])->count();
        $sid = ceil($count/10);
        if($sid == 0){
            $sid = 1;
        }
        var_dump($sid);
    }

    public function actionTimelineTask(){
        if(!LsSsds::getSsdsCanCash()){
            $this->renderJson([
                'code' => 400,
                'msg' => '该活动已修改规则'
            ]);
        }

        $this->renderJson(LsSsds::getTimelineTask());
    }
    public function actionTimelineDo(){
        if(!LsSsds::getSsdsCanCash()){
            $this->renderJson([
                'code' => 400,
                'msg' => '该活动已修改规则'
            ]);
        }

        $this->renderJson(LsSsds::doTimelineTask());
    }
    public function actionTimelineCash(){
        $stage = intval($_REQUEST['stage']);
        $user_id = \Yii::$app->user->identity->id;

        if(!LsSsds::getSsdsCanCash()){
            $this->renderJson([
                'code' => 400,
                'msg' => '该活动已修改规则'
            ]);
        }

        $player = Ssdsplayer::find()->where(['uid' => $user_id])->one();
        if(empty($player) || empty($player->timeline_task)){
            $this->renderJson([
                'code' => 400,
                'msg' => '您未参赛，或没有点击记录'
            ]);
        }
        $result_task = json_decode($player->timeline_task, true);
        if($result_task['time_line_stage'] != $stage || $result_task['time_line_tasks'][count($result_task['time_line_tasks']) - 1]['status'] != LsSsds::TIMELINE_TASK_STATUS_DONE){
            $this->renderJson([
                'code' => 400,
                'msg' => '您有未完成任务，暂时不能提交',
            ]);
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try{
            $cond = ['in', 'goods_id', [199, 205]];
            $goods_id = OrderDetail::find()->where($cond)->andWhere(['user_id'=>$user_id])->asArray()->all();
            foreach ($goods_id as $v){
                $order = Order::find()->where(array('id'=>$v['order_id'],'is_confirm'=>1))->asArray()->one();
                if(!empty($order)){
                    $goods_id = OrderDetail::find()->where(['order_id'=>$order['id']])->asArray()->one();
                }
            }
            if($goods_id['goods_id'] == 199){
                if($stage == 1){
                    $price = 108;
                }
                $price = 108;
            }elseif($goods_id['goods_id'] == 205){
                if($stage == 1){
                    $price = 108-11;
                }
                $price = 108-11;
            }

            if($stage == 3){
                $price = 0;
            }
            
            $cash = new LsPlayerCash();
            $cash->user_id = $user_id;
            $cash->stage = $stage;
            $cash->add_time = time();
            $cash->order_no = $this->getOrderNo();
            $cash->is_delete = 0;
            $cash->status = 0;
            $cash->price = $price;
            $cash->store_id = $this->store_id;
            $cash->type = 0;
            $cash->pay_time = 0;
            $cash->save();
            $transaction->commit();

            $this->renderJson([
                'data' => 'success',
            ]);
        }catch (\Exception $e){
            $transaction->rollBack();
            $this->renderJson([
                'code' => 500,
                'msg' => '您已提交申请',
            ]);
        }
    }
    private function getOrderNo()
    {
        $order_no = null;
        while(true){
            $order_no = date('YmdHis').rand(100000,999999);
            $exist_order_no = LsPlayerCash::find()->where(['order_no'=>$order_no])->exists();
            if(!$exist_order_no)
                break;
        }
        return $order_no;
    }

    public function actionViewInfo(){
        $user_id = intval($_REQUEST['user_id']);

        $player = Ssdsplayer::find()->where(['uid' => $user_id])->asArray()->one();
        if(empty($player)){
            $this->renderJson([
               'code' => 400,
               'msg' => '您没有参赛'
            ]);
        }

        $weight_log = LsPlayerWeightLog::find()
            ->where(['user_id' => $user_id, 'status' => 1])
            ->orderBy('id DESC')
            ->asArray()
            ->all();
        foreach($weight_log as $key => $value){
            $add_date_time = explode(' ', $value['add_date_time']);
            $add_date_time = explode('-', $add_date_time[0]);

            $add_date_time = $add_date_time[1] . '月' . $add_date_time[2] . '号';
            $value['add_date'] = $add_date_time;
            $weight_log[$key] =  $value;
        }

        $this->renderJson([
            'data' => [
                'player_info' => $player,
                'weight_log' => $weight_log,
            ]
        ]);
    }

    public function actionCheckV2(){
        $this->renderJson([
            'data' => [
                'is_v2' => LsSsds::getIsSsdsV2(),
            ],
        ]);
    }

    public function actionHasTimelineTask(){
        $this->renderJson([
            'data' => [
                'has_timeline_task' => LsSsds::getSsdsCanCash(),
            ],
        ]);
    }
}