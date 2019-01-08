<?php
/**
 * Created by PhpStorm.
 * User: fbi
 * Date: 2018/5/18 0018
 * Time: 17:04
 */
namespace app\modules\api\controllers\activity;

use app\models\lsPlayerSource;
use app\models\LsPlayerWeightLog;
use app\models\Ssdsplayer;
use app\models\UploadForm;
use app\models\User;
use app\modules\api\controllers\Controller;

class VoteController extends Controller
{


    //帮助拆红包
    public function actionUpVideo()
    {
        $time = time();
        $date = intval(date('Ymd', $time));
        $date_time = date('Y-m-d H:i:s', $time);



        $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        $MUser = User::findOne($user_id);
        if(empty($MUser)){
            $this->renderJson([
                'code' => -1,
                'msg' => '请先登录'
            ]);
        }

        /*$weight_log = LsPlayerWeightLog::find()->where([
            'user_id' => $user_id,
            'add_date' => $date
        ])->one();
        if($weight_log){
            $this->renderJson([
                'code' => 925,
                'msg' => '今日已上传'
            ]);
        }*/

        $upload = new UploadForm();
        $upload_rel = $upload->saveVideo();
        if($upload_rel['code'] == 0){
            $fileVideo = new lsPlayerSource();
            $fileVideo->val = $upload_rel['data']['url'];
            $fileVideo->store_id = 1;
            $fileVideo->user_id = $user_id;
            $fileVideo->type = 0;
            $fileVideo->addtime = $time;
            $fileVideo->save();

            $this->renderJson([
                'data' => ['video' => [
                    'val' => $fileVideo->val,
                    'user_id' => $fileVideo->user_id,
                ]],
            ]);
        }else{
            $this->renderJson([
                'code' => 926,
                'msg' => '上传失败'
            ]);
        }

        /*$fileVideo = new lsPlayerSource();

        $store_id = $this->store->id;
        $user_id = \Yii::$app->request->get()['user_id'];
        $fileVideoMenber = $fileVideo->findOne(['store_id'=>$store_id,'user_id'=>$user_id,'type'=>0]);
        if(empty($fileVideoMenber)){
            $form = new UploadForm();
            $res = $form->saveVideo();


            if($res['code'] == 0){
                $fileVideo->val = $res['data']['url'];
                $fileVideo->store_id = $store_id;
                $fileVideo->user_id = $user_id;
                $fileVideo->type = 0;
                $fileVideo->addtime = time();
                $fileVideo->save();
            }
            $this->renderJson([
                'data' => ['video' => $fileVideo],
            ]);
        }else
            $this->renderJson(['code'=>1,'msg'=>'您已经上传过了']);*/

    }

    public function actionEdit()
    {
        $ssdsplayer = new Ssdsplayer();
        $store_id = $this->store->id;
        $user_id = \Yii::$app->user->identity->id;
        $postData = \Yii::$app->request->post();
        $res = $ssdsplayer->findOne([/*'store_id'=>$store_id,*/'uid'=>$user_id]);

        if($res) {
            //$fileVideo->store_id = $store_id;
            $res->uid = $user_id;
            //$ssdsplayer->is_del = 0;
            $res->name = $postData['name'];
            $res->declaration = $postData['declaration'];
            $this->renderJson($res->save());
        }else
            $this->renderJson(['code'=>1,'msg'=>'用戶不存在']);




    }

    public function actionUpImage()
    {
        $fileVideo = new lsPlayerSource();
        $store_id = $this->store->id;
        $user_id = \Yii::$app->request->get()['user_id'];


        $form = new UploadForm();
        $res = $form->saveImage();

        if($res['code'] == 0) {
            $fileVideo->val = $res['data']['url'];
            $fileVideo->type = 1;
            $fileVideo->store_id = $store_id;
            $fileVideo->user_id = $user_id;
            $fileVideo->addtime = time();
            $fileVideo->save();

            $res['file'] = [
                'id' => $fileVideo->id,
                'type' => $fileVideo->type,
                'store_id' => $fileVideo->store_id,
                'user_id' => $fileVideo->user_id,
                'addtime' => $fileVideo->addtime,
                'val' => $fileVideo->val,
            ];
        }
        $this->renderJson($res);


    }

    public function actionVideo(){
        $html = '';
        $videos = LsPlayerWeightLog::find()->groupBy('user_id')->all();
        foreach ($videos as $video){
            $html .= '<div><a href="' . $video->video_url . '" target="_blank">' . $video->user_id  . '</a></div>';
        }
        echo $html;
        exit();
    }

    public function actionRight(){
        /**
         * 1.修正 add_time
         */
        /*$posts = \Yii::$app->db->createCommand('SELECT * FROM (SELECT temp.*, p.id, p.add_time, p.timeline_task, (p.add_time - temp.pay_time) as offset_time FROM (SELECT o.user_id, o.pay_time FROM hjmallind_order_detail as og LEFT JOIN hjmallind_order as o on o.id = og.order_id WHERE ( og.goods_id = 199 or og.goods_id = 205 ) AND o.is_delete = 0 AND o.is_cancel = 0) as temp LEFT JOIN hjmallind_ls_player as p on temp.user_id = p.uid ORDER BY offset_time asc) as res where res.offset_time > 100 ORDER BY `res`.`add_time` DESC')
            ->queryAll();
        foreach($posts as $post){
            $player_id = $post['id'];
            $user_id = $post['user_id'];
            $add_time = $post['pay_time'];

            $sql = 'UPDATE hjmallind_ls_player SET add_time=' . $add_time . ' WHERE id=' . $player_id;
            echo $sql . '<br>';
            \Yii::$app->db->createCommand($sql)->execute();
        }*/

        /**
         * 2.修正task
         */
        $players = Ssdsplayer::find()->all();
        foreach ($players as $player){
            $user_id = $player['uid'];

            $sql = 'SELECT o.pay_time, o.user_id FROM hjmallind_order_detail as og LEFT JOIN hjmallind_order as o on og.order_id = o.id WHERE og.goods_id in (199, 205) and o.is_pay = 1 AND o.user_id = ' . $user_id . ' GROUP BY user_id';
            //echo $sql . '---'; continue;
            $post = \Yii::$app->db->createCommand($sql)->queryOne();
            if($post){
                $sql = 'UPDATE hjmallind_ls_player SET add_time=' . $post['pay_time'] . ' WHERE uid = ' . $post['user_id'];
                echo $sql . '<br>';
                \Yii::$app->db->createCommand($sql)->execute();
            }

        }
    }
}