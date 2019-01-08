<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8
 * Time: 14:15
 */

namespace app\modules\api\controllers;

use app\models\LsShare;
use app\models\LsShareFrom;
use app\models\User;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\GoodsQrcodeForm;

class LsShareController extends Controller
{

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'login' => [
                'class' => LoginBehavior::className(),
            ],
        ]);
    }

    public function actionShare(){
        $user_id = \Yii::$app->user->id;
        $user = User::findOne($user_id);

        if(!empty($user)){

            $postData = \Yii::$app->request->post();

            $LsShare = LsShare::findOne(['user_id'=>$user->id]);

            if(empty($LsShare))
                $LsShare = new LsShare;

            $LsShare->user_id = $user->id;

            switch ($postData['type']){
                    case 1:
                        $LsShare->type_friend += 1;
                        break;
                    case 2:
                        $LsShare->type_qun += 1;
                        break;
                    case 3:
                        $LsShare->type_qr += 1;
                        break;
                }

            $LsShare->addtime = time();

            $this->renderJson($LsShare->save());
        }





    }

    public function actionShareFrom(){


        $user_id = \Yii::$app->user->id;
        $user = User::findOne($user_id);

        if(!empty($user)){

            $postData = \Yii::$app->request->post();

            $LsShare = new LsShareFrom;

            $LsShare->user_id = $user->id;
            $LsShare->type = $postData['from_type'];
            $LsShare->from_id = $postData['from_id'];
            $LsShare->addtime = time();

            $this->renderJson($LsShare->save());
        }





    }

    public function actionGetQr(){


        $GoodsQrcodeForm  = new GoodsQrcodeForm;
        $GoodsQrcodeForm->store_id = $this->store_id;;
        $GoodsQrcodeForm->user_id = \Yii::$app->user->id;
        $this->renderJson($GoodsQrcodeForm ->lsShareQr());


    }
}