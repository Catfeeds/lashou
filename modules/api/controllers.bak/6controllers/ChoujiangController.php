<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8
 * Time: 14:15
 */

namespace app\modules\api\controllers;

use app\models\LsChoujiangPrize;
use app\models\LsChoujiangInfo;

class ChoujiangController extends Controller
{   
    /*
    BUG:没有根据count 限制prize；客户端跨天提交
    */
    private $max_share_count = 2;

    private $prize_list = [
          [
            'prize_id' => 501,
            'prize_type' => 2, //0.无 1.红包 2.积分
            'prize_val' => 58,
            'image' => "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
          ],
          [
            'prize_id' => 502,
            'prize_type' => 2, //0.无 1.红包 2.积分
            'prize_val' => 58,
            'image' => "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
          ],
          [
            'prize_id' => 503,
            'prize_type' => 2, //0.无 1.红包 2.积分
            'prize_val' => 58,
            'image' => "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
          ],
          [
            'prize_id' => 504,
            'prize_type' => 2, //0.无 1.红包 2.积分
            'prize_val' => 58,
            'image' => "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
          ],
          [
            'prize_id' => 505,
            'prize_type' => 2, //0.无 1.红包 2.积分
            'prize_val' => 58,
            'image' => "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
          ],
          [
            'prize_id' => 506,
            'prize_type' => 2, //0.无 1.红包 2.积分
            'prize_val' => 58,
            'image' => "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
          ],
          [
            'prize_id' => 507,
            'prize_type' => 2, //0.无 1.红包 2.积分
            'prize_val' => 58,
            'image' => "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
          ],
          [
            'prize_id' => 508,
            'prize_type' => 2, //0.无 1.红包 2.积分
            'prize_val' => 58,
            'image' => "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
          ]
        ];

    public function actionMorePrize(){
        $is_share = \Yii::$app->request->get('is_share', false);
        $info = $this->getTodayInfo($is_share);


        $prize = $this->getPrize();
        $res = [
            'choujiang_count' => $info->choujiang_count,
            'left_share_count' => $info->left_share_count,
            'prize' => $prize
        ];
        return $this->renderJson(["data" => $res]);
    }

    public function actionIndex(){
        $info = $this->getTodayInfo();

        $left_share_count = $info->left_share_count;
        $choujiang_count = $info->choujiang_count;
        $prize = $this->getPrize();

        $res = [
            'prize_list' => $this->prize_list,
            'mine' => [
              'left_share_count' => $left_share_count,
              'choujiang_count' => $choujiang_count,
              'prize' => $prize,
            ],
        ];
        return $this->renderJson(["data" => $res]);
    }

    public function actionActive(){
      $user_id = \Yii::$app->user->id;
      $prize_id = \Yii::$app->request->get('prize_id');

      $info_date = intval(date('Ymd', time()));
      $info = LsChoujiangInfo::find()->where(['user_id' => $user_id, 'info_date' => $info_date])->one();
      $info->choujiang_count -= 1; 
      $info->save();

      $prize = LsChoujiangPrize::find()->where(['user_id' => $user_id, "prize_id" => $prize_id])->asArray()->one();
      if(!empty($prize)){
        return $this->renderJson([
          "data" => ['prize' => $prize]
        ]);
      }else{
        return $this->renderJson([
          "code" => 800,
          "msg" => "没找到记录"
        ]);
      }
    }

    private function getPrize(){
      $index = rand(0, 7);
      $prize = $this->prize_list[$index];

      //删除可能对激活有影响
      //$user_id = \Yii::$app->user->id;
      //LsChoujiangPrize::deleteAll(['user_id' => $user_id]);

      $prize_log = new LsChoujiangPrize;
      $prize_log->user_id = \Yii::$app->user->id;
      $prize_log->add_time = time();
      $prize_log->prize_id = $prize['prize_id'];

      $prize_log->save();

      return $prize;
    }

    private function getTodayInfo($is_share = false){
      $user_id = \Yii::$app->user->id;
      $info_date = intval(date('Ymd', time()));

      $info = LsChoujiangInfo::find()->where(['user_id' => $user_id, 'info_date' => $info_date])->one();
      if(empty($info)){
        $info = new LsChoujiangInfo;

        $info->user_id = $user_id;
        $info->info_date = $info_date;

        if($is_share){
          $info->choujiang_count = 1 + 1;
          $info->left_share_count = $this->max_share_count - 1;
        }else{
          $info->choujiang_count = 1;
          $info->left_share_count = $this->max_share_count;
        }

        $info->save();
      }else{
        if($is_share && $info->left_share_count > 0){
          $info->choujiang_count += 1;
          $info->left_share_count = $info->left_share_count - 1;
          $info->save();
        }
      }

      return $info;
    }
}