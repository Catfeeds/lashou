<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/3
 * Time: 13:54
 */

namespace app\modules\mch\models;
use app\models\Share;
use app\models\ShareDetailed;
use app\models\User;

/**
 * @property \app\models\User $user
 */
class UserForm extends Model
{
    public $store_id;
    public $user;
    public $level;
    public $contact_way;
    public $mobile;
    public $comments;
    public $is_partner;


    public function rules()
    {
        return [
            [['level','is_partner'],'integer'],
            [['contact_way','comments'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            ''=> PARTNER_LABEL,
            'level'=>'会员等级',
            'contact_way'=>'联系方式',
            'comments'=>'备注'
        ];
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

        //修改上级星级


    }
    public function save($id=0)
    {
        if(!$this->validate()){
            return $this->getModelError();
        }
        $this->user->is_partner = $this->is_partner;
        $user = User::findOne(['id'=>$this->user->id,'store_id'=>$this->store_id]);

        if($this->level>0){
            if($user->level > 0 ){
                $this->user->level = $this->level;
            }else{
                $share = Share::findOne(['user_id'=>$id,'store_id'=>$this->store_id,'is_delete'=>0,'status'=>1]);
                if(!$share){
                    $share_user = new Share();
                    $share_user->user_id = $id;
                    $share_user->mobile = $user->mobile;
                    $share_user->name = $user->nickname;
                    $share_user->addtime = time();
                    $share_user->status = 1;
                    $share_user->store_id = $this->store_id;
                    $share_user->save();
                }


                $this->user->level = $this->level;
                $this->user->is_distributor = 1;
            }

        }else{
            $this->user->level = $this->level;
            $share = Share::findOne(['user_id'=>$id,'store_id'=>$this->store_id,'is_delete'=>0]);
            if($share){
                $share->delete();
            }
            $this->user->is_distributor = 0;
        }
        $this->user->contact_way = trim($this->contact_way);
        $this->user->mobile = trim($this->contact_way);
        $this->user->comments = trim($this->comments);

        if($this->user->save()){
            //上级升级
           $this->editparent($this->user->parent_id);
            return [
                'code'=>0,
                'msg'=>"OK",
            ];
        }else{
            return $this->getModelError($this->user);
        }
    }
}