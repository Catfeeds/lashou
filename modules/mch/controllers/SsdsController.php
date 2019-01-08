<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/14
 * Time: 13:58
 */

namespace app\modules\mch\controllers;

use app\models\Ssdsplayer;
use app\models\LsPlayerWeightLog;
use app\modules\mch\models\SsdsVideoListForm;

class SsdsController extends Controller
{
    public function actionVideo(){
        $form = new SsdsVideoListForm();
        $form->attributes = \Yii::$app->request->get();
        $data = $form->getList();

        //print_r($data); exit();

        return $this->render('video', [
            'row_count' => $data['row_count'],
            'pagination' => $data['pagination'],
            'list' => $data['list'],
        ]);
    }

    public function actionVideoReview(){
        $status = \Yii::$app->request->post('status');
        if(!in_array($status, [1, 2])){
            return $this->renderJson([
                'code' => 601,
                'msg' => '参数不合法',
            ]);
        }

        $id = \Yii::$app->request->post('id', 0);
        $weight_log = LsPlayerWeightLog::find()->where(['id' => $id, 'status' => 0])->one();
        if(empty($weight_log)){
            return $this->renderJson([
                'code' => 404,
                'msg' => '找不到资源',
            ]);
        }

        LsPlayerWeightLog::updateAll(['status' => 3], [
            'user_id' => $weight_log->user_id,
            'add_date' => $weight_log->add_date,
        ]);

        $weight_log->status = $status;
        $weight_log->save();

        //更新选手体脂票数
        $vote_ticket_log = [];
        if($status == 1){
            $weight_first_query = LsPlayerWeightLog::find()
                ->where([
                    'user_id' => $weight_log->user_id,
                    'status' => 1,
                    ])
                ->andWhere(["<>", "id", $weight_log->id])
                ->orderBy('id ASC');
            $vote_ticket_log['sql'] = $weight_first_query->createCommand()->getRawSql();
            $weight_first = $weight_first_query->one();
            if($weight_first){
                $vote_ticket_log['entity_id'] = $weight_first->id;

                $weight_first = $weight_first->weight;
                if($weight_first > 0 && $weight_first >= $weight_log->weight){
                    $vote = ($weight_first - $weight_log->weight)/$weight_first;
                    $vote = intval($vote * 100);

                    $player = Ssdsplayer::find()->where(['uid' => $weight_log->user_id])->one();
                    $player->vote_sum += $vote;
                    $player->vote_weight = $vote;
                    $player->save();
                }
            }
        }

        return $this->renderJson([
            'code' => 0,
            'msg' => '审核成功',
            'vote_ticket_log' => $vote_ticket_log,
        ]);
    }
}