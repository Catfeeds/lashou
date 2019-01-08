<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/24
 * Time: 16:37
 */;
defined('YII_RUN') or exit('Access Denied');
use yii\widgets\LinkPager;
$urlManager = Yii::$app->urlManager;
$this->title = '订单消息';
$order_type = [
    '商城订单',
    '秒杀订单',
    '拼团订单',
    '预约订单'
];
?>


<div class="panel">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <table class="table table-bordered">
            <tr>
                <th>订单号</th>
                <th>订单所属</th>
                <th>订单类型</th>
                <th>下单用户</th>
                <th>下单时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($list as $index => $value): ?>
                <tr>
                    <td><?= $value['order_no'] ?></td>
                    <td>
                        <?php if ($value['type'] == 0): ?>
                            <span>下单</span>
                        <?php else: ?>
                            <span>售后订单</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $order_type[$value['order_type']] ?></td>
                    <td><?= $value['name'] ?></td>
                    <td><?= date('Y-m-d H:i:s', $value['addtime']) ?></td>
                    <td>
                        <?php if ($value['type'] == 0): ?>
                            <?php if ($value['order_type'] == 0): ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= $urlManager->createUrl(['mch/order/index', 'keyword_1' => 1, 'keyword' => $value['order_no']]) ?>"
                                   target="_blank">查看</a>
                            <?php elseif ($value['order_type'] == 1): ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= $urlManager->createUrl(['mch/miaosha/order/index', 'keyword_1' => 1, 'keyword' => $value['order_no']]) ?>"
                                   target="_blank">查看</a>
                            <?php elseif ($value['order_type'] == 2): ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= $urlManager->createUrl(['mch/group/order/index', 'keyword_1' => 1, 'keyword' => $value['order_no']]) ?>"
                                   target="_blank">查看</a>
                            <?php else: ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= $urlManager->createUrl(['mch/book/order/index', 'keyword_1' => 1, 'keyword' => $value['order_no']]) ?>"
                                   target="_blank">查看</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($value['order_type'] == 0): ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= $urlManager->createUrl(['mch/order/refund', 'keyword_1' => 1, 'keyword' => $value['order_no']]) ?>"
                                   target="_blank">查看</a>
                            <?php elseif ($value['order_type'] == 1): ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= $urlManager->createUrl(['mch/miaosha/order/refund', 'keyword_1' => 1, 'keyword' => $value['order_no']]) ?>"
                                   target="_blank">查看</a>
                            <?php elseif ($value['order_type'] == 2): ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= $urlManager->createUrl(['mch/group/order/refund', 'keyword_1' => 1, 'keyword' => $value['order_no']]) ?>"
                                   target="_blank">查看</a>
                            <?php else: ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= $urlManager->createUrl(['mch/book/order/index', 'keyword_1' => 1, 'keyword' => $value['order_no']]) ?>"
                                   target="_blank">查看</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="text-center">
            <?= LinkPager::widget(['pagination' => $pagination,]) ?>
            <div class="text-muted"><?= $pagination->totalCount ?>条数据</div>
        </div>
    </div>
</div>