<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/1
 * Time: 16:52
 */

namespace app\modules\api\models;


use app\models\Setting;
use app\models\Share;
use app\models\User;
use app\modules\mch\models\Model;
use Curl\Curl;

class LoginForm extends Model
{
    public $wechat_app;

    public $code;
    public $user_info;
    public $encrypted_data;
    public $iv;
    public $signature;

    public $store_id;
    
    public $oldphone;
    public $olduser;
    public $first;

    public function rules()
    {
        return [
            [['wechat_app', 'code', 'user_info', 'encrypted_data', 'iv', 'signature',], 'required'],
            [['oldphone','olduser'], 'string'],
            [['first'], 'number'],
        ];
    }

    public function login()
    {
        if (!$this->validate())
            return $this->getModelError();
        $res = $this->getOpenid($this->code);
        if (!$res || empty($res['openid'])) {
            return [
                'code' => 1,
                'msg' => '获取用户OpenId失败',
                'data' => $res,
            ];
        }
        $session_key = $res['session_key'];
        require __DIR__ . '/wxbdc/WXBizDataCrypt.php';
        $pc = new \WXBizDataCrypt($this->wechat_app->app_id, $session_key);
        $errCode = $pc->decryptData($this->encrypted_data, $this->iv, $data);
        if ($errCode == 0) {
            $data = json_decode($data, true);
            $user = User::findOne(['wechat_open_id' => $data['openId'], 'store_id' => $this->store_id]);
            if (!$user) {
                if(empty($this->oldphone) && empty($this->olduser) && $this->first!=2){
                    return [
                        'code' => -200,
                        'msg' => '正在为您跳转激活页面',
                    ];
                }
                if(!empty($this->oldphone)){
                    return [
                        'code' => 1,
                        'msg' => '暂不开放',
                    ];
                    $user = User::findOne(['mobile' => $this->oldphone,'wechat_open_id'=>'', 'store_id' => $this->store_id]);
                    if(!$user){
                         return [
                                'code' => 1,
                                'msg' => '账号不存在或已激活',
                            ];
                        $user = new User();
                    }else{
                        //验证成功有数据的话更新share的数据
                        if($user->is_distributor==1 ){
                            $myshare = Share::findOne(['user_id' => $user->id, 'store_id' => $this->store_id]);
                            if(!$myshare){
                               $myshare = new Share();
                            }
                            $myshare->user_id=$user->id;
                            $myshare->mobile=$user->mobile;
                            $myshare->name=!empty($this->nickname)?$this->nickname:$user->username;
                            $myshare->status=$user->is_distributor;
                            $myshare->addtime = time();
                            $myshare->store_id=1;
                            $myshare->save();

                            if($user->level < 1){
                                $user->level = 1;
                                $user->save();
                            }
                        }
                    }
                }else if(!empty($this->olduser)){
                    return [
                        'code' => 1,
                        'msg' => '暂不开放',
                    ];
                    $user = User::findOne(['id' => $this->olduser,'wechat_open_id'=>'', 'store_id' => $this->store_id]);
                    if(!$user){
                        return [
                                'code' => 1,
                                'msg' => '账号不存在或已激活',
                            ];
                        $user = new User();
                    }else{
                        //验证成功有数据的话更新share的数据
                        if($user->is_distributor==1 ){
                            $myshare = Share::findOne(['user_id' => $user->id, 'store_id' => $this->store_id]);
                            if(!$myshare){
                               $myshare = new Share();
                            }
                            $myshare->user_id=$user->id;
                            $myshare->mobile=$user->mobile;
                            $myshare->name= !empty($this->nickname)?$this->nickname:$user->username;
                            $myshare->status=$user->is_distributor;
                            $myshare->addtime = time();
                            $myshare->store_id=1;
                            $myshare->save();

                            if($user->level < 1){
                                $user->level = 1;
                                $user->save();
                            }
                        }
                    }
                }else{
                    $user = new User();
                }
                $user->type = 1;
                $user->username = $data['openId'];
                $user->password = \Yii::$app->security->generatePasswordHash(\Yii::$app->security->generateRandomString(),5);
                $user->auth_key = \Yii::$app->security->generateRandomString();
                $user->access_token = \Yii::$app->security->generateRandomString();
                $user->addtime = time();
                $user->is_delete = 0;
                $user->session_key = $session_key;
                $user->wechat_open_id = $data['openId'];
                $user->wechat_union_id = isset($data['unionId']) ? $data['unionId'] : '';
                //$user->nickname = $data['nickName'];
                $user->nickname = preg_replace('/[\xf0-\xf7].{3}/', '', $data['nickName']);
                $user->avatar_url = $data['avatarUrl'];
                $user->store_id = $this->store_id;
                $user->save();
                $same_user = User::find()->select('id')->where([
                    'AND',
                    [
                        'store_id' => $this->store_id,
                        'wechat_open_id' => $data['openId'],
                        'is_delete' => 0,
                    ],
                    ['<', 'id', $user->id],
                ])->one();
                if ($same_user) {
                    $user->delete();
                    $user = null;
                    $user = $same_user;
                }
            }else{
                if(empty($user->access_token)){
                    $user->access_token = \Yii::$app->security->generateRandomString();
                }
                $user->session_key = $session_key;
                $user->nickname = preg_replace('/[\xf0-\xf7].{3}/', '', $data['nickName']);
                $user->avatar_url = $data['avatarUrl'];
                $user->save();
            }
            $share = Share::findOne(['user_id' => $user->parent_id]);
            $share_user = User::findOne(['id' => $share->user_id]);
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => (object)[
                    'access_token' => $user->access_token,
                    'nickname' => $user->nickname,
                    'avatar_url' => $user->avatar_url,
                    'is_partner' => $user->is_partner,
                    'is_distributor' => $user->is_distributor ? $user->is_distributor : 0,
                    'parent' => $share->id ? ($share->name ? $share->name : $share_user->nickname) : '总店',
                    'id' => $user->id,
                    'is_clerk' => $user->is_clerk,
                    'integral' => $user->integral,
                    'money'=>$user->money
                ],
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '登录失败',
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