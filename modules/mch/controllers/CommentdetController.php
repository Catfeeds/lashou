<?php
/**
 * Created by IntelliJ IDEA.
 * User: 少年犹未解
 * Date: 2018/6/12
 * Time: 2:53
 */

namespace app\modules\mch\controllers;

use app\models\Cat;
use app\models\Goods;
use app\models\Lashou;
use app\models\OrderComment;
use app\models\User;
use app\modules\mch\models\OrderCommentForm;
use yii\data\Pagination;
use yii\helpers\Html;

class CommentdetController extends Controller
{
    public function actionIndex()
    {
        
    }
    public function actionUserdet() {
        $data = \Yii::$app->request->get();
        $uid = $data['id'];
        if(!empty($uid)){
           //通过用户信息找订单表中的佣金
            $list = \Yii::$app->db->createCommand("SELECT id,order_no,user_id, pay_time, express_price, total_price,rebate,first_price,second_price,third_price,parent_id,parent_id_1,parent_id_2,partner_id,pt_amount,is_price FROM `hjmallind_order` WHERE ((user_id =$uid and rebate>0) or (parent_id=$uid AND first_price>0) or (parent_id_1=$uid AND second_price>0) or (parent_id_2=$uid AND third_price>0) or (partner_id=$uid)) AND is_pay=1 AND is_delete=0;")->queryAll();
            //print_r($list); 
            $hejicomm = 0;
            $hejipart = 0;
            $hejicomm2 = 0;
            $hejipart2 = 0;
            $zongheji = 0;
            
            foreach ($list as $s=>$v){
                if($v['user_id']==$uid){
                    $list[$s]['commstr'] = '自购返利';
                    $list[$s]['commmoney'] = $v['rebate'];
                }else if($v['parent_id']==$uid){
                    $list[$s]['commstr'] = '一级返利';
                    $list[$s]['commmoney'] = $v['first_price'];
                }else if($v['parent_id_1']==$uid){
                    $list[$s]['commstr'] = '二级返利';
                    $list[$s]['commmoney'] = $v['second_price'];
                }else if($v['parent_id_2']==$uid){
                    $list[$s]['commstr'] = '三级返利';
                    $list[$s]['commmoney'] = $v['third_price'];
                }else{
                    $list[$s]['commstr'] = '无';
                    $list[$s]['commmoney'] = 0;
                }
                if($v['partner_id']==$uid){
                    $list[$s]['partner_money'] = $v['total_price']-$v['rebate']-$v['first_price']-$v['second_price']-$v['third_price']-$v['pt_amount'];

                    if($v['pay_time'] >= strtotime(Lashou::DAI_FA_START_TIME)){
                        $list[$s]['partner_money'] -= Lashou::getGoodsCost($v['id']) + $v['express_price'];
                    }
                    
                }else{
                    $list[$s]['partner_money'] = 0;
                }
                if($v['is_price']==1){
                    $hejicomm+=$list[$s]['commmoney'];
                    $hejipart+=$list[$s]['partner_money'];
                }else{
                    $hejicomm2+=$list[$s]['commmoney'];
                    $hejipart2+=$list[$s]['partner_money'];
                }
            }
        }else{
            
        }
        return $this->render('userdet', [
            'list' => $list,
            'hejicomm' => $hejicomm,
            'hejipart' => $hejipart,
            'hejicomm2' => $hejicomm2,
            'hejipart2' => $hejipart2,
        ]);
    }
    public function actionSharedet()
    {
        $data = \Yii::$app->request->get();
        $uid = $data['id'];
        if(!empty($uid)){
            $list = \Yii::$app->db->createCommand("SELECT sd.user_id,sd.from_id,sd.price,sd.add_time,ro.order_no,is_pay FROM `hjmallind_share_detailed` sd LEFT JOIN hjmallind_re_order ro ON sd.from_id=ro.user_id AND ro.is_pay=1 AND ro.is_parent>=1  WHERE sd.user_id=$uid;")->queryAll();
            //print_r($list);
            
        } 
        return $this->render('sharedet', [
            'list' => $list
        ]);
    }
}