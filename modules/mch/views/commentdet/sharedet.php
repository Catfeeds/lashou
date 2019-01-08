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
$this->title = '推荐明细';
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
                    <span class="input-group-btn">
                    <button class="btn btn-primary">搜索</button>
                </span>
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
                       href="javascript:void()">全部</a>
                </li>
             
            </ul>
        </div>
        <table class="table table-bordered bg-white">
            <tr>
                <td width="50px">我的ID</td>
                <td width="200px">充值单号</td>
                <td>下级ID</td>
                <td>推荐奖</td>
                <td>时间</td>
                <td>是否充值</td>
                <td>操作</td>
            </tr>
            <?php foreach ($list as $index => $value): ?>
                <tr>
                    <td><?= $value['user_id'] ?></td>
                    <td data-toggle="tooltip" data-placement="top" >
                        <?= $value['order_no'] ?>
                    </td>
                    <td>
                         <?= $value['from_id'] ?>
                        
                    </td>
                    <td>
                    <?= $value['price'] ?>
                    </td>
                    <td>
                        <?= $value['addtime'] ?>
                    </td>
                    <td><?php if($value['is_pay']==1){echo '已充值';}else{    echo '未充值';}  ?></td>
                    <td>
                        
                    </td>
                </tr>
            <?php endforeach; ?>
                
                <tr>
                    <td>合计</td>
                    <td data-toggle="tooltip" data-placement="top" >
                       
                    </td>
                    <td>
                        
                        
                    </td>
                    <td>
                    已发：<?= $hejicomm ?>
                    未发：<?= $hejicomm2 ?>
                    </td>
                    <td>
                       已发：<?= $hejipart ?>
                        未发：<?= $hejipart2 ?>
                    </td>
                    <td>
                    
                    </td>
                    <td>
                        
                    </td>
                </tr>
        </table>
        <div class="text-center">
           
        </div>
    </div>
</div>
<?= $this->render('/layouts/ss'); ?>
<script>
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