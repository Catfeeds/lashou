<?php

namespace app\models;

use Yii;
use Codeception\PHPUnit\ResultPrinter\HTML;

/**
 * This is the model class for table "{{%share}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property integer $from_id
 * @property double  $price
 * @property integer $status
 * @property integer $type
 * @property string  $remarks
 * @property integer $is_del
 * @property integer $add_time

 */
class ShareDetailed extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%share_detailed}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'from_id', 'store_id','price','remarks'], 'required'],
            [['user_id','from_id', 'status','type', 'add_time', 'store_id'], 'integer'],
            //[['remarks'], 'string', 'max' => 255],
           [['remarks'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'from_id' => 'from_id',
            'price' => 'Price',
            'status' => '0推荐收益 1订单收益',
            'type' => '0 = +;1 = -;',
            'is_del' => 'Is Delete',
            'add_time' => 'Addtime',
            'store_id' => '商城id',
            'remarks' => '备注',
        ];
    }
    public function saveS()
    {
        $this->add_time = time();
        if($this->save()){
            return [
                'code'=>0,
                'msg'=>'成功'
            ];
        }else{
            return [
                'code'=>1,
                'msg'=>'网络异常',
                'data'=>$this->errors,
            ];
        }
    }
    public function getFirstChildren()
    {
        return $this->hasMany(User::className(),['parent_id'=>'user_id'])
            ->alias('f')->andWhere(['f.is_delete'=>0])->select(['f.id','f.nickname','f.parent_id','f.addtime']);
    }
    public static function getChildren($index)
    {
        $share = Share::findOne(['user_id'=>$index]);
        if(!$share)
            return null;
        $children = $share->firstChildren;
        if(!$children)
            return null;
        return $children;
    }
    public function getC()
    {
        return $this->hasMany(User::className(),['parent_id'=>'user_id']);
    }

    public function beforeSave($insert)
    {
        $this->remarks = \yii\helpers\Html::encode($this->remarks);
        return parent::beforeSave($insert);
    }
    public  function selectDetailedAll(){
        $data = $this->find()->where(['user_id'=>$this->user_id,'is_del'=>0])->orderBy('id DESC')->asArray()->all();
        if($data){
            foreach ($data as $key => $value) {
                //获取from_id 用户信息
                $from_user = User::findOne($data[$key]['from_id']);
                if(empty($from_user))
                    $from_user['nickname'] = 0;

                $data[$key]['from_name'] = $from_user['nickname'];
                unset($data[$key]['from_id']);
                $data[$key]['add_time'] = date('Y-m-d H:i',$data[$key]['add_time']);
            }
            return [
                'code'=>0,
                'msg'=>'成功',
                'data'=>$data,

            ];
        }else{
            return [
                'code'=>1,
                'msg'=>'网络异常',
                'data'=>$this->errors,
            ];
        }

    }
}
