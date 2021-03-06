<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/6/19
 * Time: 15:14
 */

namespace app\modules\api\controllers;


use app\models\Store;
use app\models\WechatApp;
use app\models\WechatPlatform;
use app\modules\api\models\LoginForm;
use luweiss\wechat\Wechat;

/**
 * @property Store $store
 * @property WechatApp $wechat_app
 * @property Wechat $wechat 小程序的
 * @property Wechat $wechat_of_platform 公众号的
 */
class Controller extends \app\controllers\Controller
{
    public $store_id;
    public $store;
    public $wechat_app;
    public $wechat_platform;
    public $wechat;
    public $wechat_of_platform;
    public $version;

    public function init()
    {
        /*if(isset($_REQUEST['testkey']) && $_REQUEST['testkey'] == "lashou123654"){

        }else{
            $this->renderJson([
                'code' => 1,
                'msg' => '系统维护',
            ]);
        }*/

        $this->enableCsrfValidation = false;
        $this->version = $this->getVersion();
        $_acid = \Yii::$app->request->get('_acid');
        if (!$_acid) {
            $_acid = \Yii::$app->request->post('_acid');
        }

        $this->store_id = \Yii::$app->request->get('store_id');

        if ($_acid && $_acid != -1) {
            $this->store = Store::findOne([
                'acid' => $_acid,
            ]);
        } else {
            $this->store = Store::findOne($this->store_id);
        }
        if (!$this->store)
            $this->renderJson([
                'code' => 1,
                'msg' => 'Store Is Null',
            ]);
        $this->store_id = $this->store->id;
        $this->wechat_app = WechatApp::findOne($this->store->wechat_app_id);
        if (!$this->wechat_app)
            $this->renderJson([
                'code' => 1,
                'msg' => 'Wechat App Is Null',
            ]);

        if (!is_dir(\Yii::$app->runtimePath . '/pem')) {
            mkdir(\Yii::$app->runtimePath . '/pem');
            file_put_contents(\Yii::$app->runtimePath . '/pem/index.html', '');
        }

        $cert_pem_file = null;
        if ($this->wechat_app->cert_pem) {
            $cert_pem_file = \Yii::$app->runtimePath . '/pem/' . md5($this->wechat_app->cert_pem);
            if (!file_exists($cert_pem_file))
                file_put_contents($cert_pem_file, $this->wechat_app->cert_pem);
        }

        $key_pem_file = null;
        if ($this->wechat_app->key_pem) {
            $key_pem_file = \Yii::$app->runtimePath . '/pem/' . md5($this->wechat_app->key_pem);
            if (!file_exists($key_pem_file))
                file_put_contents($key_pem_file, $this->wechat_app->key_pem);
        }

        $this->wechat = new Wechat([
            'appId' => $this->wechat_app->app_id,
            'appSecret' => $this->wechat_app->app_secret,
            'mchId' => $this->wechat_app->mch_id,
            'apiKey' => $this->wechat_app->key,
            'cachePath' => \Yii::$app->runtimePath . '/cache',
            'certPem' => $cert_pem_file,
            'keyPem' => $key_pem_file,
        ]);

        $this->setWechatOfPlatform();

        $access_token = \Yii::$app->request->get('access_token');
        if (!$access_token) {
            $access_token = \Yii::$app->request->post('access_token');
        }
        if ($access_token) {
//            $form = new LoginForm();
//            $form->attributes = \Yii::$app->request->post();
//            $form->wechat_app = $this->wechat_app;
//            $form->store_id = $this->store->id;
//            $form->login();
            \Yii::$app->user->loginByAccessToken($access_token);
        }

        $controll_name = $this->id;
        $force = \Yii::$app->request->get('force');
        if (!$force) {
            $force = \Yii::$app->request->post('force');
        }
        if($force && $force == "lashou123654"){

        }elseif($controll_name == "default" || $controll_name == "user" || $controll_name == "anmeila" || $controll_name == "act" || $controll_name == "passport"){

        }else{
            //die(json_encode(['code'=>8000, 'msg' => "暂停服务"]));
        }

        //print_r([$this->id, $this->action->id, \Yii::$app->controller->action->id, $this->module->id]); exit();

        parent::init();
    }

    private function setWechatOfPlatform()
    {
        if ($this->store->use_wechat_platform_pay == 1) {
            $this->wechat_platform = WechatPlatform::findOne($this->store->wechat_platform_id);
            if (!$this->wechat_platform)
                $this->renderJson([
                    'code' => 1,
                    'msg' => 'Wechat Platform Is Null',
                ]);

            $cert_pem_file = null;
            if ($this->wechat_platform->cert_pem) {
                $cert_pem_file = \Yii::$app->runtimePath . '/pem/' . md5($this->wechat_platform->cert_pem);
                if (!file_exists($cert_pem_file))
                    file_put_contents($cert_pem_file, $this->wechat_platform->cert_pem);
            }

            $key_pem_file = null;
            if ($this->wechat_platform->key_pem) {
                $key_pem_file = \Yii::$app->runtimePath . '/pem/' . md5($this->wechat_platform->key_pem);
                if (!file_exists($key_pem_file))
                    file_put_contents($key_pem_file, $this->wechat_platform->key_pem);
            }

            $this->wechat_of_platform = new Wechat([
                'appId' => $this->wechat_platform->app_id,
                'appSecret' => $this->wechat_platform->app_secret,
                'mchId' => $this->wechat_platform->mch_id,
                'apiKey' => $this->wechat_platform->key,
                'cachePath' => \Yii::$app->runtimePath . '/cache',
                'certPem' => $cert_pem_file,
                'keyPem' => $key_pem_file,
            ]);
        }
    }


}