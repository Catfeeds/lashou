<?php
defined('YII_RUN') or exit('Access Denied');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/11
 * Time: 10:33
 */


use \app\models\Cash;

$urlManager = Yii::$app->urlManager;
$this->title = '红包详情列表';
$this->params['active_nav_group'] = 5;
$status = Yii::$app->request->get('status');
if ($status === '' || $status === null || $status == -1)
    $status = -1;
?>
<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <div class="mb-3 clearfix">
            <div class="p-4 bg-shaixuan">

            </div>

        </div>

        <table class="table table-bordered bg-white">
            <tr>
                <td width="200px">用户信息</td>
                <td>金额（元）</td>
                <td>获得方式</td>
                <td>获得时间</td>
            </tr>
            <?php foreach ($list as $index => $value): ?>
                <tr>
                    <?php $user = \app\models\User::findOne(['id'=>$value['uid']]);?>
                    <td data-toggle="tooltip" data-placement="top" title="<?= $user->nickname ?>">
                        <span
                            style="width: 150px;display:block;white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><img
                                src="<?= $user->avatar_url ?>"
                                style="width: 30px;height: 30px;margin-right: 10px;"><?= $user->nickname ?></span>
                    </td>
                    <td><?= $value['val']/100 ?>元</td>
                    <td>
                       <?= $value['log']?>
                    </td>
                    <td><?= date('Y-m-d H:i', $value['add_time']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
