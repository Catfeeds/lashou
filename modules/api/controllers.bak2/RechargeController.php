<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/15
 * Time: 13:36
 */

namespace app\modules\api\controllers;


use app\models\Option;
use app\models\Order;
use app\models\Recharge;
use app\models\RechargeModule;
use app\models\ReOrder;
use app\models\ShareDetailed;

use app\models\Share;
use app\models\Setting;
use app\models\User;
use app\modules\api\models\ShareForm;
use app\modules\api\models\recharge\OrderSubmitShare;

use app\modules\api\models\recharge\DetailForm;
use app\modules\api\models\recharge\OrderSubmit;
use app\modules\api\models\recharge\RecordForm;

class RechargeController extends Controller
{
    public function actionIndex()
    {
        $user = \Yii::$app->user->identity;

        $MUser = User::findOne(\Yii::$app->user->identity->id);
        if($MUser->is_distributor != 1){
            $this->renderJson([
                'code' => 501,
                'msg' => '成为星级用户才能充值'
            ]);
        }

        //搜索指定月份的充值记录及余额消费记录
        $form = new RecordForm();
        $form->store_id = $this->store->id;
        $form->user = $user;
        $form->attributes = \Yii::$app->request->get();
        $res = $form->search();

        //余额页设置
        $form = new RechargeModule();
        $form->store_id = $this->store->id;
        $setting = $form->search_recharge();

        return $this->renderJson([
            'code' => 0,
            'msg' => '',
            'data' => [
                'money' => $user->money,
                'list' => $res['list'],
                'setting'=>$setting,
                'date'=>$res['date']
            ]
        ]);
    }

    public function actionRecord()
    {
        $user = \Yii::$app->user->identity;

        //搜索指定月份的充值记录及余额消费记录
        $form = new RecordForm();
        $form->store_id = $this->store->id;
        $form->user = $user;
        $form->attributes = \Yii::$app->request->get();
        $res = $form->search();
        return $this->renderJson([
            'code'=>0,
            'msg'=>'',
            'data'=>$res
        ]);
    }

    public function actionList()
    {
        $balance = Option::get('re_setting',$this->store_id,'app');
        $balance = json_decode($balance,true);
        $list = Recharge::find()->where(['store_id' => $this->store->id, 'is_delete' => 0])
            ->orderBy(['pay_price' => SORT_DESC])->asArray()->all();
        $this->renderJson([
            'code' => 0,
            'msg' => '',
            'data' => [
                'list' => $list,
                'balance'=>$balance
            ]
        ]);
    }

    /**
     * 充值提交
     */
    public function actionSubmit()
    {
        $form = new OrderSubmit();
        $form->store_id = $this->store->id;
        $form->user = \Yii::$app->user->identity;
        $form->attributes = \Yii::$app->request->post();
        $this->renderJson($form->save());
    }

    /**
     * 余额收支详情
     */
    public function actionDetail()
    {
        $form = new DetailForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        $this->renderJson($form->search());
    }

    public function actionSubmit_share()
    {


        $user = \Yii::$app->user->identity;
        $share = Share::findOne(['user_id' => $user->id, 'store_id' => $this->store->id, 'is_delete' => 0]);
        if (!$share) {
            $share = new Share();
        }
        $share_setting = Setting::findOne(['store_id' => $this->store->id]);
        $form = new ShareForm();
        $form->share = $share;
        $form->store_id = $this->store->id;
        $form->user_id = $user->id;
        $form->agree = 1;
        $form->name = empty($user->nickname) ? $user->id : $user->nickname;

        if ($share_setting->share_condition == 1) {
            $form->scenario = "APPLY";
        } else if ($share_setting->share_condition == 0 || $share_setting->share_condition == 2) {
            $form->agree = 1;
        }
        $isInfo = $form->save();

        $mod_ShareDetailed = new ShareDetailed;
        $mod_ShareDetailed->saveS();

        if($isInfo['code'] == 0){
            $form = new OrderSubmitShare();
            $form->store_id = $this->store->id;
            $form->user = \Yii::$app->user->identity;
            //   $form->attributes = \Yii::$app->request->post();
            $form['is_parent'] = 1;
            $form['pay_price'] = 298;
            $form['pay_type'] = 'WECHAT_PAY';

            $this->renderJson($form->save());
        }else
            exit(json_encode(['code'=>10001,'msg'=>'请稍后再试', 'isinfo' => $isInfo]));


    }
}