<?php
defined('YII_RUN') or exit('Access Denied');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/29
 * Time: 9:50
 */

use yii\widgets\LinkPager;

/* @var \app\models\User $user */

$urlManager = Yii::$app->urlManager;
$statics = Yii::$app->request->baseUrl . '/statics';
$this->title = '统计订单';
$this->params['active_nav_group'] = 3;
$status = Yii::$app->request->get('status');
$is_recycle = Yii::$app->request->get('is_recycle');
$is_official = Yii::$app->request->get('is_official');
$is_official_bundle = Yii::$app->request->get('is_official_bundle');
$user_id = Yii::$app->request->get('user_id');
$condition = ['user_id' => $user_id, 'clerk_id' => $_GET['clerk_id'], 'shop_id' => $_GET['shop_id']];
if ($status === '' || $status === null || $status == -1)
    $status = -1;
if ($is_recycle == 1) {
    $status = 12;
}
if ($is_official == 1) {
    $status = 15;
}
if ($is_official_bundle == 1) {
    $status = 16;
}
?>
<style>
    .order-item {
        border: 1px solid transparent;
        margin-bottom: 1rem;
    }

    .order-item table {
        margin: 0;
    }

    .order-item:hover {
        border: 1px solid #3c8ee5;
    }

    .goods-item {
        margin-bottom: .75rem;
    }

    .goods-item:last-child {
        margin-bottom: 0;
    }

    .goods-pic {
        width: 5.5rem;
        height: 5.5rem;
        display: inline-block;
        background-color: #ddd;
        background-size: cover;
        background-position: center;
        margin-right: 1rem;
    }

    .goods-name {
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }



    .order-tab-2 {
        width: 20%;
        text-align: center;
    }

    .order-tab-3 {
        width: 10%;
        text-align: center;
    }

    .order-tab-4 {
        width: 20%;
        text-align: center;
    }

    .order-tab-5 {
        width: 10%;
        text-align: center;
    }

    .status-item.active {
        color: inherit;
    }
    .order-tab-1{
        width: 15%;
        text-align: center;
    }
    .order-tab-2{
        width: 15%;
        text-align: center;
    }
    .order-tab-3{
        width: 15%;
        text-align: center;
    }
    .order-tab-4{
        width: 15%;
        text-align: center;
    }
    .p-4{
        padding: .5rem .5rem!important;
    }
    .row{
        margin-left: 0rem !important;
    }
    .middle-center{
        margin-top: .8rem;
        padding: .2rem;
    }
    .new-day{
        margin-right: .5rem;
    }
</style>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">
    <title><?= $this->title ?></title>
    <link href="//at.alicdn.com/t/font_353057_xjg7zdpmf54mfgvi.css" rel="stylesheet">
    <link href="<?= Yii::$app->request->baseUrl ?>/statics/mch/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= Yii::$app->request->baseUrl ?>/statics/mch/css/jquery.datetimepicker.min.css" rel="stylesheet">
    <link href="<?= Yii::$app->request->baseUrl ?>/statics/css/flex.css?version=<?= $version ?>" rel="stylesheet">
    <link href="<?= Yii::$app->request->baseUrl ?>/statics/css/common.css?version=<?= $version ?>" rel="stylesheet">
    <link href="<?= Yii::$app->request->baseUrl ?>/statics/mch/css/common.v2.css?version=<?= $version ?>"
          rel="stylesheet">

    <script>var _csrf = "<?=Yii::$app->request->csrfToken?>";</script>
    <script>var _upload_url = "<?=Yii::$app->urlManager->createUrl(['upload/file'])?>";</script>
    <script>var _upload_file_list_url = "<?=Yii::$app->urlManager->createUrl(['mch/store/upload-file-list'])?>";</script>
    <script>var _district_data_url = "<?=Yii::$app->urlManager->createUrl(['api/default/district', 'store_id' => $this->context->store->id])?>";</script>

    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/jquery.min.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/vue.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/tether.min.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/bootstrap.min.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/plupload.full.min.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/jquery.datetimepicker.full.min.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/js/common.js?version=<?= $version ?>"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/common.v2.js?version=<?= $version ?>"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/clipboard.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/vendor/layer/layer.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/vendor/laydate/laydate.js"></script>
</head>
<script language="JavaScript" src="<?= $statics ?>/mch/js/LodopFuncs.js"></script>
<object id="LODOP_OB" classid="clsid:2105C259-1E0C-4534-8141-A753534CB4CA" width=0 height=0 style="display: none">
    <embed id="LODOP_EM" type="application/x-print-lodop" width=0 height=0></embed>
</object>

<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <div class="mb-3 clearfix">
            <div class="p-4 bg-shaixuan">
                <form method="get">
                    <?php $_s = ['keyword', 'keyword_1', 'date_start', 'date_end'] ?>
                    <?php foreach ($_GET as $_gi => $_gv):if (in_array($_gi, $_s)) continue; ?>
                        <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                    <?php endforeach; ?>
                    <div flex="dir:left">
                        <div class="mr-4">
                            <div class="form-group row w-20">
                                <div>
                                    <label>订单类型：</label>
                                </div>

                                    <button class="btn btn-secondary dropdown-toggle" type="button"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <?php if($_GET['status']==='1'): ?>拼团订单
                                        <?php elseif($_GET['status']==='2'): ?>秒杀订单
                                        <?php elseif($_GET['status']==''): ?>普通订单
                                        <?php else: ?>
                                        <?php endif; ?>
                                    </button>
                                    <div class="dropdown-menu" style="min-width:8rem"
                                    >
                                        <a class="dropdown-item" href="<?= $urlManager->createUrl(['api/tongji/tongji','store_id'=>1]) ?>">普通订单</a>
                                        <a class="dropdown-item" href="<?= $urlManager->createUrl(['api/tongji/tongji','status' => 1,'store_id'=>1]) ?>">拼团订单</a>
                                        <a class="dropdown-item" href="<?= $urlManager->createUrl(['api/tongji/tongji','status' => 2,'store_id'=>1]) ?>">秒杀订单</a>

                                    </div>

<!--                                <div class="col-7">-->
<!--                                    <input class="form-control"-->
<!--                                           name="keyword"-->
<!--                                           autocomplete="off"-->
<!--                                           value="--><?//= isset($_GET['keyword']) ? trim($_GET['keyword']) : null ?><!--">-->
<!--                                </div>-->
                            </div>
                        </div>
                      

                    </div>
                    <div class="mr-4">
                        <div class="form-group row">
                            <div>
                                <label>订单时间：</label>
                            </div>
                            <div>
                                <div class="input-group">
                                    <input class="form-control" id="date_start" name="date_start"
                                           autocomplete="off"
                                           value="<?= isset($_GET['date_start']) ? trim($_GET['date_start']) : '' ?>">
                                    <span class="input-group-btn">
                                            <a class="btn btn-secondary" id="show_date_start" href="javascript:">
                                                <span class="iconfont icon-daterange"></span>
                                            </a>
                                        </span>
                                    <span class="middle-center" style="padding:0 4px">至</span>
                                    <input class="form-control" id="date_end" name="date_end"
                                           autocomplete="off"
                                           value="<?= isset($_GET['date_end']) ? trim($_GET['date_end']) : '' ?>">
                                    <span class="input-group-btn">
                                            <a class="btn btn-secondary" id="show_date_end" href="javascript:">
                                                <span class="iconfont icon-daterange"></span>
                                            </a>
                                        </span>
                                </div>
                            </div>
                            <div class="middle-center">
                                <a href="javascript:" class="new-day" data-index="7">近7天</a>
                                <a href="javascript:" class="new-day" data-index="30">近30天</a>
                            </div>
                        </div>
                    </div>
                    <div flex="dir:left">
                        <div class="mr-4">
                            <div class="form-group">
                                <button class="btn btn-primary mr-4">筛选</button>
<!--                                <a class="btn btn-secondary"-->
<!--                                   href="--><?//= Yii::$app->request->url . "&flag=EXPORT" ?><!--">批量导出</a>-->
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <table class="table table-bordered bg-white">
            <tr>
                <th class="order-tab-1">日期</th>
                <th class="order-tab-2">订单数目</th>
                <th class="order-tab-3">订单金额</th>
                <th class="order-tab-4">充值金额</th>
<!--                <th class="order-tab-4">订单状态</th>-->
<!--                <th class="order-tab-5">操作</th>-->
            </tr>
        </table>
        <?php foreach ($list as $order_item): ?>
            <div class="order-item" style="<?= $order_item['flag'] == 1 ? 'color:#ff4544' : '' ?>">

                <table class="table table-bordered bg-white">

                    <tr>
                        <td class="order-tab-1">
                            <div>
                                <?= substr($order_item[0],5) ?>
                            </div>
                        </td>
                        <td class="order-tab-2">
                            <div><?= $order_item['id_sum'] ?>条</div>
                        </td>
                        <td class="order-tab-3">
                            <div><?= $order_item['price_sum'] ?>元</div>
                        </td>
                        <td class="order-tab-4">
                            <div>
                                <?= $order_item[1] ?>元
                            </div>

                        </td>

                    </tr>

                </table>
            </div>
        <?php endforeach; ?>
        <div class="text-center">

        </div>

        <!-- 发货 -->

        </div>
    </div>
</div>
<!-- 修改价格 -->


<?//= $this->render('/layouts/ss') ?>

<script>
    jQuery.datetimepicker.setLocale('zh');
    jQuery('#date_start').datetimepicker({
        datepicker: true,
        timepicker: false,
        format: 'Y-m-d',
        dayOfWeekStart: 1,
        scrollMonth: false,
        scrollTime: false,
        scrollInput: false,
        onShow: function (ct) {
            this.setOptions({
                maxDate: jQuery('#date_end').val() ? jQuery('#date_end').val() : false
            })
        }
    });
    $(document).on('click', '#show_date_start', function () {
        $('#date_start').datetimepicker('show');
    });
    jQuery('#date_end').datetimepicker({
        datepicker: true,
        timepicker: false,
        format: 'Y-m-d',
        dayOfWeekStart: 1,
        scrollMonth: false,
        scrollTime: false,
        scrollInput: false,
        onShow: function (ct) {
            this.setOptions({
                minDate: jQuery('#date_start').val() ? jQuery('#date_start').val() : false
            })
        }
    });
    $(document).on('click', '#show_date_end', function () {
        $('#date_end').datetimepicker('show');
    });
    $(document).on('click', '.new-day', function () {
        var index = $(this).data('index');
        var myDate = new Date();
        var mydate = new Date(myDate.getTime() - index * 24 * 60 * 60 * 1000);
        jQuery('#date_start').datetimepicker('setOptions', {value: mydate});
        jQuery('#date_end').datetimepicker('setOptions', {value: myDate});
    });
    console.log(window.location.href)
    $(document).on('click', '.del', function () {
        var a = $(this);
        $.myConfirm({
            content: a.data('content'),
            confirm: function () {
                $.ajax({
                    url: a.data('url'),
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {
                        if (res.code == 0) {
                            window.location.reload();
                        } else {
                            $.myAlert({
                                title: res.msg
                            });
                        }
                    }
                });
            }
        });
        return false;
    });

    $(document).on("click", ".apply-status-btn", function () {
        var url = $(this).attr("href");
        $.myConfirm({
            content: "确认“" + $(this).text() + "”？",
            confirm: function () {
                $.myLoading();
                $.ajax({
                    url: url,
                    dataType: "json",
                    success: function (res) {
                        $.myLoadingHide();
                        $.myAlert({
                            content: res.msg,
                            confirm: function () {
                                if (res.code == 0)
                                    location.reload();
                            }
                        });
                    }
                });
            }
        });
        return false;
    });


    $(document).on("click", ".send-btn", function () {
        var order_id = $(this).attr("data-order-id");
        $(".send-modal input[name=order_id]").val(order_id);
        $(".send-modal").modal("show");
    });
    $(document).on("click", ".send-confirm-btn", function () {
        var btn = $(this);
        var error = $(".send-form").find(".form-error");
        btn.btnLoading("正在提交");
        error.hide();
        console.log(error);
        $.ajax({
            url: "<?=$urlManager->createUrl(['mch/order/send'])?>",
            type: "post",
            data: $(".send-form").serialize(),
            dataType: "json",
            success: function (res) {
                if (res.code == 0) {
                    btn.text(res.msg);
                    location.reload();
                    $(".send-modal").modal("hide");
                }
                if (res.code == 1) {
                    btn.btnReset();
                    error.html(res.msg).show();
                }
            }
        });
    });


</script>
<!--打印函数-->
<script>
    var LODOP; //声明为全局变量
    //检测是否含有插件
    function CheckIsInstall() {
        try {
            var LODOP = getLodop();
            if (LODOP.VERSION) {
                if (LODOP.CVERSION)
                    $.myAlert({
                        content: "当前有C-Lodop云打印可用!\n C-Lodop版本:" + LODOP.CVERSION + "(内含Lodop" + LODOP.VERSION + ")"
                    });
                else
                    $.myAlert({
                        content: "本机已成功安装了Lodop控件！\n 版本号:" + LODOP.VERSION
                    });

            }
        } catch (err) {
        }
    }
    ;
    //打印预览
    function myPreview() {
        LODOP.PRINT_INIT("");
        LODOP.ADD_PRINT_HTM(10, 50, '100%', '100%', $('#print').html());
    }
    $(document).on('click', '.print', function () {
        var id = $(".send-modal input[name=order_id]").val();
        var express = $(".send-modal input[name=express]").val();
        var post_code = $(".send-modal input[name=post_code]").val();
        $.ajax({
            url: "<?=$urlManager->createUrl(['mch/order/print'])?>",
            type: 'get',
            dataType: 'json',
            data: {
                id: id,
                express: express,
                post_code: post_code
            },
            success: function (res) {
                if (res.code == 0) {
                    $(".send-modal input[name=express_no]").val(res.data.Order.LogisticCode);
                    LODOP.PRINT_INIT("");
                    LODOP.ADD_PRINT_HTM(10, 50, '100%', '100%', res.data.PrintTemplate);
                    LODOP.PRINT_DESIGN();
                } else {
                    $.myAlert({
                        content: res.msg
                    });
                }
            }
        });
    });
</script>
<script>
    $(document).on('click', '.update', function () {
        var order_id = $(this).data('id');
        $('.order-id').val(order_id);
    });
    $(document).on('click', '.add-price', function () {
        var btn = $(this);
        var order_id = $('.order-id').val();
        var price = $('.money').val();
        var update_express = $('.update-express').val();
        var type = btn.data('type');
        var error = $('.form-error');
        btn.btnLoading(btn.text());
        error.hide();
        $.ajax({
            url: "<?=$urlManager->createUrl(['mch/order/add-price'])?>",
            type: 'get',
            dataType: 'json',
            data: {
                order_id: order_id,
                price: price,
                type: type,
                update_express: update_express
            },
            success: function (res) {
                if (res.code == 0) {
                    window.location.reload();
                } else {
                    error.html(res.msg).show()
                }
            },
            complete: function (res) {
                btn.btnReset();
            }
        });
    });
    $(document).on('click', '.is-express', function () {
        if ($(this).val() == 0) {
            $('.is-true-express').prop('hidden', true);
        } else {
            $('.is-true-express').prop('hidden', false);
        }
    });
</script>