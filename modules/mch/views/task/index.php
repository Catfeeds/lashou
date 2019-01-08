<?php
defined('YII_RUN') or exit('Access Denied');
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/6/19
 * Time: 16:52
 */
use \app\models\User;

$urlManager = Yii::$app->urlManager;
$this->title = '任务管理';
$this->params['active_nav_group'] = 4;
?>

<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">

        <div class="float-right mb-4">
            <form method="get">

                <?php $_s = ['keyword'] ?>
                <?php foreach ($_GET as $_gi => $_gv):if (in_array($_gi, $_s)) continue; ?>
                    <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                <?php endforeach; ?>

                <div class="input-group">
                    <div class="col-2">
                        <select class="form-control" style="width:100%" name="search">
                            <option value="1" selected="">模糊查询</option>
                            <option value="2">ID</option>
                            <option value="3">昵称</option>
                            <option value="4">手机号</option>
                        </select>
                    </div>
                    <input class="form-control"
                           placeholder="微信昵称"
                           name="keyword"
                           autocomplete="off"
                           value="<?= isset($_GET['keyword']) ? trim($_GET['keyword']) : null ?>">
                    <span class="input-group-btn">
                    <button class="btn btn-primary">搜索</button>
                </span>
                </div>
            </form>
        </div>
        <table class="table table-bordered bg-white">
            <thead>
            <tr>
                <th>ID</th>
                <th>头像</th>
                <th>昵称</th>
                <th>联系方式</th>

                <th>申请时间</th>
                <th>完成阶段</th>
<!--                <th>开始日期</th>-->
                <th>审核</th>

                <th>操作</th>
            </tr>
            </thead>
            <?php foreach ($list as $u): ?>
                <tr>
                    <td><?= $u['user_id'] ?></td>
                    <td>
                        <img src="<?= $u['avatar_url'] ?>" style="width: 34px;height: 34px;margin: -.6rem 0;">
                    </td>
                    <td><?= $u['nickname']; ?><br><?=$u['wechat_open_id']?></td>
                    <td><?= $u['mobile']; ?></td>

                    <td><?= date('Y-m-d H:i:s', $u['addtime']) ?></td>

                    <td><?= $u['stage'] ?></td>
<!--                    <td>--><?//= date('Y-m-d H:i:s', $u['day']['day'])?><!--</td>-->
                    <td id="<?= $u['id']?>">
                        <?php if($u['status']==0):?><span style="color: red">未审核</span>
                        <?php else:?><span style="color: blue">已审核</span>
                        <?php endif?>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-danger del" href="javascript:"
                           data-url="<?= $urlManager->createUrl(['mch/task/check', 'id' => $u['id']]) ?>"
                           data-id ="<?= $u['id']?>"
                           data-content="确认审核？">审核</a>

                    </td>


                </tr>
            <?php endforeach; ?>
        </table>
        <div class="text-center">
            <?= \yii\widgets\LinkPager::widget(['pagination' => $pagination,]) ?>
            <div class="text-muted"><?= $row_count ?>条数据</div>
        </div>
    </div>
</div>
<!-- 充值积分 -->

<script>
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
                            var id = a.data('id');
                            $("#"+id).html(res.msg);
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
    $(document).on('click', '.rechangeBtn', function () {
        var a = $(this);
        var id = a.data('id');
        var integral = a.data('integral');
        $('#user_id').val(id);
        $('.integral-reduce').attr('data-integral', integral);
    });
    $(document).on('change', '.integral-reduce', function () {
        $('#integral').val($(this).data('integral'));
    });
    $(document).on('click', '.save-rechange', function () {
        var user_id = $('#user_id').val();
        var integral = $('#integral').val();
        var oldIntegral = $('.integral-reduce').data('integral');
        var rechangeType = $("input[type='radio']:checked").val();
        if (rechangeType == '2') {
            if (integral > oldIntegral) {
                $('.rechange-error').css('display', 'block');
                $('.rechange-error').text('当前用户积分不足');
                return;
            }
        }
        if (!integral || integral <= 0) {
            $('.rechange-error').css('display', 'block');
            $('.rechange-error').text('请填写积分');
            return;
        }
        $.ajax({
            url: "<?= Yii::$app->urlManager->createUrl(['mch/user/rechange']) ?>",
            type: 'post',
            dataType: 'json',
            data: {user_id: user_id, integral: integral, _csrf: _csrf, rechangeType: rechangeType},
            success: function (res) {
                if (res.code == 0) {
                    window.location.reload();
                } else {
                    $('.rechange-error').css('display', 'block');
                    $('.rechange-error').text(res.msg);
                }
            }
        });
    });


</script>
