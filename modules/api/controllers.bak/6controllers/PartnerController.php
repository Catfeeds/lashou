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
use app\models\Goods;
use app\models\Lashou;
use app\models\Option;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Partner;
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
use app\modules\api\models\PartnerDetailForm;
use app\modules\api\models\PartnerForm;
use app\modules\api\models\QrcodeForm;
use app\modules\api\models\ShareForm;
use app\modules\api\models\TeamForm;
use app\modules\mch\models\ShareCustomForm;
use app\modules\api\models\PartnerSubmitForm;
use yii\helpers\VarDumper;

class PartnerController extends Controller
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
        $partner = Partner::findOne(['user_id' => \Yii::$app->user->identity->id, 'store_id' => $this->store->id, 'is_del' => 0]);
        if (!$partner) {
            $partner = new Partner();
        }
        $form = new PartnerForm();
        $form->partner = $partner;
        $form->store_id = $this->store_id;
        $form->name =  \Yii::$app->user->identity->nickname;
        $form->attributes = \Yii::$app->request->post();
        return $this->renderJson($form->save(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed|string
     * 获取用户的审核状态
     */
    public function actionCheck()
    {
      /*  return json_encode([
            'code' => 0,
            'msg' => 'success',
            'is_partner' => \Yii::$app->user->identity->is_partner,
        ], JSON_UNESCAPED_UNICODE);*/
        $form = new PartnerForm();

        return $this->renderJson($form->checkAnMeiLaAccounts());
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


        //获取合伙人基本信息
        $partner = Partner::find()->where('`user_id` = '.$form->user_id)->asArray()->one();

        $yJAmount = Order::find()->where('`is_pay` = 1 AND `apply_delete` = 0 AND `is_cancel` = 0 AND `is_delete` = 0 AND  is_price = 0 AND partner_id = '.$form->user_id)->sum('pay_price - pt_amount - rebate - first_price - second_price - third_price');
        //一件代发 start
        $order_query = Order::find()
            ->where('`is_pay` = 1 AND `apply_delete` = 0 AND `is_cancel` = 0 AND `is_delete` = 0 AND  is_price = 0 AND partner_id = '.$form->user_id)
            ->andWhere(['>=','pay_time',strtotime(Lashou::DAI_FA_START_TIME)])
            ->select('id');
        $order_ids = $order_query->column();
        if(count($order_ids) > 0){
            $shipping_fee = max($order_query->sum('partner_shipping_fee'), $order_query->sum('express_price'));

            $order_goods_cost = OrderDetail::find()->alias('og')
                ->leftJoin(Goods::tableName() . ' as g', 'og.goods_id = g.id')
                ->where(['in', 'og.order_id', $order_ids])
                ->sum('og.num * g.cost_price');
            $yJAmount -= $shipping_fee + $order_goods_cost;
        }
        //一件代发 end

        $user = User::findOne($form->user_id);

        $res['data']['partner'] = $partner;
        $res['data']['partner']['total_amount'] = $user->partner_total_amount;
        $res['data']['partner']['yj_amount'] = number_format($yJAmount,2);
        $res['data']['partner']['amount'] = $user->partner_amount;

        //获取分销自定义数据
        $custom_form = new PartnerSubmitForm();
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

    public function actionPartnerByDetail()
    {
        $form = new PartnerDetailForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id =  \Yii::$app->user->id;
        $this->renderJson($form->search());
    }



    //获取订单中的  合伙人订单
    public function actionPartnerByOrderGet(){

        $partnerSubimtForm = new PartnerSubmitForm();
        $data = \Yii::$app->request->get();
        $partnerSubimtForm->page = $data['page'];
        $partnerSubimtForm->store_id = $this->store->id;
        $partnerSubimtForm->user_id = \Yii::$app->user->id;
        $partnerOrders = $partnerSubimtForm->getMarketOrders();

        if(empty($partnerOrders))
            return $this->renderJson(['code'=>2,'msg'=>'没有更多了']);
        return $this->renderJson(['code'=>0,'msg'=>'成功','partnerOrders'=>$partnerOrders]);
    }

    // 合伙人 抢单

    /**
     * @return string
     */
    public function actionPartnerByOrderGrab(){
        $postData = \Yii::$app->request->post();
        $partnerSubimtForm = new PartnerSubmitForm();
        $partnerSubimtForm->order_id = $postData['order_id'];
        $partnerSubimtForm->store_id = $this->store->id;
        $partnerSubimtForm->user_id = \Yii::$app->user->id;
        return $this->renderJson($partnerSubimtForm->orderGrab());

    }

    /**
     * @return
     * 查詢合伙人订单
     *
     *
     */
        //获取订单中的  合伙人订单
    public function actionPartnerMyOrders(){

        $partnerSubimtForm = new PartnerSubmitForm();
        $data = \Yii::$app->request->get();
        $partnerSubimtForm->page = $data['page'];
        $partnerSubimtForm->store_id = $this->store->id;
        $partnerSubimtForm->user_id = \Yii::$app->user->id;
        $partnerOrders = $partnerSubimtForm->getMyOrders();

        if(empty($partnerOrders))
            return $this->renderJson(['code'=>2,'msg'=>'没有更多了']);
        return $this->renderJson(['code'=>0,'msg'=>'成功','partnerOrders'=>$partnerOrders]);
    }

    public function actionPartnerSendGoods(){
        $postData = \Yii::$app->request->post();
        $partnerSubimtForm = new PartnerSubmitForm();
        $partnerSubimtForm->store_id = $this->store->id;
        $partnerSubimtForm->user_id = \Yii::$app->user->id;
        $partnerSubimtForm->order_id = $postData['order_id'];
        $partnerSubimtForm->express = $postData['express'];
        $partnerSubimtForm->express_no = $postData['express_no'];
        return $this->renderJson($partnerSubimtForm->sendGoods());
    }





}