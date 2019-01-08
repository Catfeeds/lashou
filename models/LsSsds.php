<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/16
 * Time: 17:35
 */

namespace app\models;


class LsSsds
{
    const TIMELINE_TASK_STATUS_TODO = -1;
    const TIMELINE_TASK_STATUS_DONE = 1;
    const TIMELINE_TASK_STATUS_NOT_DONE = 0;

    const SSDS_V2_START_TIME = '2018-06-26 00:00:00';
    const SHIPPING_FEE_GOODS = 226;

    public static function getTimelineTask(){
        $result_task = null;

        $default_task_1 = [
            'time_line_stage' => 1,
            'time_line_stage_name' => '第一阶段',
            'time_line_tips' => '按公司要求完成朋友圈晒圈分享连续10天',
            'time_line_tasks' => [
                ['date' => '第一天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第二天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第三天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第四天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第五天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第六天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第七天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第八天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第九天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
            ]
        ];

        $default_task_2 = [
            'time_line_stage' => 2,
            'time_line_stage_name' => '第二阶段',
            'time_line_tips' => '按公司要求完成朋友圈晒圈分享连续15天',
            'time_line_tasks' => [
                ['date' => '第一天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第二天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第三天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第四天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第五天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第六天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第七天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第八天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第九天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十一天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十二天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十三天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十四天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十五天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
            ]
        ];

        $default_task_3 = [
            'time_line_stage' => 3,
            'time_line_stage_name' => '第三阶段',
            'time_line_tips' => '按公司要求完成朋友圈晒圈分享连续15天',
            'time_line_tasks' => [
                ['date' => '第一天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第二天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第三天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第四天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第五天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第六天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第七天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第八天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第九天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十一天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十二天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十三天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十四天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十五天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
            ]
        ];

        $user_id = \Yii::$app->user->identity->id;
        $user_info = User::findOne($user_id);
        if(empty($user_info)){
            return [
                'code' => -1,
                'msg' => '请先登录'
            ];
        }

        $player = Ssdsplayer::find()->where(['uid' => $user_id])->one();
        if(empty($player)){
            return [
                'code' => 400,
                'msg' => '您没有参加比赛'
            ];
        }

        //1.如果有数据则取，否则默认第一阶段
        if(!empty($player->timeline_task)){
            $result_task = json_decode($player->timeline_task, true);
        }else{
            $result_task = $default_task_1;
        }

        $join_time = strtotime(date('Y-m-d 00:00:00', $player->add_time));
        $time = time();

        //2. 如果第一阶段最后一天的任务已经完成， 并且已经申请提现，则第二阶段任务开启
        if($result_task['time_line_stage'] == 1 && $result_task['time_line_tasks'][9]['status'] == self::TIMELINE_TASK_STATUS_DONE){
            $cash_log = LsPlayerCash::find()
                ->where([
                    'user_id' => $user_id,
                    'stage' => 1
                ])
                ->one();
            if(!empty($cash_log)){
                $result_task = $default_task_2;
            }
        }

        //2.1 如果第二阶段最后一天的任务已经完成， 并且已经申请提现，则第三阶段任务开启
        if($result_task['time_line_stage'] == 2 && $result_task['time_line_tasks'][14]['status'] == self::TIMELINE_TASK_STATUS_DONE){
            $cash_log = LsPlayerCash::find()
                ->where([
                    'user_id' => $user_id,
                    'stage' => 2
                ])
                ->one();
            if(!empty($cash_log)){
                $result_task = $default_task_3;
            }
        }

        //additional 如果是第二阶段，并且只有十天任务，则加上5天
        if($result_task['time_line_stage'] == 2 && count($result_task['time_line_tasks']) == 10){
            $result_task['time_line_tasks'] = array_merge($result_task['time_line_tasks'], [
                ['date' => '第十一天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十二天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十三天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十四天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
                ['date' => '第十五天', 'status' => self::TIMELINE_TASK_STATUS_TODO],
            ]);
        }

        //3. 哪些天可以点
        $days = min(10, ceil(($time - $join_time) / (3600 * 24)));
        if($result_task['time_line_stage'] == 2){
            $days = min(15, ceil(($time - $join_time) / (3600 * 24)) - 10);
        }
        if($result_task['time_line_stage'] == 3){
            $days = min(15, ceil(($time - $join_time) / (3600 * 24)) - 10 - 15);
        }
        for($i = 0; $i < $days; $i++){
            if($result_task['time_line_tasks'][$i]['status'] == self::TIMELINE_TASK_STATUS_TODO){
                $result_task['time_line_tasks'][$i]['status'] = self::TIMELINE_TASK_STATUS_NOT_DONE;
            }
        }



        $player->timeline_task = json_encode($result_task);
        $player->save();

        return [
            'data' => ['task' => $result_task],
        ];
    }

    public static function doTimelineTask(){
        $stage = intval($_REQUEST['stage']);
        $index = intval($_REQUEST['index']);

        $user_id = \Yii::$app->user->identity->id;
        $player = Ssdsplayer::find()->where(['uid' => $user_id])->one();
        if(!empty($player) && !empty($player->timeline_task)){
            $task = json_decode($player->timeline_task, true);
            if($task['time_line_stage'] == $stage){
                $task['time_line_tasks'][$index]['status'] = self::TIMELINE_TASK_STATUS_DONE;
            }
            $player->timeline_task = json_encode($task);
            $player->save();

            return [
                'data' => ['task' => $task],
            ];
        }else{
            return [
                'code' => 404,
                'msg' => '找不到记录'
            ];
        }
    }


    public static function getIsSsdsV2(){
        $time = time();
        if($time > strtotime(self::SSDS_V2_START_TIME)){
            return true;
        }

        $test_ssds_v2 = empty($_REQUEST['test_ssds_v2']) ? 0 : intval($_REQUEST['test_ssds_v2']);
        if($test_ssds_v2 > 0){
            return true;
        }

        return false;
    }

    public static function getSsdsCanCash(){
        $test_ssds_v2 = empty($_REQUEST['test_ssds_v2']) ? 0 : intval($_REQUEST['test_ssds_v2']);
        if($test_ssds_v2 > 0){
            return false;
        }

        $user_id = \Yii::$app->user->identity->id;
        $player = Ssdsplayer::find()->where(['uid' => $user_id])->one();

        if($player->add_time < strtotime(self::SSDS_V2_START_TIME)){
            return true;
        }

        return false;
    }
}