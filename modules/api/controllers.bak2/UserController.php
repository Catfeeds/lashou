<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/18
 * Time: 11:28
 */

namespace app\modules\api\controllers;


use app\models\Address;
use app\models\FormId;
use app\models\Lashou;
use app\models\Level;
use app\models\Option;
use app\models\Order;
use app\models\OrderDetail;
use app\models\Goods;
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
use app\modules\api\models\ActivityConfig;
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

class UserController extends Controller
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'login' => [
                'class' => LoginBehavior::className(),
            ],
        ]);
    }

    //个人中心
    public function actionIndex()
    {
        $order_count = OrderListForm::getCountData($this->store->id, \Yii::$app->user->id);
        $share_setting = Setting::find()->where(['store_id' => $this->store->id])->asArray()->one();
        $parent = User::findOne(\Yii::$app->user->identity->parent_id);
        $share = Share::findOne(['user_id' => \Yii::$app->user->identity->parent_id]);

        $user = User::findOne(['id' => \Yii::$app->user->identity->id]);
        $level = $user->level;

        $nowDay = strtotime(date('Y-m-d',time()));

        $nowSales = Order::find()->where(['user_id'=>$user->id,'is_pay'=>1,'apply_delete'=>0])->
        andWhere(['>=','pay_time',$nowDay])->sum('pay_price');

        $sy_amount1 = Order::find()->where(['parent_id'=>$user->id,'is_pay'=>1,'apply_delete'=>0,'is_confirm'=>1,'is_price'=>1])->
        andWhere(['>=','pay_time',$nowDay])->
        andWhere(['>','first_price',0])->sum('first_price');

        $sy_amount2 = Order::find()->where(['parent_id_1'=>$user->id,'is_pay'=>1,'apply_delete'=>0,'is_confirm'=>1,'is_price'=>1])->
        andWhere(['>=','pay_time',$nowDay])->
        andWhere(['>','second_price',0])->sum('second_price');

        $sy_amount3 = Order::find()->where(['user_id'=>$user->id,'is_pay'=>1,'apply_delete'=>0,'is_confirm'=>1,'is_price'=>1])->
        andWhere(['>=','pay_time',$nowDay])->
        andWhere(['>','rebate',0])->sum('rebate');

        $order_market = Order::find()->where(['partner_id'=>0,'is_pay'=>1,'apply_delete'=>0,'is_send'=>0,'is_price'=>0])
            ->andWhere(['!=','partner_id',1])
            ->andWhere(['!=','user_id',$user->id])
            ->count();

        $order_now = Order::find()->
        where(['partner_id'=>$user->id,'is_pay'=>1,'apply_delete'=>0])->
        andWhere(['>=','pay_time',$nowDay])->count();

        $now_shouyi = Order::find()->
        where(['partner_id'=>$user->id,'is_pay'=>1,'apply_delete'=>0,'is_confirm'=>1,'is_price'=>1])->
        andWhere(['>=','pay_time',$nowDay])->sum('pay_price - rebate - first_price - second_price - third_price - pt_amount');
        if($nowDay >= strtotime(Lashou::DAI_FA_START_TIME)){
            $order_query = Order::find()
                ->where(['partner_id'=>$user->id,'is_pay'=>1,'apply_delete'=>0,'is_confirm'=>1,'is_price'=>1])
                ->andWhere(['>=','pay_time',$nowDay])
                ->select('id');

            $partner_shipping_fee = $order_query->sum('partner_shipping_fee');
            $express_price = $order_query->sum('express_price');
            $shipping_fee = max($partner_shipping_fee, $express_price);

            $order_ids = $order_query->column();
            if(count($order_ids) > 0){
                $order_goods_cost = OrderDetail::find()->alias('og')
                    ->leftJoin(Goods::tableName() . ' as g', 'og.goods_id = g.id')
                    ->where(['in', 'og.order_id', $order_ids])
                    ->sum('og.num * g.cost_price');
                $now_shouyi -= $shipping_fee + $order_goods_cost;
            }

        }

        $sy_amount = $sy_amount1 + $sy_amount2 + $sy_amount3;

        if(empty($nowSales))
            $nowSales = 0;
        if(empty($now_shouyi))
            $now_shouyi = 0;


        $teamcount = ['son' => 0, 'grand' => 0];
        $team_son_query = User::find()->where(['parent_id' => \Yii::$app->user->identity->id])->select('id');
        $teamcount['son'] = $team_son_query->count();
        $team_son_ids = $team_son_query->column();
        $teamcount['grand'] = User::find()->where(['in', 'parent_id', $team_son_ids])->count();

        $myData = [
            'nowSales'=>$nowSales,
            'sy_amount'=>$sy_amount,
            'sy_point'=>$user->total_integral,
            'now_shouyi'=>$now_shouyi,
            'order_now'=>$order_now,
            'order_market'=>0,
            'team_count' => $teamcount['son'] + $teamcount['grand'],
            //'team' => $teamcount,
            //'team_son_sql' => $team_son_query->createCommand()->getRawSql(),
            //'team_grand_sql' => User::find()->where(['in', 'parent_id', $team_son_ids])->createCommand()->getRawSql(),
        ];//$order_market

        $now_level = Level::findOne(['store_id' => $this->store->id, 'level' => $level, 'is_delete' => 0]);
        $user_info = [
            'nickname' => \Yii::$app->user->identity->nickname,
            'binding' => $user->binding,
            'avatar_url' => \Yii::$app->user->identity->avatar_url,
            'is_distributor' => \Yii::$app->user->identity->is_distributor,
//            'parent' => $share ? $share->name : ($parent ? $parent->nickname : '总店'),
            'parent' => $share ? ($share->name ? $share->name : $parent->nickname) : "总店",
            'is_partner' => \Yii::$app->user->identity->is_partner,
            'id' => \Yii::$app->user->identity->id,
            'is_clerk' => \Yii::$app->user->identity->is_clerk,
            'level' => $level,
            'level_name' => $now_level ? $now_level->name : "普通用户",
            'integral' => \Yii::$app->user->identity->integral,
            'money'=>\Yii::$app->user->identity->money
        ];
	$user_info['parent'] = $user_info['parent'] == null ? "总店" : $user_info['parent'];
        $next_level = Level::find()->where(['store_id' => $this->store->id, 'is_delete' => 0, 'status' => 1])
            ->andWhere(['>', 'level', $level])->orderBy(['level' => SORT_ASC, 'id' => SORT_DESC])->asArray()->one();

        //余额功能配置
        $balance = Option::get('re_setting',$this->store->id,'app');
        $balance = json_decode($balance,true);
        //我的钱包 选项
        $wallet['integral'] = 1;
        if($balance){
            $wallet['re'] = $balance['status'];
        }

        /* 旧版的菜单列表先保留以兼容旧版，后期去掉 */
        $user_center_menu = new UserCenterMenu();
        $user_center_menu->store_id = $this->store->id;

        $user_center_form = new UserCenterForm();
        $user_center_form->store_id = $this->store->id;
        $user_center_form->user_id = $user->id;
        $user_center_data = $user_center_form->getData();
        $user_center_data = $user_center_data['data'];
        return $this->renderJson([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'myData'=>$myData,
                'order_count' => $order_count,
                'show_customer_service' => $this->store->show_customer_service,
                'contact_tel' => $this->store->contact_tel,
                'share_setting' => $share_setting,
                'user_info' => $user_info,
                'next_level' => $next_level,
                'menu_list' => $user_center_menu->getMenuList(),
                'user_center_bg' => $user_center_data['user_center_bg'],
                'orders' => $user_center_data['orders'],
                'menus' => $user_center_data['menus'],
                'copyright' => $user_center_data['copyright'],
                'wallet'=>$wallet,
                'style'=>[
                    'menu'=>$user_center_data['menu_style'],
                    'top'=>$user_center_data['top_style'],
                ]
            ],
            'can_share_activity' => in_array($user->id, ActivityConfig::getCanShareActivityUsers())
        ]);
    }


    //    短信验证是否开启
    public function actionSmsSetting()
    {
        $sms_setting = SmsSetting::findOne(['is_delete' => 0, 'store_id' => $this->store->id]);
        if($sms_setting->status == 1){
            $this->renderJson([
                'code'=>0,
                'data'=>$sms_setting->status
            ]);
        }else{
            $this->renderJson([
                'code'=>1,
                'data'=>$sms_setting->status
            ]);
        }
    }
    //    绑定手机号
    public function actionUserBinding()
    {
        $form = new UserForm();
        $user = \Yii::$app->user->identity;
        $form->attributes = \Yii::$app->request->post();


        $form->user_id = $user->id;
        $form->session_key = $user->session_key;

        $form->store_id = $this->store->id;
        $form->appId = $this->wechat->appId;
        $form->wechat_app = $this->wechat_app;

        $this->renderJson($form->binding());
    }
    //    短信验证
    public function actionUserHandBinding()
    {
        $form = new Sms();
        $form->attributes = \Yii::$app->request->post();
        $code = mt_rand(0,999999);
        $this->renderJson($form->send_text($this->store->id,$code,$form->attributes['content']));
    }
//    授权手机号确认
    public function actionUserEmpower()
    {
        $form = new UserForm();
        $form->attributes = \Yii::$app->request->post();
        $form->user_id = \Yii::$app->user->identity->id;
        $form->store_id = $this->store->id;
        $this->renderJson($form->userEmpower());
    }

    //收货地址列表
    public function actionAddressList()
    {
        $list = Address::find()->select('id,name,mobile,province_id,province,city_id,city,district_id,district,detail,is_default')->where([
            'store_id' => $this->store->id,
            'user_id' => \Yii::$app->user->id,
            'is_delete' => 0,
        ])->orderBy('is_default DESC,addtime DESC')->asArray()->all();
        foreach ($list as $i => $item) {
            $list[$i]['address'] = $item['province'] . $item['city'] . $item['district'] . $item['detail'];
        }
        $this->renderJson((object)[
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'list' => $list,
            ],
        ]);
    }

    /**
     * 会员支付
     */
    public function actionSubmitMember()
    {
        $form = new OrderMemberForm(); 
        $form->store_id = $this->store->id;
        $form->user = \Yii::$app->user->identity;
        $form->attributes = \Yii::$app->request->post();
        $this->renderJson($form->save());
    }
    //收货地址详情
    public function actionAddressDetail()
    {
        $address = Address::find()->select('id,name,mobile,province_id,province,city_id,city,district_id,district,detail,is_default')->where([
            'store_id' => $this->store->id,
            'user_id' => \Yii::$app->user->id,
            'is_delete' => 0,
            'id' => \Yii::$app->request->get('id'),
        ])->one();
        if (!$address) {
            $this->renderJson([
                'code' => 1,
                'msg' => '收货地址不存在',
            ]);
        }
        $this->renderJson((object)[
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'address_id' => $address->id,
                'name' => $address->name,
                'mobile' => $address->mobile,
                'district' => [
                    'province' => [
                        'id' => $address->province_id,
                        'name' => $address->province,
                    ],
                    'city' => [
                        'id' => $address->city_id,
                        'name' => $address->city,
                    ],
                    'district' => [
                        'id' => $address->district_id,
                        'name' => $address->district,
                    ],
                ],
                'detail' => $address->detail,
            ],
        ]);
    }

    //保存收货地址
    public function actionAddressSave()
    {
        $form = new AddressSaveForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //设为默认收货地址
    public function actionAddressSetDefault()
    {
        $form = new AddressSetDefaultForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //删除收货地址
    public function actionAddressDelete()
    {
        $form = new AddressDeleteForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }


    //保存用户的form id
    public function actionSaveFormId()
    {
        if (!\Yii::$app->user->isGuest) {
            FormId::addFormId([
                'store_id' => $this->store->id,
                'user_id' => \Yii::$app->user->id,
                'wechat_open_id' => \Yii::$app->user->identity->wechat_open_id,
                'form_id' => \Yii::$app->request->get('form_id'),
                'type' => 'form_id',
            ]);
            UserFormId::saveFormId([
                'user_id' => \Yii::$app->user->id,
                'form_id' => \Yii::$app->request->get('form_id'),
            ]);
        }
    }

    //添加商品到我的喜欢
    public function actionFavoriteAdd()
    {
        $form = new FavoriteAddForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //取消喜欢商品
    public function actionFavoriteRemove()
    {
        $form = new FavoriteRemoveForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //喜欢的商品列表
    public function actionFavoriteList()
    {
        $form = new FavoriteListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }

    //根据微信地址获取数据库省市区数据
    public function actionWechatDistrict()
    {
        $form = new WechatDistrictForm();
        $form->attributes = \Yii::$app->request->get();
        $this->renderJson($form->search());
    }

    //添加微信获取的地址
    public function actionAddWechatAddress()
    {
        $form = new AddWechatAddressForm();
        $form->attributes = \Yii::$app->request->post();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //收藏|取消收藏专题
    public function actionTopicFavorite()
    {
        $form = new TopicFavoriteForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->save());
    }

    //收藏专题列表
    public function actionTopicFavoriteList()
    {
        $form = new TopicFavoriteListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->id;
        $this->renderJson($form->search());
    }  

    //会员权益
    public function actionMember()
    {
        $level = \Yii::$app->user->identity->level;
        $money = \Yii::$app->user->identity->money;

 
        $list = Level::find()->select(['id','image','level','name','price','buy_prompt','detail'])->where(['store_id' => $this->store->id,'is_delete' => 0])->andWhere(['>', 'level', $level])->orderBy('level asc')->asArray()->all();

        $now_level = Level::find()->where(['store_id' => $this->store->id, 'level' => $level, 'is_delete' => 0])->asArray()->one();
        $user_info = [
            'nickname' => \Yii::$app->user->identity->nickname,
            'avatar_url' => \Yii::$app->user->identity->avatar_url,
            'id' => \Yii::$app->user->identity->id,
            'level' => $level,
            'level_name' => $now_level ? $now_level['name'] : "普通用户"
        ];
        $time = time();
        $store = $this->store;
        $sale_time = $time - ($store->after_sale_time * 86400);
        $next_level = Level::find()->where(['store_id' => $this->store->id, 'is_delete' => 0, 'status' => 1])
            ->andWhere(['>', 'level', $level])->orderBy(['level' => SORT_ASC, 'id' => SORT_DESC])->asArray()->one();
        $order_money = Order::find()->where(['store_id' => $this->store->id, 'user_id' => \Yii::$app->user->identity->id, 'is_delete' => 0])
            ->andWhere(['is_pay' => 1, 'is_confirm' => 1])->andWhere(['<=', 'confirm_time', $sale_time])->select([
                'sum(pay_price)'
            ])->scalar();
        $percent = 100;
        $s_money = 0;
        if ($next_level) {
            $percent = round($order_money / $next_level['money'] * 100, 2);
            $s_money = round($next_level['money'] - $order_money, 2);
        }
        $content = $store->member_content;
        $order_money = $order_money ? $order_money : 0;
        return $this->renderJson([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'user_info' => $user_info,
                'next_level' => $next_level,
                'now_level' => $now_level,
                'order_money' => $order_money,
                'percent' => $percent,
                's_money' => $s_money,
                'money' => $money,
                'content' => $content,
                'list' => $list,
            ],
        ]);
    }

    /**
     * 用户卡券
     */
    public function actionCard()
    {
        $form = new CardListForm();
        $form->store_id = $this->store->id;
        $form->user_id = \Yii::$app->user->identity->id;
        $form->attributes = \Yii::$app->request->get();
        $this->renderJson($form->search());
    }

    /**
     * 卡券二维码
     */
    public function actionCardQrcode()
    {
        $user_card_id = \Yii::$app->request->get('user_card_id');
        $user_card = UserCard::findOne(['id' => $user_card_id]);
        $form = new QrcodeForm();
        $form->data = [
            'scene' => "{$user_card_id}",
            'page' => 'pages/card-clerk/card-clerk',
            'width' => 100
        ];
        $form->store = $this->store;
        $res = $form->getQrcode();
        $this->renderJson($res);
    }

    /**
     * 卡券核销
     */
    public function actionCardClerk()
    {
        $user_card_id = \Yii::$app->request->get('user_card_id');
        if (\Yii::$app->cache->get('card_id_' . $user_card_id)) {
            $this->renderJson([
                'code' => 1,
                'msg' => '卡券核销中，请稍后重试'
            ]);
        }
        \Yii::$app->cache->set('card_id_' . $user_card_id, true);
        $user_card = UserCard::findOne(['id' => $user_card_id]);
        if ($user_card->is_use != 0) {
            $this->renderJson([
                'code' => 1,
                'msg' => '卡券已核销'
            ]);
        }
        $user = \Yii::$app->user->identity;
        if ($user->is_clerk != 1) {
            $this->renderJson([
                'code' => 1,
                'msg' => '不是核销员禁止核销'
            ]);
        }
        $user_card->clerk_id = $user->id;
        $user_card->shop_id = $user->shop_id;
        $user_card->clerk_time = time();
        $user_card->is_use = 1;
        if ($user_card->save()) {
            \Yii::$app->cache->set('card_id_' . $user_card_id, false);
            $this->renderJson([
                'code' => 0,
                'msg' => '核销成功'
            ]);
        } else {
            $this->renderJson([
                'code' => 1,
                'msg' => '核销失败'
            ]);
        }
    }

    public function actionWebLogin($token)
    {
        $m = UserAuthLogin::findOne([
            'token' => $token,
            'store_id' => $this->store->id,
        ]);
        if (!$m) {
            return $this->renderJson([
                'code' => 1,
                'msg' => '错误的小程序码，请刷新网页重试'
            ]);
        }
        if ($m->is_pass != 0) {
            return $this->renderJson([
                'code' => 1,
                'msg' => '您已处理过，请勿重复提交'
            ]);
        }
        $m->user_id = \Yii::$app->user->id;
        $m->is_pass = 1;
        $m->save();
        return $this->renderJson([
            'code' => 0,
            'msg' => '已确认登录'
        ]);
    }
}