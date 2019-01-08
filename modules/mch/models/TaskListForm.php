<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/8/3
 * Time: 13:52
 */

namespace app\modules\mch\models;


use app\models\Day;
use app\models\Level;
use app\models\LsPlayerCash;
use app\models\Order;
use app\models\Shop;
use app\models\Stage;
use app\models\Store;
use app\models\Task;
use app\models\User;
use app\models\UserCard;
use yii\data\Pagination;

class TaskListForm extends Model
{
    public $store_id;
    public $page;
    public $keyword;

    public $search;

    public function rules()
    {
        return [
            [['keyword','search','level'], 'trim'],
            [['page', 'is_clerk'], 'integer'],
            [['page'], 'default', 'value' => 1],
        ];
    }

    public function search()
    {
        $query = LsPlayerCash::find()->alias('t')->where([
            't.store_id' => $this->store_id,
        ])->leftJoin(User::tableName() . 'u', 'u.id=t.user_id');

        if ($this->keyword){
            if($this->search ==1){
                $query->andWhere(['or',['LIKE','u.nickname',$this->keyword],['LIKE','u.id',$this->keyword],['LIKE','u.mobile',$this->keyword]]);
            }elseif ($this->search == 2){
                $query->andWhere(['LIKE', 'u.id', $this->keyword]);
            }elseif ($this->search == 3){
                $query->andWhere(['LIKE', 'u.nickname', $this->keyword]);
            }elseif ($this->search ==4){
                $query->andWhere(['LIKE', 'u.mobile', $this->keyword]);
            }

        }

        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1]);
        $list = $query->select([
            'u.*', 't.*'
        ])->limit($pagination->limit)->offset($pagination->offset)->orderBy('t.add_time DESC')->asArray()->all();
//         foreach ($list as $index => $val) {
//          $list[$index]['day'] = Day::find()->where(['sid'=>$val['sid'],'user_id'=>$val['user_id']])
//              ->orderBy('day asc')->asArray()->one();
//         }
        return [
            'row_count' => $count,
            'page_count' => $pagination->pageCount,
            'pagination' => $pagination,
            'list' => $list,
        ];
    }

    public function getUser()
    {
        $query = User::find()->where([
            'type' => 1,
            'store_id' => $this->store_id,
            'is_clerk' => 0,
            'is_delete' => 0
        ]);
        if ($this->keyword)
            $query->andWhere(['LIKE', 'nickname', $this->keyword]);
        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'page' => $this->page - 1]);
        $list = $query->limit($pagination->limit)->offset($pagination->offset)->orderBy('addtime DESC')->asArray()->all();
//        $list = $query->orderBy('addtime DESC')->asArray()->all();

        return $list;
    }
}