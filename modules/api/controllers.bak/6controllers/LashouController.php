<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/5/29
 * Time: 17:00
 */

namespace app\modules\api\controllers;


use app\models\Lashou;
use app\models\LsBonusLog;
use app\models\LsQiandaoLog;
use app\models\LsTplMessage;
use app\models\LsWechatFormId;
use app\models\User;
use yii\base\Exception;

class LashouController extends Controller
{
    public function actionQiandao(){
        //test sql:用户表修改积分 和 总积分 和 连续签到天数 和 总红包，bonus log 表去掉记录，qiandao log 去掉


        $time = time();
        $user_id = \Yii::$app->user->identity->id;
        $MUser = User::findOne($user_id);

        $last_qiandao = LsQiandaoLog::find()
            ->where(["user_id" => $user_id])
            ->orderBy('id desc')
            ->one();

        //1.判断今日有无签到
        $date = intval(date("Ymd", $time));
        if($last_qiandao && $last_qiandao->add_date >= $date){
            return $this->renderJson([
                'code' => 500,
                'msg' => '今日已经签到'
            ]);
        }

        $bonus_list = [];


        $transaction = \Yii::$app->db->beginTransaction();
        try{
            //2.赠送积分
            $integral = rand(3, 8);
            $MUser->total_integral += $integral;
            $MUser->integral += $integral;
            if($MUser->save()){
                $Bonus_log = new LsBonusLog();
                $Bonus_log->uid = $user_id;
                $Bonus_log->val = $integral;
                $Bonus_log->add_time = $time;
                $Bonus_log->type = 2;
                $Bonus_log->log = "签到赠送积分";
                $Bonus_log->save();

                $bonus_list[] = [
                    "type" => 2,
                    "val" => $integral,
                ];
            }else{
                $integral = 0;
            }

            //3.赠送红包
            $has_hongbao = LsBonusLog::find()->where(['uid' => $user_id, 'type' => 1])->sum('val');
            //$hongbao = rand(5, 10);
            $hongbao = 10;
            $hongbao = min($hongbao, 1000 - $has_hongbao);
            if($hongbao > 0 && Lashou::canJoinActivity() && Lashou::hasNewerHongbao()){
                $Bonus_log = new LsBonusLog();
                $Bonus_log->uid = $user_id;
                $Bonus_log->val = $hongbao;
                $Bonus_log->add_time = $time;
                $Bonus_log->type = 1;
                $Bonus_log->extension_id = 3;
                $Bonus_log->log = "签到赠送红包";
                $Bonus_log->save();

                $MUser->ls_price += $hongbao / 100;
                $MUser->save();

                $bonus_list[] = [
                    "type" => 1,
                    "val" => $hongbao / 100,
                ];
            }

            //4.记录连续签到天数
            //确保服务器无脏数据：签到时间 顺序倒挂---测试情况可能存在
            $is_coninuous = false;

            $yesterday = intval(date("Ymd", strtotime('-1 day', $time)));
            if(!empty($last_qiandao) && $last_qiandao->add_date == $yesterday){
                $is_coninuous = true;
            }

            if($is_coninuous){
                $MUser->continuous_qiandao_day += 1;
                $MUser->save();
            }else{
                $MUser->continuous_qiandao_day = 0;
                $MUser->save();
            }

            //5.记录签到日志
            $log = new LsQiandaoLog();
            $log->user_id = $user_id;
            $log->add_time = $time;
            $log->add_date_time = date("Y-m-d H:i:s", $time);
            $log->add_date = intval(date("Ymd", $time));
            $log->bonus = json_encode($bonus_list);
            $log->save();

            $transaction->commit();
        }catch (\Exception $e) {
            $transaction->rollback();
            return $this->renderJson(["code" => $e->getCode(), "msg" => $e->getMessage()]);
        }

        $res = [
            'bonus_list' => $bonus_list,
            //'has_qiandao_sql' => $has_qiandao = LsQiandaoLog::find()->where(["user_id" => $user_id, "add_date" => $date])->createCommand()->getRawSql(),
            //'has_hongbao_sql' => LsBonusLog::find()->where(['uid' => $user_id, 'type' => 1])->createCommand()->getRawSql()
        ];

        //发送模板消息
        if(isset($_REQUEST['form_id']) && !empty($_REQUEST['form_id'])){
            $wechat_form_id = new LsWechatFormId();
            $wechat_form_id->setParams($MUser->wechat_open_id, $_REQUEST['form_id']);

            $add_time = date('Y年m月d日 H时i分s秒');
            $remark = [];
            foreach ($bonus_list as $bonus){
                switch ($bonus['type']){
                    case 1:
                        $remark[] = $bonus['val'] . "元红包";
                        break;
                    case 2:
                        $remark[] = $bonus['val'] . "积分";
                        break;
                }
            }

            LsTplMessage::send_tpl_message_by_qiandao($wechat_form_id, $add_time, "恭喜您获得 " . implode(',', $remark));
        }

        return $this->renderJson(["data" => $res]);
    }

    //form_id
    public function actionAddFormIdNewerBonus(){
        $user_id = \Yii::$app->user->identity->id;
        $MUser = User::findOne($user_id);

        $form_id = $_REQUEST['form_id'];

        $wechat_form_id = new LsWechatFormId();
        $wechat_form_id->setParams($MUser->wechat_open_id, $form_id);
        $wechat_form_id->extension_code = LsTplMessage::MESSAGE_TYPE_NEWER_BONUS;
        $wechat_form_id->user_id = $user_id;
        $wechat_form_id->add_date_time = date('Y-m-d H:i:s');
        $wechat_form_id->save();

        return $this->renderJson([
            'data' => [
                'form_id' => $wechat_form_id,
            ]
        ]);
    }
}