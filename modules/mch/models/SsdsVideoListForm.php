<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/14
 * Time: 14:21
 */

namespace app\modules\mch\models;


use app\models\LsPlayerWeightLog;
use yii\data\Pagination;
use yii\web\User;

class SsdsVideoListForm extends Model
{
    public $status;
    public $page;
    public $page_size;
    public $keyword;
    public $date_start;
    public $date_end;
    public function rules()
    {
        return [
            [['keyword',], 'trim'],
            [['status'], 'integer'],
            [['status',], 'default', 'value' => -1],
            [['page',], 'default', 'value' => 1],
            [['page_size',], 'default', 'value' => 10],
            [[ 'date_start', 'date_end'], 'trim'],
        ];
    }


    public function getList(){
        if(!$this->validate()){
            return $this->getModelError();
        }

        $query = LsPlayerWeightLog::find()->from(LsPlayerWeightLog::tableName() . ' as l')
        ->leftJoin('hjmallind_user as u', 'u.id = l.user_id');
        if ($this->keyword){
            $query->andWhere(['LIKE', 'u.nickname', $this->keyword]);
        }

        if ($this->date_start) {
            $query->andWhere(['>=', 'l.add_date_time', ($this->date_start)]);
        }
        if ($this->date_end) {
            $query->andWhere(['<=', 'l.add_date_time', date("Y-m-d",strtotime($this->date_end) + 86400)]);
        }
        if($this->status >= 0){
            $query->where(['l.status' => $this->status]);
        }

        $count = $query->count();
        $pagination = new Pagination([
            'totalCount' => $count,
            'pageSize' => $this->page_size,
            'page' => $this->page - 1,
        ]);

        $list = $query
            ->limit($this->page_size)
            ->offset($pagination->offset)
            ->orderBy('l.id desc')
            ->select('l.*, u.nickname')
            ->asArray()
            ->all();

        return [
            'row_count' => $count,
            'page_count' => $pagination->pageCount,
            'pagination' => $pagination,
            'list' => $list,
            'self' => $this
        ];
    }
}