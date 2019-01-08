<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/9
 * Time: 16:04
 */

namespace app\modules\api\models;
use app\models\Cash;
use app\models\FormId;
use app\models\Order;
use app\models\Partner;
use app\models\Setting;
use app\models\Share;
use app\models\User;
use app\modules\mch\models\ShareSettingForm;

/**
 * @property \app\models\Partner $partner
 */
class PartnerForm extends Model
{
    public $partner;
    public $name;
    public $store_id;
    public $user_id;
    public $phone;
    public $anmeila_uid;
    public $anmeila_pwd;
   // public $status;


    /**
     * @return array
     * 场景说明：NONE_CONDITION--无条件
     *           APPLY--需要申请
     */
    public function rules()
    {
        return [
            [['anmeila_uid','anmeila_pwd'],'required','on'=>'APPLY'],
            [['anmeila_uid','anmeila_pwd'],'trim'],
        ];
    }
    public function attributeLabels()
    {
        return [
            //'name'=>'name',
            'anmeila_uid'=>'代理帐号',
            'anmeila_pwd'=>'代理密码',

            //'phone'=>'手机号'
        ];
    }
    public function save()
    {
        if($this->validate()){
            $t = \Yii::$app->db->beginTransaction();

            $this->partner->attributes = $this->attributes;
            if($this->partner->isNewRecord){
                $this->partner->is_del = 0;
                $this->partner->add_time = time();
                $this->partner->store_id = $this->store_id;
            }
            $this->partner->user_id = \Yii::$app->user->identity->id;
            $user = User::findOne(['id'=>\Yii::$app->user->identity->id,'store_id'=>$this->store_id]);
            //$this->partner->status = 0;
           // $user->is_partner = 2;

            $dataAnMeiLa = $this->request_checkAnMeiLaAccounts(['uid'=>$this->anmeila_uid,'pwd'=>$this->anmeila_pwd]);

            if($dataAnMeiLa['result'] == 1){
                $anmeilaAccounts = Partner::find()->where('anmeila_uid = \''.$this->anmeila_uid.'\'')->asArray()->one();
                if(empty($anmeilaAccounts)){
                    $this->partner->name = $dataAnMeiLa['uname'];
                    $this->partner->mobile = $dataAnMeiLa['uphone'];
                    $this->partner->anmeila_uid = $this->anmeila_uid;
                    $this->partner->anmeila_pwd = $this->anmeila_pwd;
                    $this->partner->status = 1;
                    $user->is_partner = 1;
                }else{
                    $t->rollBack();
                    return [
                        'code'=>1,
                        'msg'=>'该代理已存在'
                    ];

                }

            }else{
                $t->rollBack();
                return [
                    'code'=>1,
                    'msg'=>'帐号或密码有误'
                ];

            }




           // $number =  substr_replace(\Yii::$app->user->identity->binding,'****',3,4);

        /*    if($number != $this->phone || empty($this->phone)){
                $t->rollBack();
                return [
                    'code'=>2,
                    'msg'=>'check phone error'
                ];
            }*/

            if(!$user->save()){
                $t->rollBack();
                return [
                    'code'=>1,
                    'msg'=>'网络异常'
                ];
            }
            if($this->partner->save()){
                $t->commit();
                $user = User::findOne(\Yii::$app->user->identity->id);
                return [
                    'code'=>0,
                    'is_partner'=> $user->is_partner,
                    'msg'=>'成功'
                ];
            }else{
                $t->rollBack();
                return [
                    'code'=>1,
                    'msg'=>'网络异常',
                    'data'=>$this->errors,
                ];
            }
        }else{
            return $this->getModelError();
        }
    }

    /*
     * 检测帐号密码
     *
     */

    public function checkAnMeiLaAccounts(){


        $user = User::findOne(\Yii::$app->user->identity->id);
        $partner = Partner::find()->where('user_id = '.$user->id)->one();

        $dataAnMeiLa = $this->request_checkAnMeiLaAccounts(['uid'=>$partner->anmeila_uid,'pwd'=>$partner->anmeila_pwd]);


        if(!empty($partner)){
            if($dataAnMeiLa['result'] == 1){
                $user->is_partner = 1;
                $user->save();
                $partner->status = 1;
                $partner->save();
                return [
                    'code'=>0,
                    'is_partner' => \Yii::$app->user->identity->is_partner,
                    'msg'=>'success'
                ];
            }else{
                $user->is_partner = 0;
                $user->save();
                $partner->status = 3;
                $partner->save();
                return [
                    'code'=>2,
                    'msg'=>'帐号或密码有误'
                ];

            }
        }else
            return [
                'code'=>3,
                'msg'=>'信息不存在'
            ];

    }

    /**
     * @return array
     * 获取佣金相关信息
     */
    public function getPrice()
    {
        $user = User::find()->where(['id'=>$this->user_id])->one();
        $list = Cash::find()->where([
            'store_id'=>$this->store_id,'user_id'=>$this->user_id,'is_delete'=>0
        ])->asArray()->all();
        $new_list = [];
        $new_list['total_price'] = $user->total_price;//分销佣金
        $new_list['price'] = $user->price;;//可提现
        $new_list['cash_price'] = 0;//已提现
        $new_list['un_pay'] = 0;//未审核
        $new_list['total_cash'] = 0;//提现明细
        foreach ($list as $index => $value) {
            if ($value['status'] == 1) {
                $new_list['un_pay'] = round(($new_list['un_pay'] + $value['price']), 2);
                $new_list['total_cash'] = round(($new_list['total_cash'] + $value['price']), 2);
            } elseif ($value['status'] == 2 || $value['status'] == 5) {
                $new_list['cash_price'] = round(($new_list['cash_price'] + $value['price']), 2);
                $new_list['total_cash'] = round(($new_list['total_cash'] + $value['price']), 2);
            }
        }
        return $new_list;
    }

    /**
     * @return array|null|\yii\db\ActiveRecord
     *
     */
    public function getCash()
    {
        $list = User::find()->alias('u')
            ->where(['u.is_delete'=>0,'u.store_id'=>$this->store_id,'u.id'=>$this->user_id])
            ->leftJoin('{{%cash}} c','c.user_id=u.id and c.is_delete=0')
            ->select([
                'u.total_price','u.price',
                'sum(case when c.status = 2 then c.price else 0 end) cash_price',
                'sum(case when c.status = 1 then c.price else 0 end) un_pay'
            ])->groupBy('c.user_id')->asArray()->one();
        return $list;
    }

    //获取分销团队总人数
    public function getTeamCount()
    {
        $share_setting = Setting::findOne(['store_id'=>$this->store_id]);
        if(!$share_setting || $share_setting->level == 0){
            return [
                'team_count'=>0,
                'team'=>[]
            ];
        }
        $team = [];
        $first = User::find()->select(['id'])
            ->where(['store_id'=>$this->store_id,'parent_id'=>$this->user_id,'is_delete'=>0])->column();
        $count = count($first);
        $team['f_c'] = $first;
        if($share_setting->level >= 2){
            $second = User::find()->select(['id'])
                ->where(['store_id'=>$this->store_id,'parent_id'=>$first,'is_delete'=>0])->column();
            $count += count($second);
            $team['s_c'] = $second;
            if($share_setting->level >= 3){
                $third = User::find()->select(['id'])
                    ->where(['store_id'=>$this->store_id,'parent_id'=>$second,'is_delete'=>0])->column();
                $count += count($third);
                $team['t_c'] = $third;
            }
        }
        return [
            'team_count'=>$count,
            'team'=>$team
        ];
    }

    public function getOrder()
    {
        $arr = $this->getTeamCount();
        $team_arr = $arr['team'];

        $order_money = 0;
        $first_price = Order::find()->alias('o')->where([
            'o.is_delete' => 0, 'o.is_cancel' => 0, 'o.store_id' => $this->store_id
        ])->andWhere([
            'o.parent_id' => $this->user_id,
        ])->select(['sum(first_price)'])->scalar();
        if ($first_price) {
            $order_money += doubleval($first_price);
        }
        if(!empty($team_arr['s_c'])){
            $second_price = Order::find()->alias('o')->where([
                'o.is_delete' => 0, 'o.is_cancel' => 0, 'o.store_id' => $this->store_id
            ])->andWhere([
                'or',
                ['and',['in', 'o.user_id', $team_arr['s_c']],['o.parent_id'=>$team_arr['f_c'],'o.parent_id_1'=>0]],
                ['o.parent_id_1' => $this->user_id],
            ])->select(['sum(second_price)'])->scalar();
            if ($second_price) {
                $order_money += doubleval($second_price);
            }
        }
        if(!empty($team_arr['t_c'])){
            $third_price = Order::find()->alias('o')->where([
                'o.is_delete' => 0, 'o.is_cancel' => 0, 'o.store_id' => $this->store_id
            ])->andWhere([
                'or',
                ['and',['in', 'o.user_id', $team_arr['t_c']],['o.parent_id'=>$team_arr['s_c'],'o.parent_id_1'=>0]],
                ['o.parent_id_2' => $this->user_id],
            ])->select(['sum(third_price)'])->scalar();
            if ($third_price) {
                $order_money += doubleval($third_price);
            }
        }
        $arr['order_money'] = doubleval(sprintf('%.2f', $order_money));

        return $arr;
    }




    private function request_checkAnMeiLaAccounts($param) {
        if (empty($param)) {
            return false;
        }
        $postUrl = 'http://anmeila.oioos.com/lasou/checkuserispartner';
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return json_decode($data,true);
    }

}