<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/11/9
 * Time: 11:54
 */

namespace app\modules\mch\controllers;




use app\models\CashWechatTplSender;
use app\models\LsCash;
use app\models\Task;
use app\models\User;
use app\modules\mch\models\CashLsForm;
use app\modules\mch\models\TaskListForm;
use yii\data\Pagination;

class TaskController extends Controller
{
    public $page;

    public function actionIndex()
    {
        $task = new TaskListForm();
        $task->attributes = \Yii::$app->request->get();
        $task->store_id = $this->store->id;
        $data = $task->search();
        //var_dump($data);die;
        return $this->render('index', [
            'row_count' => $data['row_count'],
            'pagination' => $data['pagination'],
            'list' => $data['list'],
        ]);

    }

    public function actionCheck($id = 0)
    {
        $user = Task::findOne(['id' => $id, 'store_id' => $this->store->id]);
        if (!$user) {
            return $this->renderJson([
                'code' => 1,
                'msg' => '用户不存在'
            ]);
        }
        if ($user->status == 0) {
            $user->status = 1;
            if ($user->save()) {
                $this->renderJson([
                    'code' => 0,
                    'msg' => '<span style="color: blue">已审核</span>'
                ]);
            } else {
                $this->renderJson([
                    'code' => 1,
                    'msg' => '网络异常'
                ]);
            }
        } else {
            $user->status = 0;
            if ($user->save()) {
                $this->renderJson([
                    'code' => 0,
                    'msg' => '<span style="color: red">未审核</span>'
                ]);
            } else {
                $this->renderJson([
                    'code' => 1,
                    'msg' => '网络异常'
                ]);
            }
        }
    }



    /**
     * @return string
     * 提现列表
     */
    public function actionCash()
    {
        $form = new CashLsForm();
        $form->attributes = \Yii::$app->request->get();
        $form->store_id = $this->store->id;
        $form->limit = 10;
        $arr = $form->getList();
        $count = $form->getCount();
        return $this->render('cash', [
            'list' => $arr[0],
            'pagination' => $arr[1],
            'count' => $count
        ]);
    }


    /**
     * @param int $id
     * @param int $status
     * @return mixed|string
     * 申请审核
     */
    public function actionApply($id = 0, $status = 0)
    {
        $cash = LsCash::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);
        $user_id = $cash->user_id;

        if (!$cash) {
            return json_encode([
                'code' => 1,
                'msg' => '提现记录不存在，请刷新重试'
            ], JSON_UNESCAPED_UNICODE);
        }
        $cash_cache = \Yii::$app->cache->get('ls_cash_cache_' . $id);
        if ($cash_cache && $cash_cache == $cash->order_no) {
            return $this->renderJson([
                'code' => 1,
                'msg' => '网络繁忙，请刷新重试'
            ]);
        }
        if (!$cash->order_no) {
            $order_no = null;
            while (true) {
                $order_no = date('YmdHis') . rand(100000, 999999);
                $exist_order_no = LsCash::find()->where(['order_no' => $order_no])->exists();
                if (!$exist_order_no)
                    break;
            }
            $cash->order_no = $order_no;
            $cash->save();
        }
        \Yii::$app->cache->set('ls_cash_cache_' . $id, $cash->order_no);
        if (!in_array($status, [1, 3])) {
            return json_encode([
                'code' => 1,
                'msg' => '提现记录已审核，请刷新重试'
            ], JSON_UNESCAPED_UNICODE);
        }
        $cash->status = $status;

        \Yii::$app->cache->set('ls_cash_cache_' . $id, false);
        if ($cash->save()) {
            if ($cash->status == 3) {
                $wechat_tpl_meg_sender = new CashWechatTplSender($this->store->id, $cash->id, $this->wechat);
                $wechat_tpl_meg_sender->cashFailMsg();
            }
            return json_encode([
                'code' => 0,
                'msg' => '成功'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode([
                'code' => 1,
                'msg' => '网络异常,请刷新重试'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    /**
     * @param int $id
     * @param int $status
     * @return mixed|string
     * 确认打款
     * 支付未做
     */
    public function actionConfirm($id = 0, $status = 0)
    {
        $cash = LsCash::findOne(['id' => $id, 'is_delete' => 0, 'store_id' => $this->store->id]);

        if (!$cash) {
            return json_encode([
                'code' => 1,
                'msg' => '提现记录不存在，请刷新重试'
            ], JSON_UNESCAPED_UNICODE);
        }
        $cash_cache = \Yii::$app->cache->get('ls_cash_cache_' . $id);
        if ($cash_cache && $cash_cache == $cash->order_no) {
            return $this->renderJson([
                'code' => 1,
                'msg' => '网络繁忙，请刷新重试'
            ]);
        }
        if (!$cash->order_no) {
            $order_no = null;
            while (true) {
                $order_no = date('YmdHis') . rand(100000, 999999);
                $exist_order_no = LsCash::find()->where(['order_no' => $order_no])->exists();
                if (!$exist_order_no)
                    break;
            }
            $cash->order_no = $order_no;
            $cash->save();
        }
        \Yii::$app->cache->set('ls_cash_cache_' . $id, $cash->order_no);
        if ($cash->status != 1) {
            return $this->renderJson([
                'code' => 1,
                'msg' => '操作错误，请刷新重试'
            ]);
        }
        if ($status == 2) {  //微信自动打款
            $cash->status = 2;
            $cash->pay_time = time();
            $cash->pay_type = 1;
            $user = User::findOne(['id' => $cash->user_id]);
            $amount = $cash->price * 100;
            $data = [
                'partner_trade_no' => $cash->order_no,
                'openid' => $user->wechat_open_id,
                'amount' => $amount,
                'desc' => '转账'
            ];
            $res = $this->wechat->pay->transfers($data);
        } else if ($status == 4) { //手动打款
            $cash->status = 2;
            $cash->pay_time = time();
            $cash->pay_type = 2;
            if ($cash->type == 3) {
                $user = User::findOne(['id' => $cash->user_id]);
                $user->money += doubleval($cash->price);
                if (!$user->save()) {
                    foreach ($user->errors as $error) {
                        return $this->renderJson([
                            'code' => 1,
                            'msg' => $error
                        ]);
                    }
                }
            }
//            $cash->type = 2;
            $res['result_code'] = "SUCCESS";
        }
        \Yii::$app->cache->set('ls_cash_cache_' . $id, false);
        if ($res['result_code'] == 'SUCCESS') {
            $cash->save();
            $wechat_tpl_meg_sender = new CashWechatTplSender($this->store->id, $cash->id, $this->wechat);
            $wechat_tpl_meg_sender->cashMsg();
            return json_encode([
                'code' => 0,
                'msg' => '成功'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode([
                'code' => 1,
                'msg' => $res['err_code_des'],
                'data' => $res
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}