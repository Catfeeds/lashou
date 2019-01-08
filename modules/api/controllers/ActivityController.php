<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/8/4
 * Time: 上午8:24
 */

namespace app\modules\api\controllers;


use app\models\ActivityShare;
use app\models\ActivityShareClick;
use app\models\ActivityUser;
use app\models\PtOrder;
use app\models\PtOrderDetail;
use app\models\User;
use app\modules\api\models\ActivityConfig;

class ActivityController extends Controller
{
    public function actionInfo(){
        $user_id = \Yii::$app->user->identity->id;


        $time = time();

        $status = 0;
        $start_left_time = 0;
        $end_left_time = 0;

        if($time >= strtotime(ActivityConfig::END_DATE_TIME)){
            $status = 2;
            $start_left_time = 0;
            $end_left_time = 0;
        }elseif($time >= strtotime(ActivityConfig::START_DATE_TIME)){
            $status = 1;
            $start_left_time = 0;
            $end_left_time = strtotime(ActivityConfig::END_DATE_TIME) - $time;
        }else{
            $status = 0;
            $start_left_time = strtotime(ActivityConfig::START_DATE_TIME) - $time;
            $end_left_time = strtotime(ActivityConfig::END_DATE_TIME) - $time;
        }

        $pintuan_id = ActivityConfig::PINGTUAN_ID;

        return $this->renderJson([
            'data' => [
                'status' => $status,
                'start_left_time' => $start_left_time,
                'end_left_time' => $end_left_time,
                'pintuan_id' => $pintuan_id,
            ]
        ]);
    }

    public function actionStatus(){
        return $this->renderJson([
            'data' => [
                'join_person' => 11723,
                'share_person' => 13117,
            ]
        ]);

        $share_person = (new \yii\db\Query())
            ->select('id')
            ->from('hjmallind_activity_share')
            ->count();

        $join_person = PtOrderDetail::find()
            ->where(['goods_id' => ActivityConfig::PINGTUAN_ID])
            ->count();


        return $this->renderJson([
            'data' => [
                'join_person' => $join_person,
                'share_person' => $share_person
            ]
        ]);
    }

    public function actionShare(){
        $user_id = \Yii::$app->user->identity->id;

        $entry = [
            'user_id' => $user_id,
            'add_time' => date('Y-m-d H:i:s'),
        ];

        if(!empty($_REQUEST['share_type'])){
            $entry['share_type'] = intval($_REQUEST['share_type']);
        }

        \Yii::$app->db->createCommand()->insert('hjmallind_activity_share', $entry)->execute();

        $entry['id'] = \Yii::$app->db->getLastInsertID();

        return $this->renderJson([
            'data' => [
                'share' => $entry,
            ]
        ]);
    }

    public function actionClickShare(){
        $user_id = \Yii::$app->user->identity->id;

        $entry = [
            'user_id' => $user_id,
            'add_time' => date('Y-m-d H:i:s'),
            'share_user_id' => intval($_REQUEST['share_user_id']),
        ];

        \Yii::$app->db->createCommand()->insert('hjmallind_activity_share_click', $entry)->execute();

        $entry['id'] = \Yii::$app->db->getLastInsertID();

        return $this->renderJson([
            'data' => [
                'share_click' => $entry,
            ]
        ]);
    }

    public function actionAdd(){
        $user_id = \Yii::$app->user->identity->id;

        if(empty($_REQUEST['share_user_id']) && !in_array($user_id, ActivityConfig::getCanShareActivityUsers())){
            return $this->renderJson([
                'code' => 501,
                'msg' => '不是分享链接，且不在允许用户范围内'
            ]);
        }


        $transaction = \Yii::$app->db->beginTransaction();
        try{
            //投票日志
            $entry = new ActivityUser();
            $entry->user_id = $user_id;
            $entry->add_time = date('Y-m-d H:i:s');
            $entry->save();

            $transaction->commit();
            $this->renderJson(array('code'=>0,'msg'=>'加入成功','data'=>['user_id'=>$user_id]));
        }catch(\Exception $e){
            $transaction->rollback();
            return $this->renderJson(["code" => $e->getCode(), "msg" => "已加入"]);
        }
    }

    public function actionTeam(){
        $login_user_id = \Yii::$app->user->identity->id;

        $user_id = empty($_REQUEST['user_id']) ? $login_user_id : intval($_REQUEST['user_id']);

        $query = ActivityShareClick::find()->alias("log")
            ->leftJoin(ActivityUser::tableName() . " as au", "au.user_id = log.user_id")
            ->leftJoin(User::tableName() . ' as u', 'u.id = log.user_id')
            ->where(['log.share_user_id' => $user_id])
            ->andWhere(['<>', 'log.user_id', $user_id])
            ->groupBy("log.user_id")
            ->select('log.add_time, log.user_id, u.nickname, u.avatar_url, u.mobile');

        $teams = $query->asArray()->all();

        foreach($teams as $key => $team){
            $buy_info = PtOrderDetail::find()->alias('og')
                ->leftJoin(PtOrder::tableName() . " o ", " o.id = og.order_id")
                ->where(['og.goods_id' => ActivityConfig::PINGTUAN_ID, 'o.user_id' => $team['user_id']])
                //->andWhere(['>', 'o.parent_id', 0])
                ->count();
            $team['buy_status'] = $buy_info > 0 ? "已购买" : "未购买";

            $ping_info = PtOrderDetail::find()->alias('og')
                ->leftJoin(PtOrder::tableName() . " o ", " o.id = og.order_id")
                ->where(['og.goods_id' => ActivityConfig::PINGTUAN_ID, 'o.user_id' => $team['user_id']])
                ->andWhere(['o.parent_id' => 0])
                ->select('o.limit_time, o.status')
                ->asArray()
                ->one();
            /*$ping_sql = PtOrderDetail::find()->alias('og')
                ->leftJoin(PtOrder::tableName() . " o ", " o.id = og.order_id")
                ->where(['og.goods_id' => ActivityConfig::PINGTUAN_ID, 'o.user_id' => $team['user_id']])
                ->andWhere(['o.parent_id' => 0])
                ->select('o.limit_time, o.status')
                ->createCommand()
                ->getRawSql();
            $team['ping_Sql'] = $ping_sql;
            exit($ping_sql);*/
            //print_r($ping_info);exit();
            $timediff = $ping_info['limit_time'] - time();
            $groupFail = "进行中";//0
            if($ping_info['status'] == 4){
                $groupFail = "失败";     // 拼团失败 2
            }elseif($timediff<=0||$ping_info['status']==3){
                $groupFail = "成功";     // 拼团成功 1
            }

            $team['pin_status'] = empty($ping_info) ? "未拼团" : "已拼团" . $groupFail;

            $share_count = ActivityShare::find()->where(['user_id' => $team['user_id']])->count();
            $team['share_status'] = $share_count > 0 ? "已分享" : "未分享";

            $mobile = ActivityUser::findOne(['user_id' => $team['user_id']]);
            if(!empty($mobile) && !empty($mobile->mobile)){
                $team['mobile'] = $mobile->mobile;
            }

            $teams[$key] = $team;
        }


        $share_count = ActivityShare::find()
            ->where(['user_id' => $login_user_id])
            ->count();
        $share_click_count = ActivityShareClick::find()
            ->where(['share_user_id' => $login_user_id])
            ->count();

        return $this->renderJson([
            'data' => [
                'banner' => 'https://api.anmeila.com.cn/statics/images/activity/cover-banner.jpg',
                'list' => $teams,
                'share_count' => $share_count,
                'share_click_count' => $share_click_count,
                'share_status' => "分享数：" . $share_count . "  点击数：" . $share_click_count
            ]
        ]);
    }
}