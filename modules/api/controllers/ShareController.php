<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8
 * Time: 14:15
 */

namespace app\modules\api\controllers;

use app\extensions\CreateQrcode;
use app\models\Cash;
use app\models\Color;
use app\models\Lashou;
use app\models\LsBonusLog;
use app\models\Option;
use app\models\Qrcode;
use app\models\Setting;
use app\models\Share;
use app\models\Store;
use app\models\ShareDetailed;
use app\models\UploadConfig;
use app\models\UploadForm;
use app\models\User;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\BindForm;
use app\modules\api\models\CashForm;
use app\modules\api\models\CashListForm;
use app\modules\api\models\QrcodeForm;
use app\modules\api\models\ShareForm;
use app\modules\api\models\TeamForm;
use app\modules\mch\models\ShareCustomForm;
use yii\helpers\VarDumper;
use yii\base\Exception;

class ShareController extends Controller
{

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'login' => [
                'class' => LoginBehavior::className(),
            ],
        ]);
    }

    /**
     * @return mixed|string
     * 申请成为分销商
     */
    public function actionJoin()
    {
        $share = Share::findOne(['user_id' => \Yii::$app->user->identity->id, 'store_id' => $this->store->id, 'is_delete' => 0]);
        if (!$share) {
            $share = new Share();
        }
        $share_setting = Setting::findOne(['store_id' => $this->store_id]);
        $form = new ShareForm();
        $form->share = $share;
        $form->store_id = $this->store_id;
        $form->attributes = \Yii::$app->request->post();
        if ($share_setting->share_condition == 1) {
            $form->scenario = "APPLY";
        } else if ($share_setting->share_condition == 0 || $share_setting->share_condition == 2) {
            $form->agree = 1;
        }
        return json_encode($form->save(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed|string
     * 获取用户的审核状态
     */
    public function actionCheck()
    {
        return json_encode([
            'subName' =>'支付298成为一星',
            'code' => 0,
            'msg' => 'success',
            'data' => \Yii::$app->user->identity->is_distributor,
            'level' => \Yii::$app->user->identity->level
        ], JSON_UNESCAPED_UNICODE);
        $setting = Setting::findOne(['store_id' => $this->store_id]);
        if ($setting->share_condition == 0) {
            $share = Share::findOne(['user_id' => \Yii::$app->user->identity->id, 'store_id' => $this->store->id, 'is_delete' => 0]);
            if (!$share) {
                $share = new Share();
            }
            $form = new ShareForm();
            $form->share = $share;
            $form->store_id = $this->store_id;
            $form->agree = 1;
//            $form->scenario = "NONE_CONDITION";
            $form->attributes = \Yii::$app->request->post();
            $res = $form->save();
            if ($res['code'] == 0) {
                return json_encode([
                    //'subName' =>'支付298成为一星',
                    'code' => 0,
                    'msg' => 'success',
                    'data' => 2,
                    'level' => \Yii::$app->user->identity->level
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            return json_encode([
                //'subName' =>'支付298成为一星',
                'code' => 0,
                'msg' => 'success',
                'data' => \Yii::$app->user->identity->is_distributor,
                'level' => \Yii::$app->user->identity->level
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @return mixed|string
     * 获取分销中心数据
     */
    public function actionGetInfo_1()
    {
        $res = [
            'code' => 0,
            'msg' => 'success',
        ];
        //获取分销佣金及提现
        $form = new ShareForm();
        $form->store_id = $this->store_id;
        $form->user_id = \Yii::$app->user->identity->id;
        $res['data']['price'] = $form->getPrice();
        //获取我的团队
        $team = new TeamForm();
        $team->user_id = \Yii::$app->user->id;
        $team->store_id = $this->store_id;
        $team->status = -1;
        $get_team = $team->getList();
        $res['data']['team_count'] = $get_team['data']['first'] + $get_team['data']['second'] + $get_team['data']['third'];
        //获取分销订单总额
        $team->limit = -1;
        $order = $team->GetOrder();
        $res['data']['order_money'] = doubleval(sprintf('%.2f', $order));

        return json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed|string
     * 获取分销中心数据
     */
    public function actionGetInfo()
    {
        $res = [
            'code' => 0,
            'msg' => 'success',
        ];
        //获取分销佣金及提现
        $form = new ShareForm();
        $form->store_id = $this->store_id;
        $form->user_id = \Yii::$app->user->identity->id;
        $res['data']['price'] = $form->getPrice();
        $ShareDetailed = new ShareDetailed();

        $shareDetailed_sum  = $ShareDetailed::find()->where(['user_id'=>\Yii::$app->user->identity->id,'type'=>0])
            ->sum('`price`');

        //获取我的团队
        $team = new TeamForm();
        $team->user_id = \Yii::$app->user->identity->id;
        $team->store_id = $this->store_id;
        $arr = $team->getOrderCount();
        $res['data']['team_count'] = $arr['team_count'];
        $res['data']['order_money'] = number_format($arr['order_money'],2);
        $res['data']['tuijian_money'] = number_format($shareDetailed_sum,2);
        $res['data']['total_amountCount'] = number_format($res['data']['price']['total_price'],2);
        $res['data']['order_money_un'] = $arr['order_money_un'];

        //获取分销自定义数据
        $custom_form = new ShareCustomForm();
        $custom_form->store_id = $this->store->id;
        $custom = $custom_form->getData();
        $res['data']['custom'] = $custom['data'];
        return json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed|string
     * 获取佣金相关
     */
    public function actionGetPrice()
    {
        $form = new ShareForm();
        $form->store_id = $this->store_id;
        $form->user_id = \Yii::$app->user->identity->id;

        $res['data']['price'] = $form->getPrice();
        $setting = Setting::findOne(['store_id' => $this->store->id]);
        $res['data']['pay_type'] = $setting->pay_type;
        $res['data']['bank'] = $setting->bank;
        $res['data']['remaining_sum'] = $setting->remaining_sum;


        $cash_last = Cash::find()->where(['store_id' => $this->store->id, 'user_id' => \Yii::$app->user->identity->id, 'is_delete' => 0])
            ->orderBy(['id' => SORT_DESC])->select(['name', 'mobile', 'type', 'bank_name'])->asArray()->one();

        $res['data']['cash_last'] = $cash_last;
        $cash_max_day = floatval(Option::get('cash_max_day', $this->store_id, 'share', 0));
        if ($cash_max_day) {
            $cash_sum = Cash::find()->where([
                'store_id' => $this->store->id,
                'is_delete' => 0,
                'status' => [0, 1, 2 , 5],
            ])->andWhere([
                'AND',
                ['>=', 'addtime', strtotime(date('Y-m-d 00:00:00'))],
                ['<=', 'addtime', strtotime(date('Y-m-d 23:59:59'))],
            ])->sum('price');
            $cash_max_day = $cash_max_day - ($cash_sum ? $cash_sum : 0);
            $res['data']['cash_max_day'] = max(0, floatval(sprintf('%.2f', $cash_max_day)));
        } else {
            $res['data']['cash_max_day'] = -1;
        }
        return $this->renderJson($res);
    }

    /**
     * @return mixed|string
     * 申请提现
     */
    public function actionApply()
    {
        $form = new CashForm();
        $form->user_id = \Yii::$app->user->identity->id;
        $form->store_id = $this->store_id;
        $form->attributes = \Yii::$app->request->post();
        if(\Yii::$app->request->post()['is_partner'] != 1)
            $form->is_partner = 0;
       // print_r(\Yii::$app->request->post());
        return json_encode($form->save(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 提现明细列表
     */
    public function actionCashDetail()
    {
        $form = new CashListForm();
        $get = \Yii::$app->request->get();
        $form->attributes = $get;
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->getList());
    }

    /**
     * @return mixed|string
     * 获取推广海报
     */
    public function actionGetQrcode()
    {
        //获取用户信息
        $user_id = \Yii::$app->user->id;
        $store_id = $this->store_id;
        $user = User::findOne(['id' => $user_id, 'store_id' => $store_id]);
        $avatar = $user->avatar_url;
        $avatar = self::saveTempImage($user->avatar_url);
        //$avatar= '/data/wwwroot/api.anmeila.com.cn/runtime/image/3137a5acff22d81c1ec3fcee41371d45.jpg';
        //exit($avatar);
        //3137a5acff22d81c1ec3fcee41371d45
        //31317731844727b7ba40924d679f82f4
        $name = $user->nickname;
        $save_root = \Yii::$app->basePath . '/web/temp/';
        $save_name = md5("v={$this->getVersion()}&store_id={$this->store->id}&store_name={$this->store->name}&user_id={$user_id}") . '.jpg';
        $pic_url = str_replace('http://', 'https://', \Yii::$app->request->hostInfo . \Yii::$app->request->baseUrl . '/temp/' . $save_name);
        if (file_exists($save_root . $save_name)) {
//            return json_encode([
//                'code' => 0,
//                'msg' => 'success',
//                'data' => $pic_url . '?v=' . time()
//            ], JSON_UNESCAPED_UNICODE);
        }
        //获取商户海报设置  默认为1
        $store_qrcode = Qrcode::findOne(['store_id' => $store_id, 'is_delete' => 0]);
        if (!$store_qrcode) {
//            $store_qrcode = Qrcode::findOne(1);
            return json_encode([
                'code' => 1,
                'msg' => '请先在后台设置分销海报'
            ]);
        }
        $font_position = json_decode($store_qrcode->font_position, true);
        $qrcode_position = json_decode($store_qrcode->qrcode_position, true);
        $avatar_position = json_decode($store_qrcode->avatar_position, true);
        $avatar_size = json_decode($store_qrcode->avatar_size, true);
        $qrcode_size = json_decode($store_qrcode->qrcode_size, true);
        $font_size = json_decode($store_qrcode->font, true);
        $qrcode_bg = self::saveTempImage($store_qrcode->qrcode_bg);
        list($qrcode_bg_w, $qrcode_bg_h) = getimagesize($qrcode_bg);

        $percent = $qrcode_bg_w / 300;
//        $percent = 0.5;
        //获取微信小程序码
        $access_token = $this->wechat->getAccessToken();
        $api = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";
        $data = json_encode([
            'scene' => "{$user_id}",
//            'page'=>"pages/index/index",
            'width' => (int)($qrcode_size['w'] * $percent)
        ], JSON_UNESCAPED_UNICODE);
        $this->wechat->curl->post($api, $data);
        if ($this->wechat->curl->error) {
            return json_encode([
                'code' => 1,
                'msg' => '小程序码获取失败',
            ]);
        }
        $res = $this->wechat->curl->response;

        //保存到本地
        $saveRoot = \Yii::$app->basePath . '/web/temp/';
        $saveDir = '';
        if (!is_dir($saveRoot . $saveDir)) {
            mkdir($saveRoot . $saveDir);
            file_put_contents($saveRoot . $saveDir . '.gitignore', "*\r\n!.gitignore");
        }
        $webRoot = \Yii::$app->request->baseUrl . '/';
        $saveName = md5(uniqid()) . '.png';
        file_put_contents($saveRoot . $saveDir . $saveName, $res);
        $form = new CreateQrcode();
        $form->qrcode = $saveRoot . $saveDir . $saveName;
        $form->avatar = $avatar;
        $form->name = $name;
        $form->store_qrcode = $store_qrcode;
        $font_file = \Yii::$app->basePath . '/web/statics/font/AaBanSong.ttf';//字体
        $font_array = imagettfbbox((int)$font_size['size'] * $percent * 0.74, 0, $font_file, $name);
        $form->font_x = (int)$font_position['x'] * $percent;
        $form->font_y = (int)$font_position['y'] * $percent + (int)($font_array[3] - $font_array[5]);
        $color = Color::find()->andWhere(['id' => (int)$font_size['color']])->asArray()->one();
        $form->font_size = (int)$font_size['size'] * $percent * 0.74;
        $form->font_color = json_decode($color['rgb'], true);

        $form->qrcode_x = (int)$qrcode_position['x'] * $percent;
        $form->qrcode_w = (int)$qrcode_size['w'] * $percent;
        $form->qrcode_y = (int)$qrcode_position['y'] * $percent;
        $form->qrcode_true = isset($qrcode_size['c']) ? $qrcode_size['c'] : true;

        $form->avatar_x = (int)$avatar_position['x'] * $percent;
        $form->avatar_y = (int)$avatar_position['y'] * $percent;
        $form->avatar_w = (int)$avatar_size['w'] * $percent;
        $form->avatar_h = (int)$avatar_size['h'] * $percent;

        $form->qrcode_bg = $qrcode_bg;
        $form->save_name = $save_name;
        $res = $form->getQrcode();
        return json_encode([
            'code' => 0,
            'msg' => 'success',
            'data' => $pic_url . '?v=' . time()
        ], JSON_UNESCAPED_UNICODE);
    }


    //获取网络图片到临时目录
    private function saveTempImage($url)
    {
        $wdcp_patch = false;
        $wdcp_patch_file = \Yii::$app->basePath . '/patch/wdcp.json';
        if (file_exists($wdcp_patch_file)) {
            $wdcp_patch = json_decode(file_get_contents($wdcp_patch_file), true);
            if ($wdcp_patch && in_array(\Yii::$app->request->hostName, $wdcp_patch)) {
                $wdcp_patch = true;
            } else {
                $wdcp_patch = false;
            }
        }
        if ($wdcp_patch)
            $url = str_replace('http://', 'https://', $url);

        if (!is_dir(\Yii::$app->runtimePath . '/image'))
            mkdir(\Yii::$app->runtimePath . '/image');
        $save_path = \Yii::$app->runtimePath . '/image/' . md5($url) . '.jpg';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $img = curl_exec($ch);
        curl_close($ch);
        $fp = fopen($save_path, 'w');
        fwrite($fp, $img);
        fclose($fp);
        return $save_path;
    }

    /**
     * @return mixed|string
     * 商店分销设置信息
     */
    public function actionShopShare()
    {
        $list = Setting::find()->alias('s')
            ->where(['s.store_id' => $this->store_id])
            ->leftJoin('{{%qrcode}} q', 'q.store_id=s.store_id and q.is_delete=0')
            ->select(['s.level', 'q.qrcode_bg'])
            ->asArray()->one();
        return json_encode([
            'code' => 0,
            'msg' => '',
            'data' => $list
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed|string
     * 绑定上下级关系
     */
    public function actionBindParent()
    {
        //1.插入share 表：user_id, share_id, add_time

        //2.share user 加随机金额的红包

        //3.保证红包 <= 10

        //4.判断share表的记录条数，> 1 回滚
        $hongbao_res = [];
        $parent_id = \Yii::$app->request ->get()['parent_id'];
        $parentinfo = User::findOne($parent_id);
        $user = User::findOne(\Yii::$app->user->id);
        //file_put_contents('/data/wwwroot/api.anmeila.com.cn/modules/api/11111.txt','111111');
        //var_dump($user);die;
        if(empty($user->share_id)  
            && $parentinfo 
            && $parentinfo->is_distributor != 1 
            && $parentinfo->is_partner != 1 
            && Lashou::hasNewerHongbao($parent_id)){
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $i = rand(3,6);

                $parent_hongbao_query = LsBonusLog::find()->where(["uid" => $parent_id])->andWhere(["type" => 1]);
                $parent_hongbao = $parent_hongbao_query->sum("val");
                if(empty($parent_hongbao)){
                    $parent_hongbao = 0;
                }

                if($parent_hongbao + $i > 1000){
                    $i = 1000 - $parent_hongbao;
                }

                if($i > 0){
                    $ls_price = $i;

                    //1.插入日志
                    $lsbonuslog = new LsBonusLog();
                    $lsbonuslog -> add_time = time();
                    $lsbonuslog -> type = 1;
                    $lsbonuslog ->extension_id = 2;
                    $lsbonuslog -> uid = $parent_id;
                    $lsbonuslog -> val = $ls_price;
                    $lsbonuslog->log = '分享获得随机金额的红包';
                    $lsbonuslog->son_uid = \Yii::$app->user->id;
                    $lsbonuslog -> save();


                    //2.查询日志总金额 如果 > 10, 回滚
                    $log_val_total = LsBonusLog::find()->where(['uid' => $parent_id, 'type' => 1])->sum('val');
                    if($log_val_total > 1000){
                        throw new Exception('已超过1000',900);
                    }

                    //3.查询日志记录中parent 分享 user的记录， 如果数量 > 1, 回滚
                    $has_record = LsBonusLog::find()
                        ->where(['uid' => $parent_id, 'son_uid' => \Yii::$app->user->id, 'type' => 1, 'extension_id' => 2])
                        ->count();
                    if($has_record > 1){
                        throw new Exception('已经有该记录',901);
                    }

                    //4.提交修改 分享者发送红包
                    //$parentinfo -> ls_price += $ls_price/100;
                    $parentinfo->updateCounters(['ls_price' => $ls_price/100]);//by wuran 0606,防止并发，参见yii文档：如果你使用 yii\db\ActiveRecord::save() 更新一个计数列，你最终将得到错误的结果， 因为可能发生这种情况，多个请求间并发读写同一个计数列。
                    $parentinfo -> save(); //给分享者红包

                    //更新shareid
                    $user->share_id = $parent_id;
                    $user->save();

                    $transaction->commit();
                }
            } catch (\Exception $e) {
                $transaction->rollback();
                $hongbao_res = [$e->getMessage(), $e->getCode()];
            }
        }


        $form = new BindForm();
        $form->user_id = \Yii::$app->user->id;
        $form->store_id = $this->store_id;
        return json_encode($form->save(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed|string
     * 获取团队详情
     */
    public function actionGetTeam()
    {
        $form = new TeamForm();
        $form->user_id = \Yii::$app->user->id;
        $form->store_id = $this->store_id;
        $form->scenario = "TEAM";
        $form->attributes = \Yii::$app->request->get();
        return json_encode($form->getTeam(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed|string
     * 获取分销订单
     */
    public function actionGetOrder()
    {
        $form = new TeamForm();
        $form->user_id = \Yii::$app->user->id;
        $form->store_id = $this->store_id;
        $form->scenario = "ORDER";
        $form->attributes = \Yii::$app->request->get();
        return json_encode($form->getOrder(), JSON_UNESCAPED_UNICODE);
    }

    public function actionSelectDetailed(){
        $mod_ShareDetailed = new ShareDetailed;
        $mod_ShareDetailed->user_id = \Yii::$app->user->id;
        //$mod_ShareDetailed->store_id = $this->store_id;
        return json_encode($mod_ShareDetailed->selectDetailedAll());

    }

    public function actionTime(){
        echo date("Y-m-d H:i:s", $_GET['t']) . "<br>";
        echo time();
    }
}