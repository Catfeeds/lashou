<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/17
 * Time: 11:47
 */

namespace app\modules\api\controllers;


use app\models\Goods;
use app\models\Order;
use app\models\OrderDetail;
use app\models\UploadConfig;
use app\models\UploadForm;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\ExpressDetailForm;
use app\modules\api\models\LocationForm;
use app\modules\api\models\OrderClerkForm;
use app\modules\api\models\OrderCommentForm;
use app\modules\api\models\OrderCommentPreview;
use app\modules\api\models\OrderConfirmForm;
use app\modules\api\models\OrderDetailForm;
use app\modules\api\models\OrderListForm;
use app\modules\api\models\OrderPayDataForm;
use app\modules\api\models\OrderRefundDetailForm;
use app\modules\api\models\OrderRefundForm;
use app\modules\api\models\OrderRefundPreviewForm;
use app\modules\api\models\OrderRevokeForm;
use app\modules\api\models\OrderSubmitForm;
use app\modules\api\models\OrderSubmitPreviewForm;
use app\modules\api\models\QrcodeForm;
use yii\helpers\VarDumper;

class OrderController extends Controller
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'login' => [
                'class' => LoginBehavior::className(),
            ],
        ]);
    }

    //订单提交前的预览页面
    public function actionSubmitPreview()
    {
        $form = new OrderSubmitPreviewForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }

    //订单提交
    public function actionSubmit()
    {
        $form = new OrderSubmitForm();
        $model = \Yii::$app->request->post();
        if($model['offline'] == 0){
            $form->scenario = "EXPRESS";
        }else{
            $form->scenario = "OFFLINE";
        }
        $form->attributes = $model;
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $form->version = $this->version;
        $this->renderJson($form->save());
    }

    //订单支付数据
    public function actionPayData()
    {
        $form = new OrderPayDataForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user = \Yii::$app->user->identity;
        $this->renderJson($form->search());
    }

    //订单列表
    public function actionList()
    {
        $form = new OrderListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }

    //订单取消
    public function actionRevoke()
    {
        $form = new OrderRevokeForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //订单确认收货
    public function actionConfirm()
    {
        $form = new OrderConfirmForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //订单各个状态数量
    public function actionCountData()
    {
        $res = OrderListForm::getCountData($this->store->id, \Yii::$app->user->id);
        return $this->renderJson([
            'code' => 0,
            'msg' => 'success',
            'data' => $res,
        ]);
    }

    //订单详情
    public function actionDetail()
    {
        $form = new OrderDetailForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }

    //售后页面
    public function actionRefundPreview()
    {
        $form = new OrderRefundPreviewForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }

    //售后提交
    public function actionRefund()
    {
        $form = new OrderRefundForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }
    //获取订单中的  合伙人订单
    public function actionPartnerByOrderGet(){

        $postData = \Yii::$app->request->get();
        if(empty($postData['page']))
            $postData['page'] = 1;
        //查询合伙人订单
        $partnerOrders =  Order::find()->select('`id`,`order_no`,`total_price`,`pay_price`,addtime,is_delete')->where('`store_id` = '.$this->store->id.' AND `partner_id` = 0 AND is_delete = 0 ')->limit(10)->offset($postData['page'])->asArray()->orderBy('id DESC')->all();

        //合并 订单 与 商品
        foreach ($partnerOrders as $key => $value) {
            $goods = [];
            $order_id = $partnerOrders[$key]['id'];
            $partnerOrders[$key]['addtime'] = date('Y-m-d H:i',$partnerOrders[$key]['addtime']);
            $orderDetail = OrderDetail::find()->select('goods_id,attr,pic,num')->where('`is_delete` = 0 AND `order_id` = ' . $order_id)->asArray()->all();


            foreach ($orderDetail as $ik => $iv) {
                $goods_id = $orderDetail[$ik]['goods_id'];
                $orderDetail[$ik]['attr'] = json_decode($orderDetail[$ik]['attr']);

                $goods[$ik] = Goods::find()->select('`name`,`price`')->where('`id` ='.$goods_id)->asArray()->one();
                $partnerOrders[$key]['goodInfo'][$ik] =  $goods[$ik];

                $partnerOrders[$key]['goodInfo'][$ik] = array_merge($partnerOrders[$key]['goodInfo'][$ik],$orderDetail[$ik]);


            }



         //   print_r($partnerOrders);
        }
        if(empty($partnerOrders))
            return json_encode(['code'=>2,'msg'=>'没有更多了']);
        return json_encode(['code'=>0,'msg'=>'成功','partnerOrders'=>$partnerOrders]);


    }
    //售后订单详情
    public function actionRefundDetail()
    {
        $form = new OrderRefundDetailForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }

    //评论预览页面
    public function actionCommentPreview()
    {
        $form = new OrderCommentPreview();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }

    //评论提交
    public function actionComment()
    {
        $form = new OrderCommentForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //订单物流信息
    public function actionExpressDetail()
    {
        $form = new ExpressDetailForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }
    //核销订单
    public function actionClerk()
    {
        $form = new OrderClerkForm();
        $form->order_id = \Yii::$app->request->get('order_id');
        $form->order_no = \Yii::$app->request->get('order_no');
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }
    //核销订单详情
    public function actionClerkDetail()
    {
        if(\Yii::$app->user->identity->is_clerk != 1){
            $this->renderJson([
                'code' => 1,
                'msg' => '不是核销员禁止核销'
            ]);
        }
        $form = new OrderDetailForm();
        $form->order_no = \Yii::$app->request->get('order_no');
        $form->store_id = $this->store->id;
//        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->clerk());
    }
    public function actionGetQrcode()
    {
        $order_no = \Yii::$app->request->get('order_no');
        $order = Order::findOne(['order_no'=>$order_no,'store_id'=>$this->store->id]);
        $form = new QrcodeForm();
//        $form->order_no = $order_no;
        $form->data = [
            'scene'=>"{$order_no}",
            'page'=>"pages/clerk/clerk",
            'width'=>100
        ];
        $form->store = $this->store;
        $res = $form->getQrcode();
        return json_encode($res,JSON_UNESCAPED_UNICODE);
    }

    public function actionLocation()
    {
        $form = new LocationForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        $this->renderJson($form->search());
    }
}