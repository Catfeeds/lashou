<?php
/**
 * Created by PhpStorm.
 * User: zc
 * Date: 2018/4/25
 * Time: 9:36
 */

namespace app\modules\api\models;

use app\models\ActivityUser;
use app\models\User;
use app\models\Viewer;
use Curl\Curl;
class UserForm extends Model
{
    public $store_id;
    public $user_id;
    public $appId;
    public $session_key;
    public $code;
    public $encryptedData;
    public $iv;
    public $wechat_app;
    public $phone;

    public function rules()
    {
        return [
            [['user_id',], 'required'],
            [['binding','phone','phonecode'], 'integer'],
            [['appId','code','encryptedData','iv','wechat_app'], 'string'],
        ];
    }
    public function userEmpower()
    {
        $user = user::find()->where(['store_id'=>$this->store_id,'id'=>$this->user_id])->one();
        $user->binding = $this->phone;
        if($user->save()){
            return [
                'code' => 0
            ];
        }else{
            return [
                'code'=>1,
                'msg'=>'fail'
            ];
        }
    }

    public function binding()
    {

   /*     if (strlen($this->session_key) != 24) {
            return 1;
        }
           if (strlen($this->iv) != 24) {
            return 3;
        }
        $aesKey=base64_decode($this->session_key);
        $aesIV=base64_decode($this->iv);
        $aesCipher=base64_decode($this->encryptedData);
        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj=json_decode($result);


        return [
            'code' => 0,
            'msg' => 'success',
            'data' =>[
                'dataObj' => $errCode->phoneNumber,
            ]
        ];*/
        require __DIR__ . '/wxbdc/WXBizDataCrypt.php';
        $pc = new \WXBizDataCrypt($this->appId, $this->session_key);

        $errCode = $pc->decryptData($this->encryptedData, $this->iv, $data);
        $dataObj = json_decode($data,true);
        if ($errCode == 0){

           //$user = user::find()->where(['store_id'=>$this->store_id,'id'=>$this->user_id])->one();
            $phone = /*$dataObj['countryCode'].*/$dataObj['phoneNumber'];
            //$user->binding = $phone;

            $phone_cl = substr_replace($dataObj['phoneNumber'],'****',3,4);
            $view = Viewer::find()->where(['uid'=>$this->user_id])->one();
            if(!empty($view)){

            }else{
                $view = new Viewer();
            }

            $activity_user = ActivityUser::findOne(['user_id' => $this->user_id]);
            if(!empty($activity_user)){
                $activity_user->mobile = $phone;
                $activity_user->save();
            }
            
            $view->mobile = $phone;
            $view->uid = $this->user_id;
            $view->add_time= time();
            if($view->save()){
                return [
                    'code' => $errCode,
                    'msg' => 'success',
                    'data' =>['countryCode'=>$dataObj['countryCode'],'phoneNumber'=>$phone_cl]
                ];
            }else{
                return [
                    'code'=>1,
                    'msg'=>'fail'
                ];
            }

        }else{
            return [
                'code' => $errCode,
                'msg' => 'Fail',

            ];

        }
    }

    private function getOpenid($code)
    {
        $api = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->wechat_app->app_id}&secret={$this->wechat_app->app_secret}&js_code={$code}&grant_type=authorization_code";
        $curl = new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->get($api);
        $res = $curl->response;
        $res = json_decode($res, true);
        return $res;
    }

}