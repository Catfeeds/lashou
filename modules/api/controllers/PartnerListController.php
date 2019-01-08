<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/11
 * Time: 14:44
 */

namespace app\modules\api\controllers;


use app\models\LsPartnerList;
use app\models\User;

class PartnerListController extends Controller
{
    public function actionPrepare(){
        set_time_limit(0);

        $password = $_REQUEST['password'];
        if(empty($password) || $password !== 'lashou123321'){
            $this->renderJson([
                'code' => 901,
                'msg' => 'wrong password'
            ]);
        }

        $ids = empty($_REQUEST['ids']) ? null : explode(',', $_REQUEST['ids']);

        $transaction = \Yii::$app->db->beginTransaction();
        try{
            //1.清除缓存表
            \Yii::$app->db->createCommand("TRUNCATE " . LsPartnerList::tableName())->execute();

            //2.逐个操作
            $partner_query = User::find()->where(['is_partner' => 1])->orderBy('id asc');
            if($ids){
                $partner_query->andWhere(['in', 'id', $ids]);
            }

            foreach ($partner_query->each() as $partner){
                $partner_list = new LsPartnerList();
                $partner_list->user_id = $partner->id;
                $partner_list->total = -1;
                $partner_list->last_update = date('Y-m-d H:i:s');
                $partner_list->save();
            }

            $transaction->commit();

            $this->renderJson([
                'data' => 'success',
            ]);
        }catch (\Exception $e){
            $transaction->rollBack();
            $this->renderJson([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function actionBuildCache(){
        set_time_limit(0);

        $password = $_REQUEST['password'];
        if(empty($password) || $password !== 'lashou123321'){
            $this->renderJson([
                'code' => 901,
                'msg' => 'wrong password'
            ]);
        }

        $page = empty($_REQUEST['page']) ? 1 : intval($_REQUEST['page']);
        $page_size = 1000;

        $transaction = \Yii::$app->db->beginTransaction();
        try{
            /*$partner_query = LsPartnerList::find()
                ->orderBy('user_id asc')
                ->offset(($page - 1) * $page_size)
                ->limit($page_size);*/

            $partner_query = LsPartnerList::find()
                ->where(['total' => -1])
                ->orderBy('user_id asc')
                ->limit(1);

            foreach ($partner_query->each() as $partner){
                $this->computeTeam($partner->user_id);
            }

            $transaction->commit();
            $this->renderJson([
                'data' => 'success',
            ]);
        }catch (\Exception $e){
            $transaction->rollBack();
            $this->renderJson([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
            ]);
        }

    }

    /**
     *
     * return
     *
     *
     */
    private $team_total = 0;
    private $team_active_num = 0;
    private $current_partner_id = 0;
    private function computeTeam($partner_id){
        echo "computer team for user_id " . $partner_id . '<br>';
        $this->current_partner_id = $partner_id;
        $this->team_total = 0;
        $this->team_active_num = 0;

        $partner_info = User::findOne($partner_id);
        if(empty($partner_info)){
            return;
        }

        $children_query = User::find()->where(['parent_id' => $partner_id])->select('id, is_distributor, wechat_open_id');
        foreach($children_query->each() as $child_user){
            $this->excuteChild($child_user);
        }

        $this->cacheTeam();
    }
    private function cacheTeam(){
        $partner_list = LsPartnerList::findOne(['user_id' => $this->current_partner_id]);
        $partner_list->user_id = $this->current_partner_id;
        $partner_list->total = $this->team_total;
        $partner_list->active_num = $this->team_active_num;
        $partner_list->active_rate = $this->team_total == 0 ? 0 : ($this->team_active_num / $this->team_total);
        $partner_list->last_update = date('Y-m-d H:i:s');
        $partner_list->save();
    }

    private $status_partner_list = null;
    private function getStatusPartner(){
        if($this->status_partner_list != null){
            return $this->status_partner_list;
        }

        $this->status_partner_list = LsPartnerList::find()
            ->select('user_id')
            ->column();
        return $this->status_partner_list;
    }

    /**
     * @param $user_info User
     */
    private function excuteChild($user_info){
        //1.如果是合伙人 退出
        /*if($user_info->is_partner == 1){
            return;
        }*/
        if(in_array($user_info->id, $this->getStatusPartner())){
            echo "stop: user_id " . $user_info->id . " in list " . implode(',', $this->getStatusPartner()) . '<br>';
            return;
        }else{
            //echo "continue: user_id " . $user_info->id . " not in list " . implode(',', $this->getStatusPartner()) . '<br>';
        }

        //2.团队和激活人数累加
        if($user_info->wechat_open_id != ''){
            $this->team_total += 1;

            if($user_info->is_distributor == 1){
                $this->team_active_num += 1;
            }
        }

        /*if($user_info->wechat_open_id != ''){
            $this->team_active_num += 1;
        }*/

        //3.递归子用户
        $children_query = User::find()->where(['parent_id' => $user_info->id])->select('id, is_distributor, wechat_open_id');
        foreach ($children_query->each() as $user){
            $this->excuteChild($user);
        }
    }

    public function actionGetList(){
        $sort = empty($_REQUEST['sort']) ? "total" : trim($_REQUEST['sort']);//total active_rate
        if(!in_array($sort, ['total', 'active_rate'])){
            $sort = "total";
        }

        $page = empty($_REQUEST['page']) ? 1 : intval($_REQUEST['page']);
        $page_size = 20;

        $count = LsPartnerList::find()->count();
        $total_page = ceil($count / $page_size);

        $data_list = LsPartnerList::find()
            ->orderBy($sort . ' DESC')
            ->offset(($page - 1) * $page_size)
            ->limit($page_size)
            ->all();

        return $this->renderJson([
            'data_list' => $data_list,
            'count' => $count,
            'total_page' => $total_page,
        ]);
    }

    public function actionTest(){
        phpinfo();
    }
}