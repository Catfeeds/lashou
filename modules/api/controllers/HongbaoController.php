<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/6/19
 * Time: 15:15
 */

namespace app\modules\api\controllers;


use app\models\AppNavbar;
use app\models\Article;
use app\models\Banner;
use app\models\Cash;
use app\models\CashWechatTplSender;
use app\models\Cat;
use app\models\FormId;
use app\models\Goods;
use app\models\LsBonusLog;
use app\models\LsTplMessage;
use app\models\LsWechatFormId;
use app\models\Option;
use app\models\Setting;
use app\models\Store;
use app\models\UploadConfig;
use app\models\UploadForm;
use app\models\User;
use app\modules\api\behaviors\LoginBehavior;
use app\modules\api\models\CashForm;
use app\modules\api\models\CatListForm;
use app\modules\api\models\CommentListForm;
use app\modules\api\models\CouponListForm;
use app\modules\api\models\DistrictForm;
use app\modules\api\models\GoodsAttrInfoForm;
use app\modules\api\models\GoodsForm;
use app\modules\api\models\GoodsListForm;
use app\modules\api\models\GoodsQrcodeForm;
use app\modules\api\models\IndexForm;
use app\modules\api\models\LsBonusLogForm;
use app\modules\api\models\ShopListForm;
use app\modules\api\models\TopicForm;
use app\modules\api\models\TopicListForm;
use app\modules\api\models\VideoForm;
use app\modules\api\models\ShopForm;
use Curl\Curl;
use yii\data\Pagination;
use yii\helpers\VarDumper;
use app\modules\api\models\TopicTypeForm;
use app\models\Lashou;
use yii\base\Exception;

class HongbaoController extends Controller
{
    const hongbao_rule = "1.只有加入新用户可以参与本次活动(星级用户不参与),首次进入可领取5元现金红包.
2.通过分享指定红包链接点击(已点击不能重复点击)/每日签到获取随机的红包金额.
3.红包满10元且成为星级用户后红包可提交提现申请，超过10元部分不参与累计.
4.提现后红包可直接用于余额消费或提现到您的微信钱包。
本活动最终解释权归浙江拉手贸易有限公司所有.";
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
        ]);
    }
    //红包提现接口
    //url=http://192.168.2.116/index.php?store_id=1&r=api/api/cash&access_token=0T-XIOVz56kvQdGuApDPJuL0j5F4tzUv&_uniacid=-1&_acid=-1&cash=10&pay_type=0
    public function actionCash(){
        $MUser = User::findOne(\Yii::$app->user->identity->id);
        if($MUser->is_distributor != 1){
            $this->renderJson([
                'code' => 501,
                'msg' => '成为星级用户才能提现'
            ]);
        }

        $form = new CashForm();
        $form->user_id = \Yii::$app->user->identity->id;
        $form->pay_type = 0;
        $hongbao_total =(float)Cash::find()->where(['user_id'=>\Yii::$app->user->identity->id,'status'=>2,'hongbao'=>1])->sum('price');
        if($hongbao_total>=10){
            return json_encode([
                'code' => 1,
                'msg' => '本次活动最多提现10.00元'
            ],JSON_UNESCAPED_UNICODE);
        }
        $cash = \Yii::$app->user->identity->ls_price;
        if($cash>=10){
            $form->cash = 10;
        }else{
            return json_encode([
                'code' => 1,
                'msg' => '当前账户余额'.\Yii::$app->user->identity->ls_price.'元，还差'.(10 - \Yii::$app->user->identity->ls_price). '元即可提现~'
            ],JSON_UNESCAPED_UNICODE);
        }
//        $form->cash = \Yii::$app->user->identity->ls_price;
        $form->store_id = $this->store_id;
        $form->attributes = \Yii::$app->request->get();
       // $result = $form->ls_save();
//        if($result['code'] == 0){
//            $id = $result['id'];
//            $cash = Cash::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
//            $cash->status = 2;
//            $cash->pay_time = time();
//            $cash->pay_type = 1;
//            $user = User::findOne(['id' => $cash->user_id]);
//            $data = [
//                'partner_trade_no' => $cash->order_no,
//                'openid' => $user->wechat_open_id,
//                'amount' => $cash->price * 100,
//                'desc' => '转账'
//            ];
//            $res = $this->wechat->pay->transfers($data);
//            \Yii::$app->cache->set('cash_cache_' . $id, false);
//            if ($res['result_code'] == 'SUCCESS') {
//                $cash->save();
//                $wechat_tpl_meg_sender = new CashWechatTplSender($this->store->id, $cash->id, $this->wechat);
//                $wechat_tpl_meg_sender->cashMsg();
//                return json_encode([
//                    'code' => 0,
//                    'msg' => '提现成功'
//                ], JSON_UNESCAPED_UNICODE);
//            } else {
//                return json_encode([
//                    'code' => 1,
//                    'msg' => $res['err_code_des'],
//                    'data' => $res
//                ], JSON_UNESCAPED_UNICODE);
//            }
//        }else{

//        }
        return json_encode($form->ls_save(), JSON_UNESCAPED_UNICODE);

    }

    //是否发放新人红包

    public function  actionLsbonuslog(){
        $res = LsBonusLog::findOne(['uid' => \Yii::$app->user->identity->id, 'type' => 1 ,'extension_id' => 1]);
        if(!empty($res)){
            return json_encode(array('code'=>1,'data'=>array('val'=>$res['val']),'msg'=>'error'),JSON_UNESCAPED_UNICODE);
        }else{
            return json_encode(array('code'=>0,'data'=>array('val'=>''),'msg'=>'success'),JSON_UNESCAPED_UNICODE);
        }
    }

    //新人第一次领取红包

    public  function actionNewerGet(){
        $user_id = \Yii::$app->user->identity->id;
        if(empty($user_id)){
            return $this->renderJson([
                'code' => -1,
                'msg' => '请先登录'
            ]);
        }

        if(! Lashou::canJoinActivity()){
            return $this->renderJson([
                'code' => 800,
                'msg' => '您不能参与该次活动'
            ]);
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            //1.加入红包记录
            $form = new LsBonusLog();
            $form->uid = $user_id;
            $form->add_time = time();
            $form->extension_id = 1;
            $form->add_time = time();
            $form->val = 500;
            $form->type = 1;
            $form->log = '新人红包';
            $res = $form -> save();

            //2.金额累加
            if($res){
                $user = new User();
                $userarr = $user::findOne(['id'=>$user_id]);
                $userarr->ls_price += 5;

                if($userarr->save()){
                }else{
                    throw new Exception('用户红包余额更新失败',802);
                }

            }else{
                throw new Exception('红包日志写入失败',801);
            }

            //3.如果记录条数大于1，回滚
            $res = LsBonusLog::find()->where(['uid' => $user_id, 'type' => 1 ,'extension_id' => 1])->count();
            if($res > 1){
                throw new Exception('您已参与该活动',800);
            }else{
                $transaction->commit();

                //发送消息

                if(isset($_REQUEST['form_id']) && !empty($_REQUEST['form_id'])){
                    $form_id = $_REQUEST['form_id'];
                    $MUser = User::findOne($user_id);

                    $wechat_form_id = new LsWechatFormId();
                    $wechat_form_id->setParams($MUser->wechat_open_id, $form_id);

                    LsTplMessage::send_tpl_message_by_newer_bonus($wechat_form_id, date('Y年m月d日 H时i分s秒'));
                }else{
                    $wechat_form_id = LsWechatFormId::find()
                        ->where(['user_id' => $user_id])
                        ->andWhere(['>', 'send_count', 0])
                        ->one();
                    LsTplMessage::send_tpl_message_by_newer_bonus($wechat_form_id, date('Y年m月d日 H时i分s秒'));
                    $wechat_form_id->delete();
                }

                return $this->renderJson(['data' => ['val' => 5]]); 
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            return $this->renderJson([
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ]);
        }
    }



    //红包记录

    public function actionLog(){
        $uid = \Yii::$app->user->identity->id;
        $hongbao_total =(float) LsBonusLog::find() -> where(['uid' => \Yii::$app->user->identity->id, 'type' => 1 ])->sum('val');
        $hongbao_total = number_format($hongbao_total/100,2,'.','');
        $hongbao_leftarr = User::find() -> select('ls_price')-> where(['id' => \Yii::$app->user->identity->id]) -> asArray() ->one();
        $hongbao_left = number_format($hongbao_leftarr['ls_price'],2,'.','');
        $hongbao_cash = Cash::find()->where(['user_id'=>\Yii::$app->user->identity->id,'status'=>2,'hongbao'=>1])->sum('price');
        $hongbao_nocash = Cash::find()->where("user_id = $uid and status IN (0,1) and hongbao=1")->sum('price');
        $hongbao_cash = number_format(($hongbao_cash),2,'.','');
        $hongbao_nocash =  number_format(($hongbao_nocash),2,'.','');
        $log_list = LsBonusLog::find()  -> where(['uid' => \Yii::$app->user->identity->id, 'type' => 1 ])-> asArray() -> all();
        foreach ($log_list as $k=>$val){
            $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            $val['val']=number_format($val['val']/100,2,'.','');
            $log_list[$k] = $val;
        }

        $guessLike = Lashou::guessLike();
        $ruls = self::hongbao_rule;

        $share_tips = "红包活动升级中，全新玩法,即将来袭。";
        $can_cash = false;
        $cash_tips = "成为星级用户才能提现";

        $MUser = User::findOne($uid);
        if($MUser->is_distributor == 1){
            $can_cash = true;
            $cash_tips = "";
        }

        return json_encode(array('code'=>0,'data'=>array('cash_tips' => $cash_tips, 'can_cash' => $can_cash, 'share_tips' => $share_tips, 'rules' => $ruls, 'guess_like' => $guessLike, 'hongbao_total'=>$hongbao_total,'hongbao_nocash'=>$hongbao_nocash,'hongbao_left'=> $hongbao_left,'hongbao_cash'=>$hongbao_cash,'log_list'=>$log_list),'msg'=>'success'),JSON_UNESCAPED_UNICODE);

    }
}