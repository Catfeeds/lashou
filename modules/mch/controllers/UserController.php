<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/8/3
 * Time: 13:51
 */

namespace app\modules\mch\controllers;


use app\models\IntegralLog;
use app\models\Level;
use app\models\Shop;
use app\models\Store;
use app\models\User;
use app\models\UserCoupon;
use app\modules\mch\models\LevelForm;
use app\modules\mch\models\LevelListForm;
use app\modules\mch\models\recharge\UserRechargeForm;
use app\modules\mch\models\UserCardListForm;
use app\modules\mch\models\UserCouponForm;
use app\modules\mch\models\UserForm;
use app\modules\mch\models\UserListForm;
use yii\data\Pagination;
use app\models\Order;
use app\models\LevelOrder;
use app\models\Share;

class UserController extends Controller
{
    public function actionIndex()
    {
        $form = new UserListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $data = $form->search();

//
    //    $level_list = Level::find()->where(['store_id'=>$this->store->id,'is_delete'=>0,'status'=>1])
 //           ->orderBy(['level'=>SORT_ASC])->asArray()->all();
        return $this->render('index', [
            'row_count' => $data['row_count'],
            'pagination' => $data['pagination'],
            'list' => $data['list'],
            //'level_list'=>$level_list
        ]);
    }

    /**
     * @param null $id
     * @param int $status //0--解除核销员  1--设置核销员
     * @param int $edit //0--设置/取消核销员 1--变更门店
     * @return null
     * 设置/取消核销员
     */
    public function actionClerkEdit($id = null, $status = 0, $shop_id = 0, $edit = 0)
    {
        $user = User::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$user) {
            return $this->renderJson([
                'code' => 1,
                'msg' => '网络异常'
            ]);
        }
        if ($status == 1) {
            $shop_exit = Shop::find()->where(['store_id' => $this->store->id, 'is_delete' => 0, 'id' => $shop_id])->exists();
            if (!$shop_exit) {
                return $this->renderJson([
                    'code' => 1,
                    'msg' => '店铺不存在'
                ]);
            }
            $user->shop_id = $shop_id;
        }
        if ($edit == 0) {
            if ($user->is_clerk == $status) {
                $this->renderJson([
                    'code' => 1,
                    'msg' => '网络异常'
                ]);
            }
            $user->is_clerk = $status;
            if($status == 0){
                $user->shop_id = 0;
            }
        }
        if ($user->save()) {
            $this->renderJson([
                'code' => 0,
                'msg' => '成功'
            ]);
        } else {
            $this->renderJson([
                'code' => 1,
                'msg' => '网络异常'
            ]);
        }
    }

    /**
     * @return string
     * 核销员列表
     */
    public function actionClerk()
    {
        User::updateAll(['shop_id'=>0],['is_clerk'=>0]);
        $form = new UserListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->is_clerk = 1;
        $data = $form->search();
        $data_list = $form->getUser();

        $clerk=array();
        foreach($data['list'] as $k=>$v){
            $clerk[] = $v['id'];
        };
        $detail = Order::find()->where(['in','clerk_id',$clerk])->andwhere(['is_pay'=>1,'store_id' => $this->store->id])->asArray()->all();
        
        foreach($data['list'] as $k => $v){
            $pay_price = 0;
            foreach($detail as $k1 => $v1){
                if($v1['clerk_id']==$v['id']){
                    $pay_price +=$v1['pay_price'];
                }
            }
            $data['list'][$k]['total_price'] = $pay_price;
        }

        $shop_list = Shop::find()->where(['store_id' => $this->store->id, 'is_delete' => 0])->asArray()->all();
        return $this->render('clerk', [
            'row_count' => $data['row_count'],
            'pagination' => $data['pagination'],
            'list' => $data['list'],
            'user_list' => json_encode($data_list, JSON_UNESCAPED_UNICODE),
            'shop_list' => json_encode($shop_list, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function actionGetUser()
    {
        $form = new UserListForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $data_list = $form->getUser();
        return json_encode($data_list, JSON_UNESCAPED_UNICODE);
    }

    public function actionDel($id = null)
    {
        $user = User::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        if (!$user) {
            return $this->renderJson([
                'code' => 1,
                'msg' => '用户不存在'
            ]);
        }
        $user->is_delete = 1;
        $user->wechat_open_id='';
        if ($user->save()) {
            $this->renderJson([
                'code' => 0,
                'msg' => '成功'
            ]);
        } else {
            $this->renderJson([
                'code' => 1,
                'msg' => '网络异常'
            ]);
        }
    }

    public function actionCoupon()
    {
        $form = new UserCouponForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        $form->limit = 10;
        $arr = $form->search();
        $data = $form->getCountData();
        $user_id = \Yii::$app->request->get('user_id');
        $user = User::findOne(['store_id' => $this->store->id, 'id' => $user_id]);
        return $this->render('coupon', [
            'row_count' => $arr['row_count'],
            'pagination' => $arr['pagination'],
            'list' => $arr['list'],
            'data' => $data,
            'user' => $user
        ]);
    }

    public function actionCouponDel($id = null)
    {
        $user_coupon = UserCoupon::findOne(['id' => $id, 'store_id' => $this->store->id]);
        if (!$user_coupon)
            return json_encode([
                'code' => 1,
                'msg' => '网络异常_1'
            ], JSON_UNESCAPED_UNICODE);
        $user_coupon->is_delete = 1;
        if ($user_coupon->save()) {
            return json_encode([
                'code' => 0,
                'msg' => '成功'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode([
                'code' => 1,
                'msg' => '网络异常'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 会员等级
     */
    public function actionLevel()
    {
        $form = new LevelListForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        $arr = $form->search();
        return $this->render('level',[
            'list'=>$arr['list'],
            'pagination'=>$arr['p'],
            'row_count'=>$arr['row_count']
        ]);
    }

    /**
     * 会员等级编辑
     */
    public function actionLevelEdit($id = null)
    {
        $level = Level::findOne(['id'=>$id,'is_delete'=>0,'store_id'=>$this->store->id]);
        if(!$level){
            $level = new Level();
        }
        $store = Store::findOne(['id'=>$this->store->id]);
        if(\Yii::$app->request->isAjax){
            $form = new LevelForm();
            $post = \Yii::$app->request->post();
            $form->scenario = $post['scene'];
            $form->store_id = $this->store->id;
            $form->model = $level;
            $form->attributes = $post;
            if($post['scene'] == 'edit'){
                $this->renderJson($form->save());
            }else if($post['scene'] == 'content'){
                $this->renderJson($form->saveContent());
            }
        }
        return $this->render('level-edit',[
            'level'=>$level,
            'store'=>$store
        ]);
    }
    /**
     * 会员等级启用/禁用
     */
    public function actionLevelType($type = 0,$id=null)
    {
        $level = Level::find()->where(['id'=>$id,'store_id'=>$this->store->id])->one();
        if(!$level){
            $this->renderJson([
                'code'=>1,
                'msg'=>'会员等级不存在'
            ]);
        }
        $level->status = $type;
        if($type == 0){
            $exit = User::find()->where(['store_id'=>$this->store->id,'level'=>$level->level])->exists();
            if($exit){
                $this->renderJson([
                    'code'=>1,
                    'msg'=>'该会员等级下有会员，不可禁用'
                ]);
            }
        }
        if($level->save()){
            $this->renderJson([
                'code'=>0,
                'msg'=>'成功'
            ]);
        }else{
            $this->renderJson([
                'code'=>1,
                'msg'=>'网络异常'
            ]);
        }
    }
    /**
     * 会员等级删除
     */
    public function actionLevelDel($id=null)
    {
        $level = Level::findOne(['id'=>$id,'store_id'=>$this->store->id]);
        if(!$level){
            $this->renderJson([
                'code'=>1,
                'msg'=>'会员等级不存在'
            ]);
        }
        $exit = User::find()->where(['store_id'=>$this->store->id,'level'=>$level->level])->exists();
        if($exit){
            $this->renderJson([
                'code'=>1,
                'msg'=>'该会员等级下有会员，不可删除'
            ]);
        }
        $level->is_delete = 1;
        if($level->save()){
            $level->delete();
            $this->renderJson([
                'code'=>0,
                'msg'=>'成功'
            ]);
        }else{
            $this->renderJson([
                'code'=>1,
                'msg'=>'网络异常'
            ]);
        }
    }
    /**
     * 会员编辑
     */
    public function actionEdit($id=null)
    {
        $user = User::findOne(['id'=>$id,'store_id'=>$this->store->id]);
        if(!$user){
            $this->redirect(\Yii::$app->urlManager->createUrl(['mch/user/index']))->send();
        }
        if(\Yii::$app->request->isAjax){
            $form = new UserForm();
            $form->store_id = $this->store->id;
            $form->user = $user;
            $form->attributes = \Yii::$app->request->post();
            $this->renderJson($form->save($id));
        }
        $level = Level::findAll(['store_id'=>$this->store->id,'status'=>1,'is_delete'=>0]);
        //2018.6.2添加上级信息
        if(!empty($user->parent_id)){
            $parent = User::findOne(['id'=>$user->parent_id,'store_id'=>$this->store->id]);
        }else{
            $parent=array();
        }
        return $this->render('edit',[
            'user'=>$user,
            'level'=>$level,
            'parent'=>$parent
        ]);
    }
    //修改上级星级
    public function editparent($id){
        if($id > 0){
            $parent = User::findOne(['id'=>$id]);
            if($parent->level > 0){
                $fan1 = User::find()->where("`parent_id` =". $parent->id." and `level` > 0")->asArray()->all();
                $fan1num = count($fan1);
                $fan2num = 0;
                foreach ($fan1 as $v){
                    $fan2 = User::find()->where(['parent_id'=>$v['id'],'is_delete'=>0])->asArray()->all();
                    $fan2num += count($fan2);
                }
                $fannum = $fan1num + $fan2num;
                if($fan1num >= 15 && $fannum >= 60 ){
                    $parent->level = 2;
                    $parent->save();
                }else{
                    $parent->level = 1;
                    $parent->save();
                }
            }
            if($parent->parent_id > 0){
                $parent = User::findOne(['id'=>$parent->parent_id]);
                if($parent->level > 0){
                    $fan1 = User::find()->where("`parent_id` =". $parent->id." and `level` > 0")->asArray()->all();
                    $fan1num = count($fan1);
                    $fan2num = 0;
                    foreach ($fan1 as $v){
                        $fan2 = User::find()->where(['parent_id'=>$v['id'],'store_id'=>$this->store_id,'is_delete'=>0])->asArray()->all();
                        $fan2num += count($fan2);
                    }
                    $fannum = $fan1num + $fan2num;
                    if($fan1num >= 15 && $fannum >= 60 ){
                        $parent->level = 2;
                        $parent->save();
                    }else{
                        $parent->level = 1;
                        $parent->save();
                    }
                }

            }
        }

    }

    /**
     * 修改上级
     */
    public function actionSetparent($id=null)
    {
        $user = User::findOne(['id'=>$id,'store_id'=>$this->store->id]);
        if(!$user){
            $this->redirect(\Yii::$app->urlManager->createUrl(['mch/user/index']))->send();
        }
        if(\Yii::$app->request->isAjax){
            
            $attributes = \Yii::$app->request->post();
            $nlog = new \app\models\NexusLog();
            $nlog->store_id = $this->store->id;
            $nlog->content = 'ID:'.$user->id.'原上级:'.$user->parent_id.'改成:'.$attributes['parent_id'].'选项'.serialize($attributes['uupdata']);
            $nlog->time = ''.time();
            $nlog->save();
            if(!empty($attributes['uupdata'])){
                $uupdata = $attributes['uupdata'];
            }else{
                $uupdata = [0];
            }
            $partner = User::findOne(['id'=>$user->parent_id,'store_id'=>$this->store->id]);
            if(!empty($attributes['parent_id'])){
                //第一步，修改上级
                if(empty($user->parent_id) or in_array(1,$uupdata)){
                    $oldparent = $user->parent_id;
                    $user->parent_id = $attributes['parent_id'];
                    if($user->save()){
                        $msg="修改上级成功;";
                        //新上级星级修改
                        $res = $this->editparent($oldparent);
                        $res1 = $this->editparent($user->parent_id);


                    }else{
                        $this->renderJson([
                            'code'=>1,
                            'msg'=>'修改上级失败'
                        ]);
                    }
                     
                    //修改上级成功之后开始计算298
                    if(in_array(2,$uupdata)){
                        $share_detailed = \app\models\ShareDetailed::findOne(['from_id'=>$user->id]);
                        if(!$share_detailed){
                            $share_detailed = new \app\models\ShareDetailed();
                        }
                        if(!empty($partner) && $partner['is_distributor']==1){
                            if($partner['level']==2){
                                $myprice = 80;
                            }else{
                                $myprice = 50;
                            }
                            $share_detailed->store_id = $this->store->id;
                            $share_detailed->user_id = $user->parent_id;
                            $share_detailed->from_id = $user->id;
                            $share_detailed->price = $myprice;
                            $share_detailed->remarks = '推荐收益';
                            $share_detailed->add_time = time();
                            if($share_detailed->save()){
                                $msg.='修改上级收益成功;';
                            }else{
                                $this->renderJson([
                                    'code'=>1,
                                    'msg'=>$msg.'修改上级收益失败'
                                ]);
                            }
                        }
                    }
                    //修改上级成功之后,充值过298 的用户升级和计算佣金
                    if(in_array(3,$uupdata)){
                        //查找我的充值记录
                        //$reorder = \app\models\ReOrder::find()->where(['user_id'=>$user->id,'is_pay'=>1,'store_id'=>$this->store->id])->andWhere(' pay_price>298 ');
                        $reorder = \Yii::$app->db->createCommand("SELECT * FROM hjmallind_re_order  WHERE user_id=".$user->id." AND is_pay=1 AND store_id=".$this->store->id." AND pay_price>=298")->queryAll();

                        if(!$reorder){
                            $reorder = \app\models\ReOrder::findOne(['user_id'=>$user->id,'pay_price'=>300,'is_pay'=>1,'store_id'=>$this->store->id]);
                        }
                        if(!empty($reorder)){
                            //修改用户为代理
                            $user->is_distributor = 1;
                            $user->level = 1;
                            if($user->save()){
                                //添加用户推荐人记录表
                                $myshare = Share::findOne(['user_id' => $user->id, 'store_id' => $this->store->id]);
                                if(empty($myshare)){
                                   $myshare = new Share();
                                }
                                
                                $myshare->user_id=$user->id;
                                $myshare->mobile=$user->mobile;
                                $myshare->name=!empty($user->nickname)?$user->nickname:$user->username;
                                $myshare->status=$user->is_distributor;
                                $myshare->addtime = time();
                                $myshare->store_id=1;
                                $myshare->save();
                                $msg.= '设置用户为代理成功;';
                            }
                            
                            $share_detailed = \app\models\ShareDetailed::findOne(['from_id'=>$user->id]);
                            if(!$share_detailed){
                                $share_detailed = new \app\models\ShareDetailed();
                            }
                            if(!empty($partner) && $partner['is_distributor']==1){
                                if($partner['level']==2){
                                    $myprice = 80;
                                }else{
                                    $myprice = 50;
                                }
                                $share_detailed->store_id = $this->store->id;
                                $share_detailed->user_id = $user->parent_id;
                                $share_detailed->from_id = $user->id;
                                $share_detailed->price = $myprice;
                                $share_detailed->remarks = '推荐收益';
                                $share_detailed->add_time = time();
                                if($share_detailed->save()){
                                    $msg.='修改上级收益成功;';
                                }else{
                                    $this->renderJson([
                                        'code'=>1,
                                        'msg'=>$msg.'修改上级收益失败'
                                    ]);
                                }
                                 //这里加入一个逻辑
                                 foreach ($reorder as $v){
                                     if($v->is_parent==0){
                                        //加一个判断该用户在充值表中有没有大于0的is_parent的记录如果有了就不执行了。
                                        $isreorder = \app\models\ReOrder::find()->where(['user_id'=>$v['user_id'],'is_pay'=>1,'store_id'=>$this->store->id])->andWhere(['>=','is_parent',1])->one();
                                        if($isreorder){
                                           
                                        }else{
                                            if(1){
                                                            //给上级加钱
                                                           if($partner){
                                                               $partner->price = $myprice+$partner->price;
                                                               $partner->total_price = $myprice+$partner->total_price;
                                                               $partner->save();
                                                           }
                                            }
                                            //加完钱之后要走这里
                                            $myreorder = \app\models\ReOrder::findOne(['id' => $v['id'], 'store_id' => $this->store->id]);
                                            $myreorder->is_parent =2;
                                            $myreorder->save();
                                            $msg.='推荐奖发送成功';
                                        }
                                    }
                                 }
                            }
                        } else {
                            $msg.='无充值298记录,不要瞎点;';
                        }
                        
                    }
                    if(in_array(4,$uupdata)){
                        $msg .='修改订单收益暂时没写';
                    }
                    $this->renderJson([
                                        'code'=>1,
                                        'msg'=>$msg
                                    ]);
                }else{
                    if(!in_array(1,$uupdata)){
                        $this->renderJson([
                            'code'=>1,
                            'msg'=>'修改上级失败,已有上级需选择替换上级'
                        ]);
                    }else{
                         $this->renderJson([
                            'code'=>1,
                            'msg'=>'修改上级失败,上级id不能为空'
                        ]);
                    }
                    
                }
            }else{
                //充值过298 的用户升级和计算佣金
                        if(in_array(3,$uupdata)){
                            //查找我的充值记录
                                //$reorder = \app\models\ReOrder::find()->where(['user_id'=>$user->id,'is_pay'=>1,'store_id'=>$this->store->id])->andWhere(' pay_price>298 ');
                                $reorder = \Yii::$app->db->createCommand("SELECT * FROM hjmallind_re_order  WHERE user_id=".$user->id." AND is_pay=1 AND store_id=".$this->store->id." AND pay_price>=298")->queryAll();
//                            if(!$reorder){
//                                $reorder = \app\models\ReOrder::findOne(['user_id'=>$user->id,'pay_price'=>300,'is_pay'=>1,'store_id'=>$this->store->id]);
//                            }
                            if(!empty($reorder)){
                                //修改用户为代理
                                $user->is_distributor = 1;
                                $user->level = 1;
                                if($user->save()){
                                    //添加用户推荐人记录表
                                    $myshare = \app\models\Share::findOne(['user_id' => $user->id, 'store_id' => $this->store->id]);
                                    if(!$myshare){
                                       $myshare = new \app\models\Share();
                                    }
                                    $myshare->user_id=$user->id;
                                    $myshare->mobile=$user->mobile;
                                    $myshare->name=!empty($user->nickname)?$user->nickname:$user->username;
                                    $myshare->status=$user->is_distributor;
                                    $myshare->addtime = time();
                                    $myshare->store_id=1;
                                    $myshare->save();
                                    $msg.= '设置用户为代理成功;';
                                }

                                $share_detailed = \app\models\ShareDetailed::findOne(['from_id'=>$user->id]);
                                if(!$share_detailed){
                                    $share_detailed = new \app\models\ShareDetailed();
                                }
                                if(!empty($partner) && $partner['is_distributor']==1){
                                    if($partner['level']==2){
                                        $myprice = 80;
                                    }else{
                                        $myprice = 50;
                                    }
                                    $share_detailed->store_id = $this->store->id;
                                    $share_detailed->user_id = $user->parent_id;
                                    $share_detailed->from_id = $user->id;
                                    $share_detailed->price = $myprice;
                                    $share_detailed->remarks = '推荐收益';
                                    $share_detailed->add_time = time();
                                    if($share_detailed->save()){
                                        $msg.='修改上级收益成功;';
                                    }else{
                                        $this->renderJson([
                                            'code'=>1,
                                            'msg'=>$msg.'修改上级收益失败'
                                        ]);
                                    }
                                     //这里加入一个逻辑
                                    foreach ($reorder as $v){
                                        if($v->is_parent==0){
                                           //加一个判断该用户在充值表中有没有大于0的is_parent的记录如果有了就不执行了。
                                           $isreorder = \app\models\ReOrder::find()->where(['user_id'=>$v['user_id'],'is_pay'=>1,'store_id'=>$this->store->id])->andWhere(['>=','is_parent',1])->one();
                                           if($isreorder){

                                           }else{
                                               if(1){
                                                               //给上级加钱
                                                              if($partner){
                                                                  $partner->price = $myprice+$partner->price;
                                                                  $partner->total_price = $myprice+$partner->total_price;
                                                                  $partner->save();
                                                              }
                                               }
                                               //加完钱之后要走这里
                                               $myreorder = \app\models\ReOrder::findOne(['id' => $v['id'], 'store_id' => $this->store->id]);
                                               $myreorder->is_parent =2;
                                               $myreorder->save();
                                               $msg.='推荐奖发送成功';
                                           }
                                       }
                                    }
                                }
                            } else {
                                $msg.='无充值298记录,不要瞎点;';
                            }
                            $this->renderJson([
                                        'code'=>1,
                                        'msg'=>$msg
                                ]);
                            
                 }else{
                      $this->renderJson([
                                        'code'=>0,
                                        'msg'=>"上级不能为空"
                                ]);
                 }
            }
            $this->renderJson($attributes);
        }
      
        
        return $this->render('setparent',[
            'user'=>$user,
        ]);
    }

    /**
     * @return mixed|string
     * 后台用户积分充值
     */
    public function actionRechange()
    {
        $integral = (int)\Yii::$app->request->post('integral');
        $user_id = \Yii::$app->request->post('user_id');
        $rechangeType = \Yii::$app->request->post('rechangeType');
        $user = User::findOne(['id'=>$user_id,'store_id'=>$this->store->id]);
        if (!$user){
            return json_encode([
                'code'  => 1,
                'msg'   => '用户不存在，或已删除'
            ],JSON_UNESCAPED_UNICODE);
        }
        if (empty($integral)){
            return json_encode([
                'code'  => 1,
                'msg'   => '积分设置不正确'
            ],JSON_UNESCAPED_UNICODE);
        }
        if ($this->is_we7){
            $admin = \Yii::$app->user->identity;
        }else{
            $admin = \Yii::$app->admin->identity;
        }
        if ($rechangeType == '2'){
            if ($integral > $user->integral){
                return json_encode([
                    'code'  => 1,
                    'msg'   => '用户当前积分不足'
                ],JSON_UNESCAPED_UNICODE);
            }
            $user->integral -= $integral;
        }elseif ($rechangeType == '1'){
            $user->integral += $integral;
            $user->total_integral += $integral;
        }
        if (!$user->save()){
            return json_encode([
                'code' => 1,
                'msg'  => '充值失败！请重试'
            ],JSON_UNESCAPED_UNICODE);
        }

        $integralLog = new IntegralLog();
        $integralLog->user_id = $user->id;
        if ($rechangeType == '2'){
            $integralLog->content = "管理员： ".$admin->username." 后台操作账号：".$user->nickname." 积分扣除：".$integral." 积分";
        }elseif ($rechangeType == '1'){
            $integralLog->content = "管理员： ".$admin->username." 后台操作账号：".$user->nickname." 积分充值：".$integral." 积分";
        }

        $integralLog->integral = $integral;
        $integralLog->addtime = time();
        $integralLog->username = $user->nickname;
        $integralLog->operator = $admin->username;
        $integralLog->store_id = $this->store->id;
        $integralLog->operator_id = $admin->id;
        if ($integralLog->save()){
            return json_encode([
                'code'  => 0,
                'msg'   => '充值成功'
            ],JSON_UNESCAPED_UNICODE);
        }else{
            return json_encode([
                'code'  => 1,
                'msg'   => '充值失败'
            ],JSON_UNESCAPED_UNICODE);
        }
    }

    public function actionRechangeLog($user_id = 0)
    {
        $query = IntegralLog::find()
            ->andWhere(['store_id'=>$this->store->id,'user_id'=>$user_id]);
        $count = $query->count();
        $p = new Pagination(['totalCount' => $count, 'pageSize' => 20]);
        $list = $query->orderBy('addtime DESC')
            ->limit($p->limit)
            ->offset($p->offset)
            ->all();

        return $this->render('rechange-log', [
            'list' => $list,
            'pagination' => $p,
        ]);
    }
    
    /**
     * 会员卡券
     */
    public function actionCard()
    {
        $form = new UserCardListForm();
        $form->store_id = $this->store->id;
        $form->attributes = \Yii::$app->request->get();
        $user_id = \Yii::$app->request->get('user_id');
        $user = User::findOne(['store_id'=>$this->store->id,'id'=>$user_id]);
        $clerk_id = \Yii::$app->request->get('clerk_id');
        $clerk = User::findOne(['store_id'=>$this->store->id,'id'=>$clerk_id]);
        $shop_id = \Yii::$app->request->get('shop_id');
        $shop = Shop::findOne(['store_id'=>$this->store->id,'id'=>$shop_id]);
        $arr = $form->search();
        $data = $form->getCount();
        return $this->render('card',[
            'list'=>$arr['list'],
            'pagination'=>$arr['pagination'],
            'row_count'=>$arr['row_count'],
            'data'=>$data,
            'user'=>$user,
            'clerk'=>$clerk,
            'shop'=>$shop,
        ]);
    }

    /**
     * 会员充值记录
     */
    public function actionRecharge($user_id = null)
    {
        $form = new UserRechargeForm();
        $form->store_id = $this->store->id;
        $form->user_id = $user_id;
        $form->attributes = \Yii::$app->request->get();
        $arr = $form->search();
        return $this->render('recharge',[
            'list'=>$arr['list'],
            'pagination'=>$arr['pagination'],
            'row_count'=>$arr['row_count']
        ]);
    }

    /**
     * 会员购买记录
     */
    public function actionBuy($keyword = null)
    {
        $query = LevelOrder::find()->alias('ro')->where(['ro.store_id' => $this->store->id, 'ro.is_delete' => 0, 'ro.is_pay' => 1])
            ->leftJoin(['u' => User::tableName()], 'u.id=ro.user_id')
            ->leftJoin(['current' => Level::tableName()], 'current.level=ro.current_level')
            ->leftJoin(['after' => Level::tableName()], 'after.level=ro.after_level');

        if ($keyword) {
            $query->andWhere(['like', 'u.nickname', $keyword]);
        }
        $count = $query->count();
        $p = new Pagination(['totalCount' => $count, 'pageSize' => 20]);
        $list = $query->select(['u.nickname', 'ro.*','after.name as after_name','current.name as current_name'])
            ->limit($p->limit)->offset($p->offset)->orderBy(['ro.addtime' => SORT_DESC])->asArray()->all();

        return $this->render('buy',[
            'list' => $list,
            'pagination' => $p,
            'row_count' => $count,
        ]);
    }
}