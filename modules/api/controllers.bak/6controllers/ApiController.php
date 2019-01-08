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
use app\models\Cat;
use app\models\FormId;
use app\models\Goods;
use app\models\LsBonusLog;
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

class ApiController extends Controller
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
        ]);
    }
    //红包提现接口
    //url=http://192.168.2.116/index.php?store_id=1&r=api/api/cash&access_token=0T-XIOVz56kvQdGuApDPJuL0j5F4tzUv&_uniacid=-1&_acid=-1&cash=10&pay_type=0
    public function actionCash(){
        $form = new CashForm();
        $user = new User();
        //$uid = \Yii::$app->user->id;
        $userinfo = $user::findOne(['id' => $uid, 'store_id' => $this->store_id]);
        $form->user_id = \Yii::$app->user->identity->id;
        $form->pay_type = 0;
        $form->cash = \Yii::$app->user->identity->ls_price;
        $form->store_id = $this->store_id;
        $form->attributes = \Yii::$app->request->get();
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
}