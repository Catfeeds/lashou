<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8
 * Time: 16:13
 */

namespace app\modules\mch\models;


use app\models\MsOrder;
use app\models\Order;
use app\models\PtOrder;
use app\models\Share;
use app\models\User;
use app\models\YyOrder;
use yii\data\Pagination;
use yii\helpers\VarDumper;

class ShareListForm extends Model
{
    public $store_id;
    public $user_id;
    public $page;
    public $limit;
    public $status;
    public $keyword;
    public $seller_comments;
    public $sortColumn;
    public $sortType;
    public function rules()
    {
        return [
            [['keyword', 'seller_comments'], 'trim'],
            [['page', 'limit', 'status'], 'integer'],
            [['status',], 'default', 'value' => -1],
            [['page'], 'default', 'value' => 1],
            [['seller_comments'], 'string'],
        ];
    }

    public function getsearch(){
        if ($this->validate()) {
            $query = User::find()->alias('u')
                ->where(['u.is_delete' => 0,'u.parent_id'=>\Yii::$app->request->get()['user_id']]);
            if(\Yii::$app->request->get()['keyword']){
                $query->andWhere([
                    'or',
                    ['like', 'u.nickname', \Yii::$app->request->get()['keyword']],
                    ['=','u.id',\Yii::$app->request->get()['keyword']]
                ]);
            }
            $count = $query->count();
            $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);
            $list = $query->limit($pagination->limit)->offset($pagination->offset);
            $list = $list->asArray()->all();
             return [$list, $pagination];
        }else{
            return $this->getModelError();
        }
    }

    public function getList()
    {
        if ($this->validate()) {
            //清楚错误数据
            $error_user = User::find()->alias('u')->where(['u.store_id' => $this->store_id, 'u.is_delete' => 0, 'u.is_distributor' => 2])
                ->leftJoin(Share::tableName() . ' s', 's.user_id=u.id and s.is_delete=0')->andWhere('s.id is null')->select('u.id')->asArray()->column();
            User::updateAll(['is_distributor' => 0], ['in', 'id', $error_user]);

            $query = Share::find()->alias('s')
                ->where(['s.is_delete' => 0, 's.store_id' => $this->store_id])
                ->leftJoin('{{%user}} u', 'u.id=s.user_id')
                ->andWhere(['u.is_delete' => 0])
                ->andWhere(['in', 's.status', [0, 1]]);
            if ($this->keyword) {
                $query->andWhere([
                    'or',
                    ['like', 's.name', $this->keyword],
                    ['like', 'u.nickname', $this->keyword],
                    ['=','s.user_id',$this->keyword]
                ]);
            }
            if ($this->status == 0 && $this->status != '') {
                $query->andWhere(['s.status' => 0]);
            }
            if ($this->status == 1) {
                $query->andWhere(['s.status' => 1]);
            }
            $count = $query->count();
//            if(\Yii::$app->request->get()['sortColumn']){
//                $flag = array();
//
//                foreach($arr[0] as $v){
//                    $flag[] = $v['first'];
//                }
//                array_multisort($flag, SORT_DESC, $arr[0]);
//
//            }
            if(\Yii::$app->request->get()['sortColumn'] && \Yii::$app->request->get()['sortColumn'] !='first'){
                if(\Yii::$app->request->get()['sortType'] == 'desc')
                    $type = SORT_DESC;
                else
                    $type = SORT_ASC;
                $orderby = [\Yii::$app->request->get()['sortColumn']=> $type];
            }else{
                $orderby = ['total_price' => SORT_DESC];

            }

            $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);
            if($orderby)
            $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy('s.status ASC,s.addtime DESC')
                ->select([
                    's.*', 'u.nickname', 'u.avatar_url', 'u.time', 'u.price', 'u.total_price', 'u.level','u.id user_id','u.is_partner u_partner'
                ])->orderBy($orderby)->asArray()->all();
            else{
                $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy('s.status DESC,s.addtime DESC')
                    ->select([
                        's.*', 'u.nickname', 'u.avatar_url', 'u.time', 'u.price', 'u.total_price', 'u.level','u.id user_id','u.is_partner u_partner'
                    ])->asArray()->all();
            }
            foreach ($list as $index => $value) {
                $parent1Member = User::find()->where('`level` >= 1 AND `parent_id` = '.$value['user_id'])->asArray()->all();
        $fens1 = count($parent1Member);
       // $parent = User::find()->where(' `parent_id` = 503444')->asArray()->all();
        foreach ($parent1Member as $k => $v){
            $parentMember = User::find()->where(['parent_id'=>$v['id']])->asArray()->all();
            $fens2 += count($parentMember);

        }
        $list[$index]['fans1'] = ($fens1);
        $list[$index]['fans'] = ($fens2+$fens1);
                $list[$index]['order_count'] = Order::find()->where([
                    'store_id' => $this->store_id, 'is_delete' => 0, 'is_cancel' => 0, 'is_recycle' => 0, 'user_id' => $value['user_id']
                ])->count();
                $list[$index]['ms_order_count'] = MsOrder::find()->where([
                    'store_id' => $this->store_id, 'is_delete' => 0, 'is_cancel' => 0, 'user_id' => $value['user_id']
                ])->count();
                $list[$index]['pt_order_count'] = PtOrder::find()->where([
                    'store_id' => $this->store_id, 'is_delete' => 0, 'user_id' => $value['user_id']
                ])->count();
//                $list[$index]['yy_order_count'] = YyOrder::find()->where([
//                    'store_id' => $this->store_id, 'is_delete' => 0, 'user_id' => $value['user_id']
//                ])->count();


                $list[$index]['first'] = 0;
                $list[$index]['second'] = 0;
                $list[$index]['third'] = 0;
                $f_children = Share::getChildren($value['user_id']);
                if (!$f_children)
                    continue;
                $list[$index]['first'] += count($f_children);
//                foreach ($f_children as $A => $a) {
//                    $s_children = Share::getChildren($a['id']);
//                    if (!$s_children)
//                        continue;
//                    $list[$index]['second'] += count($s_children);
//                    foreach ($s_children as $b) {
//                        $t_children = Share::getChildren($b['id']);
//                        if (!$t_children)
//                            continue;
//                        $list[$index]['third'] += count($t_children);
//                    }
//                }
            }
            if(\Yii::$app->request->get()['sortColumn'] == 'first'){
                $flag = array();

                foreach($list as $v){
                    $flag[] = $v['first'];
                }
                if(\Yii::$app->request->get()['sortType'] == 'desc')
                    array_multisort($flag, SORT_DESC, $list);
                else
                    array_multisort($flag,SORT_ASC,$list);

            }
            return [$list, $pagination];

        } else {
            return $this->getModelError();
        }
    }

    //无效
    public function getList1()
    {
        $query = Share::find()->alias('s')
            ->where(['s.is_delete' => 0, 's.store_id' => $this->store_id])
            ->leftJoin(User::tableName() . ' u', 'u.id=s.user_id')
            ->joinWith('firstChildren')->groupBy('s.id');
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);
        $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy('s.addtime DESC')
            ->select(['s.*', 'u.nickname', 'u.avatar_url', 'u.time', 'u.price', 'u.total_price',])->asArray()->all();
        $new_list = $list;
        foreach ($list as $index => $value) {
            $list[$index]['first'] = count($value['firstChildren']);
            foreach ($value['firstChildren'] as $i => $v) {
                $list[$index]['firstChildren'][$i]['time'] = date('Y-m-d', $v['addtime']);
                foreach ($new_list as $j => $item) {
                    if ($v['id'] == $item['user_id']) {
                        $list[$index]['second'] = $new_list[$j]['firstChildren'];
                    }
                }
            }
        }
        $new_list = $list;
        foreach ($list as $index => $value) {
            if (isset($value['secondChildren']) && is_array($value['secondChildren'])) {
                foreach ($value['secondChildren'] as $i => $v) {
                    $list[$index]['secondChildren'][$i]['time'] = date('Y-m-d', $v['addtime']);
                    foreach ($new_list as $j => $item) {
                        if ($v['id'] == $item['user_id']) {
                            $list[$index]['thirdChildren'] = $new_list[$j]['firstChildren'];
                        }
                    }
                }
            }
        }
        return $list;
    }

    public function getTeam($user_id = 0)
    {
        //获取有一级下线的分销商
        if($user_id < 1){
            return false;
        }
        $query = Share::find()->alias('s')
            ->where(['s.is_delete' => 0, 's.store_id' => $this->store_id,'s.user_id'=>$user_id])
            ->leftJoin(User::tableName() . ' u', 'u.id=s.user_id')
            ->joinWith('firstChildren')->groupBy('s.id');
        $count = $query->count();
//        $sql = $query->createCommand()->getRawSql();
//        var_dump($sql);die;
//        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);
        $list = $query->select(['s.*', 'u.nickname'])->asArray()->all();

        $new_list = $list;
        //获取二级下线
        foreach ($list as $index => $value) {
            $res = [];
            foreach ($value['firstChildren'] as $i => $v) {
                $list[$index]['firstChildren'][$i]['time'] = date('Y-m-d', $v['addtime']);

                foreach ($new_list as $j => $item) {
                    if ($v['id'] == $item['user_id']) {
//                            $list[$index]['secondChildren'] = $new_list[$j]['firstChildren'];
                        $res = array_merge($res, $new_list[$j]['firstChildren']);
                    }
                }
            }
            $list[$index]['secondChildren'] = $res;
        }
        $new_list = $list;
        foreach ($list as $index => $value) {
            $res = [];
            if (isset($value['secondChildren']) && is_array($value['secondChildren'])) {
                foreach ($value['secondChildren'] as $i => $v) {
                    $list[$index]['secondChildren'][$i]['time'] = date('Y-m-d', $v['addtime']);
                    foreach ($new_list as $j => $item) {
                        if ($v['id'] == $item['user_id']) {
//                            $list[$index]['thirdChildren'] = $new_list[$j]['firstChildren'];
                            $res = array_merge($res, $new_list[$j]['firstChildren']);
                        }
                    }
                }
            }
            $list[$index]['thirdChildren'] = $res;
        }
        return $list;
    }
    public function getTeam1($user_id = 0)
    {
        //获取有一级下线的分销商
        if($user_id < 1){
            return false;
        }
        $query = Share::find()->alias('s')
            ->where(['s.is_delete' => 0, 's.store_id' => $this->store_id,'s.user_id'=>$user_id])
            ->leftJoin(User::tableName() . ' u', 'u.id=s.user_id')
            ->joinWith('firstChildren')->groupBy('s.id');
        $count = $query->count();
//        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => $this->limit, 'page' => $this->page - 1]);
        $list = $query->select(['s.*', 'u.nickname'])->asArray()->all();

        $new_list = $list;
        //获取二级下线
        foreach ($list as $index => $value) {
            $res = [];
            foreach ($value['firstChildren'] as $i => $v) {
                $list[$index]['firstChildren'][$i]['time'] = date('Y-m-d', $v['addtime']);
                foreach ($new_list as $j => $item) {
                    if ($v['id'] == $item['user_id']) {
//                            $list[$index]['secondChildren'] = $new_list[$j]['firstChildren'];
                        $res = array_merge($res, $new_list[$j]['firstChildren']);
                    }
                }
            }
            $list[$index]['secondChildren'] = $res;
        }
        $new_list = $list;
        foreach ($list as $index => $value) {
            $res = [];
            if (isset($value['secondChildren']) && is_array($value['secondChildren'])) {
                foreach ($value['secondChildren'] as $i => $v) {
                    $list[$index]['secondChildren'][$i]['time'] = date('Y-m-d', $v['addtime']);
                    foreach ($new_list as $j => $item) {
                        if ($v['id'] == $item['user_id']) {
//                            $list[$index]['thirdChildren'] = $new_list[$j]['firstChildren'];
                            $res = array_merge($res, $new_list[$j]['firstChildren']);
                        }
                    }
                }
            }
            $list[$index]['thirdChildren'] = $res;
        }
        return $list;
    }


    public function getCount()
    {
        $list = Share::find()
            ->select([
                'sum(case when s.status = 0 then 1 else 0 end) count_1',
                'sum(case when s.status = 1 then 1 else 0 end) count_2',
                'sum(case when s.status != 2 then 1 else 0 end) total'
            ])->alias('s') ->leftJoin(User::tableName() . ' u', 'u.id=s.user_id')
            ->where(['s.is_delete' => 0, 's.store_id' => $this->store_id]);
             if ($this->keyword) {
                 $list->andWhere([
                     'or',
                     ['like', 's.name', $this->keyword],
                     ['like', 'u.nickname', $this->keyword],
                     ['=','s.user_id',$this->keyword]
                 ]);
             }
            $list = $list->asArray()->one();
        return $list;
    }
}