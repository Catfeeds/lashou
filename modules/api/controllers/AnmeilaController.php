<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8
 * Time: 14:15
 */

namespace app\modules\api\controllers;

use app\models\Address;
use app\models\FormId;
use app\models\Level;
use app\models\Option;
use app\models\Order;
use app\models\Setting;
use app\models\Share;
use app\models\Store;
use app\models\User;
use app\models\UserAuthLogin;
use app\models\UserCard;
use app\models\UserCenterForm;
use app\models\UserCenterMenu;
use app\models\UserFormId;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\AddressDeleteForm;
use app\modules\api\models\AddressSaveForm;
use app\modules\api\models\AddressSetDefaultForm;
use app\modules\api\models\AddWechatAddressForm;
use app\modules\api\models\CardListForm;
use app\modules\api\models\FavoriteAddForm;
use app\modules\api\models\FavoriteListForm;
use app\modules\api\models\FavoriteRemoveForm;
use app\modules\api\models\OrderListForm;
use app\modules\api\models\TopicFavoriteForm;
use app\modules\api\models\TopicFavoriteListForm;
use app\modules\api\models\WechatDistrictForm;
use app\modules\api\models\QrcodeForm;
use app\modules\api\models\OrderMemberForm;
use app\models\SmsSetting;
use app\modules\api\models\UserForm;
use app\extensions\Sms;

class AnmeilaController extends Controller
{   
    public function actionTeam(){
        $user = User::find()->where(['id' => \Yii::$app->user->id])->asArray()->all(); 
        $ispaent  = User::find()->where(['id' => $user[0]['parent_id'], 'store_id' => $this->store_id])->asArray()->all(); 
        
        if(!empty($ispaent)){
            if(empty($ispaent[0]['wechat_open_id'])){
                $paent_user_name = $ispaent[0]['username'];
                $paent_status = 0;
            }else{
                $paent_user_name = $ispaent[0]['nickname'];
                $paent_status = 1;
            }
        }else{
            $paent_user_name = '无';
            $paent_status = '无';
        }
        $list_1 = User::find()->where(['parent_id' => $user[0]['id'], 'store_id' => $this->store_id])->select('username user_name,nickname,id,wechat_open_id')->asArray()->all();
        $list_total = User::find()->where(['parent_id' => $user[0]['id'], 'store_id' => $this->store_id])->count();
        $list_active = User::find()->where(['parent_id' =>  $user[0]['id'], 'store_id' => $this->store_id])->andWhere(' wechat_open_id != ""')->count();
        $list_2 = array();
        $level_2_total = 0;
        $level_2_active = 0;
        if(!empty($list_1)){
            foreach ($list_1 as $v){
                $list = User::find()->where(['parent_id' => $v['id'], 'store_id' => $this->store_id])->select('username user_name,nickname,id,wechat_open_id')->asArray()->all();;
                $level_2_total+=User::find()->where(['parent_id' => $v['id'], 'store_id' => $this->store_id])->count();
                $level_2_active+=User::find()->where(['parent_id' => $v['id'], 'store_id' => $this->store_id])->andWhere(' wechat_open_id != ""')->count();
                $list_2 = array_merge($list_2,$list);
            }
        }
        
        $res = [
                    "parent" => [
                        "user_name" => $paent_user_name, //用户名
                        "status" => $paent_status //激活状态：0 未激活 1 已激活
                    ],

                    "level_1" => [
                        "total" => $list_total,//总数
                        "active" => $list_active,//已激活
                        "list" => $list_1
                    ],

                    "level_2" => [
                        "total" => $level_2_total,//总数
                        "active" => $level_2_active,//已激活
                        "list" => $list_2
                    ],
                ];
        return $this->renderJson(["data" => $res]);
    }
}