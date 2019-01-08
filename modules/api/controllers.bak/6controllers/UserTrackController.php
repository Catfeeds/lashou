<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/7/29
 * Time: 下午9:29
 */

namespace app\modules\api\controllers;


use app\models\User;
use yii\db\Exception;

class UserTrackController extends Controller
{
    public function actionTrack(){
        $user = User::findOne(['id' => \Yii::$app->user->identity->id]);

        //event_name
        $event_name = empty($_REQUEST['event_name']) ? "" : trim($_REQUEST['event_name']);
        if (empty($event_name)){
            $this->renderJson([
                'code' => 501,
                'msg' => "no event name"
            ]);
        }

        //event_params 必须是一维数组的json字符串
        $event_params = empty($_REQUEST['event_params']) ? "" : trim($_REQUEST['event_params']);
        $keywords = "";
        if(!empty($event_params)){
            $params = json_decode($event_params, true);
            foreach ($params as $key => $value){
                $keywords  .= $key . ":" . $value . "  ";
            }
        }

        //from_track_id 默认为0
        $from_track_id = empty($_REQUEST['from_track_id']) ? 0 : intval($_REQUEST['from_track_id']);

        try {
            $track = [
                'user_id' => $user->id,
                'user_name' => $user->username,
                'nickname' => $user->nickname,
                'add_time' => date('Y-m-d H:i:s'),
                'event_name' => htmlspecialchars($event_name),
                'event_params' => htmlspecialchars($event_params),
                'keywords' => htmlspecialchars($keywords),
                'from_track_id' => $from_track_id
            ];

            \Yii::$app->db->createCommand()->insert('hjmallind_user_track', $track)->execute();

            $track['id'] = \Yii::$app->db->getLastInsertID();

            return $this->renderJson([
                'data' => [
                    'track' => $track,
                ]
            ]);
        } catch (Exception $e) {
            $this->renderJson([
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ]);
        }
    }
}