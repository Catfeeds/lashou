<?php
defined('YII_RUN') or exit('Access Denied');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/11
 * Time: 10:33
 */
/* @var $pagination yii\data\Pagination */
use yii\widgets\LinkPager;
use \app\models\Cash;

$urlManager = Yii::$app->urlManager;
$this->title = '提现列表';
$this->params['active_nav_group'] = 5;
$status = Yii::$app->request->get('status');
if ($status === '' || $status === null || $status == -1)
    $status = -1;
?>
<style>
    .dropdown-item1 {
        display: block;
        width: 100%;
        padding: 3px 1.5rem;
        clear: both;
        font-weight: 400;
        color: #292b2c;
        text-align: inherit;
        white-space: nowrap;
        background: 0 0;
        border: 0;
    }
</style>
<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <div class="mb-3 clearfix">
            <div class="p-4 bg-shaixuan">
                <form method="get">

                    <?php $_s = ['keyword'] ?>
                    <?php foreach ($_GET as $_gi => $_gv):if (in_array($_gi, $_s)) continue; ?>
                        <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                    <?php endforeach; ?>
                    <div flex="dir:left">
                        <div>
                            <div class="input-group">
                                <input class="form-control"
                                       placeholder="姓名/微信昵称"
                                       name="keyword"
                                       autocomplete="off"
                                       value="<?= isset($_GET['keyword']) ? trim($_GET['keyword']) : null ?>">
                                <input class="form-control" style="display: none"
                                       placeholder="" id="type"
                                       name="type"
                                       autocomplete="off"
                                       value="<?= isset($_GET['type']) ? trim($_GET['type']) : null ?>">
                                <div class="dropdown float-right ml-2">
                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdown-toggle"
                                            style="margin-right: 10px"     data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <?php if($_GET['type']==='0'): ?>微信打款
                                        <?php elseif($_GET['type']==='3'): ?>余额打款
                                        <?php elseif($_GET['type']==''): ?>打款方式
                                        <?php else: ?>
                                        <?php endif; ?>
                                    </button>
                                    <div class="dropdown-menu" style="min-width:8rem">
                                        <a class="dropdown-item1"  href="javascript:"
                                           data-url="<?= $urlManager->createUrl(['mch/share/cash']) ?>">打款方式</a>
                                        <a class="dropdown-item1" href="javascript:" data-url="<?= $urlManager->createUrl(['mch/share/cash','type' => 0]) ?>" value="0">微信打款</a>
                                        <a class="dropdown-item1" href="javascript:" data-url="<?= $urlManager->createUrl(['mch/share/cash','type' => 3]) ?>" value="3">余额打款</a>

                                    </div>
                                </div>
                                <span class="input-group-btn">
                                <button class="btn btn-primary">搜索</button>
                                </span>
                            </div>
                        </div>
                        <div class="dropdown float-right ml-2">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                批量设置
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton"
                                 style="max-height: 200px;overflow-y: auto">
                                <a href="javascript:void(0)" class="btn btn-secondary batch dropdown-item"
                                   data-url="<?= $urlManager->createUrl(['mch/partner/batchcash']) ?>" data-content="是否批量审核"
                                   data-type="0">批量审核</a>
                                <a href="javascript:void(0)" class="btn btn-warning batch dropdown-item"
                                   data-url="<?= $urlManager->createUrl(['mch/partner/batchcash']) ?>" data-content="是否批量打款"
                                   data-type="1">批量打款</a>
                                <a href="javascript:void(0)" class="btn btn-danger batch dropdown-item"
                                   data-url="<?= $urlManager->createUrl(['mch/partner/batchcash']) ?>" data-content="是否批量驳回"
                                   data-type="2">批量驳回</a>

                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
        <div class="mb-4">
            <ul class="nav nav-tabs status">
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == -1 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/partner/cash']) ?>">全部</a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 0 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/partner/cash', 'status' => 0]) ?>">未审核<?= $count['count_1'] ? "(" . $count['count_1'] . ")" : null ?></a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 1 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/partner/cash', 'status' => 1]) ?>">待打款<?= $count['count_2'] ? "(" . $count['count_2'] . ")" : null ?></a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 2 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/partner/cash', 'status' => 2]) ?>">已打款<?= $count['count_3'] ? "(" . $count['count_3'] . ")" : null ?></a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 3 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/partner/cash', 'status' => 3]) ?>">无效<?= $count['count_4'] ? "(" . $count['count_4'] . ")" : null ?></a>
                </li>
            </ul>
        </div>
        <table class="table table-bordered bg-white">
            <tr>
<!--                <td width="50px">ID</td>-->
                <th style="text-align: left;">
                    <label class="checkbox-label">
                        <input type="checkbox" class="goods-all">
                        <span class="label-icon"></span>
                        <span class="label-text">ID</span>
                    </label>
                </th>
                <td width="200px">微信信息</td>
                <td>账号信息</td>
                <td>提现金额（元）</td>
                <td>手续费（%）</td>
                <td>状态</td>
                <td>申请时间</td>
                <td>到账时间</td>
                <td>查看明细</td>
                <td>操作</td>
            </tr>
            <?php foreach ($list as $index => $value): ?>
                <tr>
                    <td class="nowrap" style="text-align: left;" data-toggle="tooltip"
                        data-placement="top" title="<?=$value['user_id']?>">
                        <label class="checkbox-label">
                            <input data-num="<?= 1 ?>" type="checkbox"
                                   class="goods-one"
                                   value="<?= $value['id'] ?>">
                            <span class="label-icon"></span>
                            <span class="label-text"><?= $value['user_id'] ?></span>
                        </label>
                    </td>
<!--                    <td>--><?//= $value['user_id'] ?><!--</td>-->
                    <td data-toggle="tooltip" data-placement="top" title="<?= $value['nickname'] ?>">
                        <span
                            style="width: 150px;display:block;white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><img
                                src="<?= $value['avatar_url'] ?>"
                                style="width: 30px;height: 30px;margin-right: 10px;"><?= $value['nickname'] ?></span>
                    </td>
                    <td>
                        <div>
                            <?php if ($value['type'] == 0): ?>
                                <div>姓名：<?= $value['name'] ?></div>
                                <span>微信号：<?= $value['mobile'] ?></span>
                            <?php elseif ($value['type'] == 1): ?>
                                <div>姓名：<?= $value['name'] ?></div>
                                <span>支付宝账号：<?= $value['mobile'] ?></span>
                            <?php elseif ($value['type'] == 2): ?>
                                <div>姓名：<?= $value['name'] ?></div>
                                <div>开户行：<?= $value['bank_name'] ?></div>
                                <span>银行卡号：<?= $value['mobile'] ?></span>
                            <?php elseif ($value['type'] == 3): ?>
                                <span>余额提现</span>
                            <?php endif; ?>
                            <?php if ($value['is_partner'] == 1) :?>
                                <div style="color: red">(<?= PARTNER_LABEL?>提现)</div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= $value['price'] ?><?php if($value['hongbao']==1):?>(红包提现)<?php endif; ?></td>
                    <td><?= $value['poundage'] ?>%</td>
                    <td>
                        <?php if ($value['pay_type'] != 1): ?>
                            <?= Cash::$status[$value['status']] ?><?= ($value['status'] == 2) ? "(" . Cash::$type[$value['type']] . ")" : "" ?>
                            <?php if ($value['status'] == 5): ?>
                                <span>已打款</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= Cash::$status[$value['status']] ?><?= ($value['status'] == 2) ? "(微信自动打款)" : "" ?>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d H:i', $value['addtime']); ?></td>
                    <?php if ($value['pay_time'] > 0): ?><td><div><?= date('Y-m-d H:i', $value['pay_time']); ?></div> <span><?php if ($value['type'] == 0): ?><?= $value['price']*(1-$value['poundage']) ?>元 <?php elseif ($value['type'] == 3): ?><?= $value['price'] ?>元<?php endif; ?></span></td> <?php else: ?><td>未到账</td> <?php endif; ?>
                    <td><a class="btn btn-sm btn-link"
                           href="<?= $urlManager->createUrl(['mch/share/order', 'parent_id' => $value['user_id']]) ?>">分销商明细</a>
                          <a class="btn btn-sm btn-link" href="<?= $urlManager->createUrl(['mch/commentdet/userdet', 'id' => $value['user_id']]) ?>">综合明细</a>
                          <a class="btn btn-sm btn-link" href="<?= $urlManager->createUrl(['mch/commentdet/sharedet', 'id' => $value['user_id']]) ?>">推荐明细</a></td>
                    
                    <td>
                        <?php if ($value['status'] == 0): ?>
                            <a class="btn btn-sm btn-primary del" href="javascript:"
                               data-url="<?= $urlManager->createUrl(['mch/partner/apply', 'status' => 1, 'id' => $value['id']]) ?>"
                               data-content="是否通过申请？">通过</a>
                            <a class="btn btn-sm btn-danger del" href="javascript:"
                               data-url="<?= $urlManager->createUrl(['mch/partner/apply', 'status' => 3, 'id' => $value['id']]) ?>"
                               data-content="是否驳回申请？">驳回</a>
                            <?php if($value['hongbao']==1):?>
                                <a class="btn btn-sm " href="<?= $urlManager->createUrl(['mch/share/log', 'id' => $value['id']]) ?>"
                                  >查看记录</a>
                            <?php endif; ?>
                        <?php elseif ($value['status'] == 1): ?>
                            <div>
                                <a class="btn btn-sm btn-danger del" href="javascript:"
                                   data-url="<?= $urlManager->createUrl(['mch/partner/apply', 'status' => 3, 'id' => $value['id']]) ?>"
                                   data-content="是否驳回申请？">驳回</a>
                            </div>
                            <?php if($value['type'] == 0): ?>
                            <div class="mt-2">
                                <a class="btn btn-sm btn-primary pay" href="javascript:"
                                   data-url="<?= $urlManager->createUrl(['mch/partner/confirm', 'status' => 2, 'id' => $value['id']]) ?>"
                                   data-content="是否确认打款？">确认打款</a>
                                <span>（微信支付自动打款）</span>
                            </div>
                            <?php elseif($value['type'] == 3): ?>
                            <div class="mt-2">
                                <a class="btn btn-sm btn-primary pay" href="javascript:"
                                   data-url="<?= $urlManager->createUrl(['mch/partner/confirm', 'status' => 4, 'id' => $value['id']]) ?>"
                                   data-content="是否确认打款？">余额打款</a>
                                <span>（余额打款）</span>
                            </div>
                                <?php endif;?>
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
<?= $this->render('/layouts/ss'); ?>
<script>
    $(document).on('click', '.dropdown-item1', function () {
        type = $.trim($(this).attr('value'));
        $("#type").val(type);
        var val = $.trim($(this).text());
        $("#dropdown-toggle").val(val);
        $("#dropdown-toggle").html(val);

    });
    $(document).on('click', '.goods-all', function () {
        var checked = $(this).prop('checked');
        $('.goods-one').prop('checked', checked);
        if (checked) {
            $('.batch').addClass('is_use');
        } else {
            $('.batch').removeClass('is_use');
        }
    });
    $(document).on('click', '.goods-one', function () {
        var checked = $(this).prop('checked');
        var all = $('.goods-one');
        var is_all = true;//只要有一个没选中，全选按钮就不选中
        var is_use = false;//只要有一个选中，批量按妞就可以使用
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                is_use = true;
            } else {
                is_all = false;
            }
        });
        if (is_all) {
            $('.goods-all').prop('checked', true);
        } else {
            $('.goods-all').prop('checked', false);
        }
        if (is_use) {
            $('.batch').addClass('is_use');
        } else {
            $('.batch').removeClass('is_use');
        }
    });
    $(document).on('click', '.batch', function () {
        var all = $('.goods-one');
        var is_all = true;//只要有一个没选中，全选按钮就不选中
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                is_all = false;
            }
        });
        if (is_all) {
            $.myAlert({
                content: "请先勾选用户"
            });
        }
    });
    $(document).on('click', '.is_use', function () {
        var a = $(this);
        var goods_group = [];
        var all = $('.goods-one');
        all.each(function (i) {
            if ($(all[i]).prop('checked')) {
                var goods = {};
                goods.id = $(all[i]).val();
                goods.num = $(all[i]).data('num');
                goods_group.push(goods);
            }
        });
        $.myConfirm({
            content: a.data('content'),
            confirm: function () {
                $.myLoading();
                $.ajax({
                    url: a.data('url'),
                    type: 'get',
                    dataType: 'json',
                    data: {
                        goods_group: goods_group,
                        type: a.data('type'),
                    },
                    success: function (res) {
                        if (res.code == 0) {
                            $.myAlert({
                                content:res.msg,
                                confirm:function(){
                                    window.location.reload();
                                }
                            });
                        } else {
                            $.myAlert({
                                content:res.msg
                            });
                        }
                    },
                    complete: function () {
                        $.myLoadingHide();
                    }
                });
            }
        })
    });
    $(document).on('click', '.del', function () {
        var a = $(this);
        if (confirm(a.data('content'))) {
            a.btnLoading();
            $.ajax({
                url: a.data('url'),
                type: 'get',
                dataType: 'json',
                success: function (res) {
                    if (res.code == 0) {
                        window.location.reload();
                    } else {
                        $.myAlert({
                            content: res.msg
                        });
                        a.btnReset();
                    }
                }
            });
        }
        return false;
    });
</script>
<script>
    $(document).on('click', '.pay', function () {
        var a = $(this);
        var btn = $('.pay');
        if (confirm(a.data('content'))) {
            btn.btnLoading();
            $.ajax({
                url: a.data('url'),
                type: 'get',
                dataType: 'json',
                success: function (res) {
                    if (res.code == 0) {
                        window.location.reload();
                    } else {
                        $.myAlert({
                            content: res.msg
                        });
                        btn.btnReset();
                    }
                }
            });
        }
        return false;
    });
</script>