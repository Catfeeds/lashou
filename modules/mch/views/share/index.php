<?php
defined('YII_RUN') or exit('Access Denied');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/8
 * Time: 14:57
 */
/* @var $pagination yii\data\Pagination */
/* @var $setting \app\models\Setting */
use yii\widgets\LinkPager;

$urlManager = Yii::$app->urlManager;
$this->title = '分销商列表';
$this->params['active_nav_group'] = 5;
$status = Yii::$app->request->get('status');
if ($status === '' || $status === null || $status == -1)
    $status = -1;
?>
<style type="text/css">
    .sortCls{
        cursor:pointer;
    }
</style>

<style>
    .page-bar{
        margin:40px;
    }
    ul,li{
        margin: 0px;
        padding: 0px;
    }
    li{
        list-style: none
    }
    .page-bar li:first-child>a {
        margin-left: 0px
    }
    .page-bar a{
        border: 1px solid #ddd;
        text-decoration: none;
        position: relative;
        float: left;
        padding: 6px 12px;
        margin-left: -1px;
        line-height: 1.42857143;
        color: #337ab7;
        cursor: pointer
    }
    .page-bar a:hover{
        background-color: #eee;
    }
    .page-bar a.banclick{
        cursor:not-allowed;
    }
    .page-bar .active a{
        color: #fff;
        cursor: default;
        background-color: #337ab7;
        border-color: #337ab7;
    }
    .page-bar i{
        font-style:normal;
        color: #d44950;
        margin: 0px 4px;
        font-size: 12px;
    }
</style>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <div class="mb-3 clearfix">
            <div class="p-4 bg-shaixuan">
                <form method="get">
                    <?php $_s = ['keyword','page','sortColumn','sortType'] ?>
                    <?php foreach ($_GET as $_gi => $_gv):if (in_array($_gi, $_s)) continue; ?>
                        <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                    <?php endforeach; ?>
                    <div flex="dir:left">
                        <div>
                            <div class="input-group">
                                <input class="form-control"
                                       placeholder="姓名/微信昵称/ID"
                                       name="keyword"
                                       autocomplete="off"
                                       value="<?= isset($_GET['keyword']) ? trim($_GET['keyword']) : null ?>">
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary">筛选</button>
                                    </span>
                                    <span style="padding-left:20px" class="input-group-btn">
                                        <a class="btn btn-primary batch" href="javascript:void(0)"
                                           data-url="<?= $urlManager->createUrl(['mch/share/batch']) ?>"
                                           data-content="是否批量通过" data-type="0">批量通过</a>
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
                       href="<?= $urlManager->createUrl(['mch/share/index']) ?>">全部</a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 0 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/share/index', 'status' => 0]) ?>">未审核<?= $count['count_1'] ? '(' . $count['count_1'] . ')' : "(0)" ?></a>
                </li>
                <li class="nav-item">
                    <a class="status-item nav-link <?= $status == 1 ? 'active' : null ?>"
                       href="<?= $urlManager->createUrl(['mch/share/index', 'status' => 1]) ?>">已审核<?= $count['count_2'] ? '(' . $count['count_2'] . ')' : "(0)" ?></a>
                </li>
            </ul>
        </div>
        <form action="<?= $urlManager->createUrl(['mch/share/index']) ?>" id="searchForm">
            <?php $_s = ['keyword','page','sortColumn','sortType'] ?>
            <?php foreach ($_GET as $_gi => $_gv):if (in_array($_gi, $_s)) continue; ?>
                <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
            <?php endforeach; ?>
        <input id="sortType" type="text" value="" name="sortType">
        <input id="sortColumn" type="text" value="" name="sortColumn">
        <table class="table table-bordered bg-white table-striped table-condensed" id="contentTable" data-toggle="table" data-animation="false">
            <tr>
                <td style="text-align: left;" data-type="id" class="sortCls">
                    <label class="checkbox-label">
                        <input type="checkbox" class="goods-all">
                        <span class="label-icon"></span>

                        <span class="label-text">ID</span>
                    </label>
                    <i class="icon ion-navicon-round id" style="float: right"></i>
                    <div style="margin-left: 15px">星级</div>
                </td>
                <td width="200px">微信信息</td>
                <td>
                    <div>姓名</div>
                    <div>手机号</div>
                </td>
                <td>
                    <div data-type="total_price" class="sortCls">累计佣金</div><i class="icon ion-arrow-down-b total_price" style="float: right"></i>
                    <div>打款佣金</div>
                </td>
                <td data-sort-order="asc" data-sortable="true"  id="packCntTh" class="sortCls " data-type="first">
                    <i class="icon ion-navicon-round first" style="float: right"></i>
                    <div>下级分销商</div>

                    <div>直系星级;团队</div>
                </td>
                <td>状态</td>
                <td>时间</td>
                <td>会员订单</td>
                <td>备注信息</td>
                <td>操作</td>
            </tr>
            <?php foreach ($list as $index => $value): ?>
                <tr>
                    <td class="nowrap" style="text-align: left;" data-toggle="tooltip"
                        data-placement="top" title="<?= $value['user_id'] ?>">
                        <label class="checkbox-label">
                            <input data-user_id="<?= $value['user_id'] ?>" type="checkbox"
                                   class="goods-one"
                                   value="<?= $value['id'] ?>">
                            <span class="label-icon"></span>
                            <span class="label-text"><?= $value['user_id'] ?></span>
                        </label>
                        <div> <?php if ($value['level'] == 1): ?>
                            <span style="margin-left: 15px"> 一星</span>
                        <?php elseif ($value['level'] == 2): ?>
                            <span style="color: red;font-weight: bold;margin-left: 15px">二星</span>
                            <?php else:?>
                            <?= $value['level']?>
                            <?php endif;?>
                        </div>
                    </td>
                    <td data-toggle="tooltip" data-placement="top" title="<?= $value['nickname'] ?>">
                        <span
                            style="width: 150px;display:block;white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><img
                                src="<?= $value['avatar_url'] ?>"
                                style="width: 30px;height: 30px;margin-right: 10px"><?= $value['nickname'] ?></span>
                    </td >
                    <td data-toggle="tooltip" data-placement="top" title="<?= $value['name'] ?>">
                        <div style="width: 150px;display:block;white-space:nowrap; overflow:hidden; text-overflow:ellipsis;""><?= $value['name'] ?></div>
                        <div><?= $value['mobile'] ?></div>
                    </td>
                    <td>
                        <div><?= $value['total_price'] ?></div>
                        <div><?= $value['price'] ?></div>
                    </td>
                    <td>
                        <?php if ($value['status'] == 1): ?>
                            <?php if ($setting->level == 0): ?>
                                <span>0</span>
                            <?php else: ?>
                                <?php if ($setting->level >= 1): ?>
                                    <div style="text-align: center">

                                        <a class="team" data-index="<?= $value['id'] ?>" data-level="1" @click="goList(<?= $value['user_id'] ?>)"
                                            href="javascript:" data-toggle="modal" data-user_id = "<?= $value['user_id'] ?>" data-url="<?= $urlManager->createUrl(['mch/share/getteam', 'user_id' => $value['user_id']]) ?>"
                                            data-target="#exampleModal" ><?= $setting->first_name ? $setting->first_name : "一级" ?>
                                            ：<?= $value['first'] ?></a>
                                    </div>
                                    <div style="text-align: center">
                                        <span><?= $value['fans1']?></span>人;
                                        <span><?= $value['fans']?></span>人
                                    </div>
                                <?php endif; ?>

                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= ($value['status'] == 0) ? "未审核" : (($value['status'] == 1) ? "通过" : "不通过") ?></td>
                    <td>
                        <div class="fs-sm">申请时间：<?= date('Y-m-d H:i', $value['addtime']); ?></div>
                        <div class="fs-sm">
                            审核时间：<?= ($value['time'] != 0) ? date('Y-m-d H:i', $value['time']) : ""; ?></div>
                    </td>
                    <td>
                        <?php if ($value['order_count'] && $value['order_count'] > 0): ?>
                            <div>
                                <a target="_blank" href="<?= $urlManager->createUrl(['mch/order/index', 'user_id' => $value['user_id']]) ?>">商城订单：<?= $value['order_count'] ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if ($value['ms_order_count'] && $value['ms_order_count'] > 0): ?>
                            <div>
                                <a target="_blank" href="<?= $urlManager->createUrl(['mch/miaosha/order/index', 'user_id' => $value['user_id']]) ?>">秒杀订单：<?= $value['ms_order_count'] ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if ($value['pt_order_count'] && $value['pt_order_count'] > 0): ?>
                            <div>
                                <a target="_blank" href="<?= $urlManager->createUrl(['mch/group/order/index', 'user_id' => $value['user_id']]) ?>">拼团订单：<?= $value['pt_order_count'] ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if ($value['yy_order_count'] && $value['yy_order_count'] > 0): ?>
                            <div>
                                <a target="_blank" href="<?= $urlManager->createUrl(['mch/book/order/index', 'user_id' => $value['user_id']]) ?>">预约订单：<?= $value['yy_order_count'] ?></a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style='width:120px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;'
                             title='<?= $value['seller_comments'] ?>'>
                            <?= $value['seller_comments'] ?>
                        </div>
                    </td>

                    <td>
                        <div class="btn btn-group" role="group">
                            <a class="btn btn-secondary dropdown-toggle" href="javascript:" type="button"
                               id="dropdownMenuButton"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-animation="false">
                                操作
                            </a>
                            <div class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownMenuButton">
                                <?php if ($value['status'] == 0): ?>
                                    <a class="dropdown-item del" href="javascript:"
                                       data-url="<?= $urlManager->createUrl(['mch/share/status', 'status' => 1, 'id' => $value['id']]) ?>"
                                       data-content="是否审核通过？">审核通过</a>
                                    <a class="dropdown-item del" href="javascript:"
                                       data-url="<?= $urlManager->createUrl(['mch/share/status', 'status' => 2, 'id' => $value['id']]) ?>"
                                       data-content="是否审核不通过？">不通过</a>
                                <?php elseif ($value['status'] == 1): ?>
                                    <a class="dropdown-item"
                                       href="<?= $urlManager->createUrl(['mch/share/order', 'parent_id' => $value['user_id']]) ?>">分销订单</a>
                                    <a class="dropdown-item"
                                       href="<?= $urlManager->createUrl(['mch/share/cash', 'id' => $value['id']]) ?>">提现明细</a>
                                    <a class="dropdown-item del" href="javascript:"
                                       data-url="<?= $urlManager->createUrl(['mch/share/del', 'id' => $value['id']]) ?>"
                                       data-content="是否删除分销商？">删除分销商</a>
                                <?php endif; ?>
                                <a href="javascript:" class="dropdown-item" data-toggle="modal" data-target="#myModal"
                                        onclick="add_comments(<?= $value['id'] ?>,'<?= $value['seller_comments'] ?>' )">添加备注
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
</div>
</form>
        <div class="text-center">
            <?= LinkPager::widget(['pagination' => $pagination,]) ?>
            <div class="text-muted"><?= $count['total'] ? $count['total'] : 0 ?>条数据</div>
        </div>

        <!-- 下线 -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true"  data-backdrop="static" data-keyboard="false" data-animation="false">
            <div class="modal-dialog modal-lg" style="width: 1000px">
                <div class="modal-content" id="app" style="display: none;margin-bottom: -87px;overflow:scroll;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">下线情况</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="resetnum()">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="p-4 bg-shaixuan">

                            <?php $_s = ['keyword'] ?>
                            <?php foreach ($_GET as $_gi => $_gv):if (in_array($_gi, $_s)) continue; ?>
                                <input type="hidden" name="<?= $_gi ?>" value="<?= $_gv ?>">
                            <?php endforeach; ?>
                            <div flex="dir:left">
                                <div>
                                    <div class="input-group">
                                        <input class="form-control" id="search"
                                               placeholder="姓名/微信昵称/ID"
                                               name="keyword"
                                               autocomplete="off"
                                               value="">
                                        <span class="input-group-btn">
                                        <button class="btn btn-primary"   @click="search()" >筛选</button>
                                    </span>

                                    </div>
                                </div>
                            </div>

                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered ">
                            <tr>
                                <td>序号</td>
                                <td>上级昵称</td>
                                <td>下线等级</td>
                                <td>昵称</td>
                                <td>ID</td>
                                <td>加入时间</td>
                                <td>下级</td>
                            </tr>
                            <tr v-for="(item,index) in list" v-if="list.length > 0">
                                <td>{{index+1}}</td>
                                <td data-toggle="tooltip" data-placement="top" v-bind:title=name>
                        <span
                                style="width: 120px;display:block;white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{name}}</span>
                                </td>
                                <td>{{level}}</td>
                                <td data-toggle="tooltip" data-placement="top" v-bind:title="item.nickname">
                        <span
                                style="width: 120px;display:block;white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{item.nickname}}</span>
                                </td>
<!--                                <td>{{item.nickname}}</td>-->
                                <td>{{item.id}}</td>
                                <td v-if="item.addtime != '1970-01-01'">
                                    {{item.addtime}}
                                </td>
                                <td v-else="item.addtime" style="color: red">
                                   未注册
                                </td>
                                <td><a class="team2" @click="goList(item.id)" data-index="item.id" data-level="2"
                                       href="javascript:" data-toggle="modal1"
                                       data-target="#exampleModal"> {{jibie}}
                                        ：{{item.first}}</a></td>
                            </tr>
                            <tr v-else>
                                <td> 未数据</td>
                            </tr>
                        </table>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="resetnum()">关闭</button>
                    </div>
                </div>

                <div class="text-center" >
                    <div class="page-bar" id="page-bar">
                        <ul>
                            <li v-if="cur>1"><a v-on:click="btnClick(1)">首页</a></li>
                            <li v-if="cur<=1"><a class="banclick">首页</a></li>
                            <li v-if="cur>1"><a v-on:click="cur--,pageClick()">上一页</a></li>
                            <li v-if="cur==1"><a class="banclick">上一页</a></li>
                            <li v-for="index in indexs"  v-bind:class="{ 'active': cur == index}">
                                <a v-on:click="btnClick(index)" data-user_id="<?= $value['user_id'] ?>">{{ index }}</a>
                            </li>
                            <li v-if="cur!=all"><a v-on:click="cur++,pageClick()">下一页</a></li>
                            <li v-if="cur == all"><a class="banclick">下一页</a></li>
                            <li><a  v-if="cur != all" v-on:click="btnClick(all)">共<i>{{all}}</i>页</a></li>
                            <li><a  v-if="cur == all" class="banclick">共<i>{{all}}</i>页</a></li>
                        </ul>
                    </div>
                </div>
        </div>

    </div>
</div>

<div class="modal fade" aria-labelledby="myModalLabel" aria-hidden="true" id="myModal"
     style="margin-top:200px;display: ;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="height:40px;">
                <h5 class="modal-title" id="myModalLabel">
                    添加备注
                </h5>
            </div>
            <div class="modal-body">
                备注：<textarea name="seller_comments" id="seller_comments" cols="75" rows="5"
                             style="resize: none;"></textarea>
                <input type="hidden" value="" name="user_id" id="user_id">
            </div>
            <div class="modal-footer" style="height:40px;">
                <button type="button" class="btn btn-default" data-dismiss="modal" id="close">关闭</button>
                <button type="button" class="btn btn-primary" id="member" onclick="comments()">添加</button>
            </div>
        </div>
    </div>
</div>

<?= $this->render('/layouts/ss'); ?>
<script type="text/javascript">
    $(function(){
    //alert(getCookieValue('first'))
        if(getCookieValue('first') !=''){
            alert(getCookieValue('first'))
                var type = getCookie('first');
                if(type == 'desc')
                $(".first").attr("class", "icon ion-arrow-down-b first");
                else if(type == 'asc'){
                    $(".first").attr("class", "icon ion-arrow-up-b first");
                }else {
                    $(".first").attr("class", "icon ion-navicon-round first");
                }
        }
        if(getCookieValue('id') !=''){

            var type = getCookie('id');
            if(type == 'desc')
                $(".id").attr("class", "icon ion-arrow-down-b id");
            else if(type == 'asc'){
                $(".id").attr("class", "icon ion-arrow-up-b id");
            }else {
                $(".id").attr("class", "icon ion-navicon-round id");
            }
        }
        if(getCookieValue('total_price') !=''){
            alert(getCookieValue('total_price'))
            var type = getCookieValue('total_price');
            if(type == 'desc')
                $(".total_price").attr("class", "icon ion-arrow-down-b total_price");
            else if(type == 'asc'){
                $(".total_price").attr("class", "icon ion-arrow-up-b total_price");
            }else{
                $(".total_price").attr("class", "icon ion-navicon-round total_price");
            }
        }

    })
    /**
     * 默认的cookie写入方法
     * @param name
     * @param value
     */
    function setCookie(name,value){
        var Days = 1;
        var exp = new Date();
        exp.setTime(exp.getTime() + Days*24*60*60*1000);
        document.cookie = name + "="+ encodeURI (value) + ";expires=" + exp.toGMTString()+";path=/leasingCalculator";
    }
    /**
     * 获取Cookie中的值
     * @param objName
     * @returns
     */
    function getCookie(objName){//获取指定名称的cookie的值

        var strCookie=document.cookie;

        var arrCookie=strCookie.split(";");

        for(var i=0;i<arrCookie.length;i++){

            var c=arrCookie[0].split("=");

            if(c[0]==name){

                return c[1];

            }

        }

        return "";
    }
    function getCookieValue(name) {

        var strCookie=document.cookie;

        var arrCookie=strCookie.split(";");

        for(var i=0;i<arrCookie.length;i++){

            var c=arrCookie[0].split("=");

            if(c[0]==name){

                return c[1];

            }

        }

        return "";

    }

    $(document).ready(function(){
        $(".sortCls").click(function(){
            var sortColumn = $(this).data('type');
            var sortType;
            if($(this).find("i").hasClass("icon ion-arrow-down-b")){
                sortType = "asc";
                $("i").attr("class", "");
                $(this).find("i").addClass("icon ion-arrow-up-b");
                //var userName="xiaoming";

            }else if($(this).find("i").hasClass("icon ion-arrow-up-b")){
                sortType = "desc";
                $("i").attr("class", "");
                $(this).find("i").addClass("icon ion-arrow-down-b");
            }else{
                $("i").attr("class", "");
                $(this).find("i").addClass("icon ion-arrow-down-b");
                sortType = "desc";
            }
            //table 设置两个隐藏域，用于向后台传递排序字段和排序方式（降序/升序）
            $("#sortType").attr("value",'');
            $("#sortColumn").attr("value",'');
            $("#sortType").attr("value",sortType);
            $("#sortColumn").attr("value",sortColumn);
            alert(sortColumn);
            document.cookie = "'first'='';'id'='';'total_price'=''"
            document.cookie= sortColumn +'='+ sortType;
            setCookie(sortColumn,sortType);
            console.log(getCookieValue(sortColumn))
            //提交Table所在的Form表单，发起请求
            $("#searchForm").attr("action","<?= $urlManager->createUrl(['mch/share/index']) ?>");
            $("#searchForm").submit();
            //alert(1)
            layer.load('正在查询，请稍等...');
        });
    });
</script>

<script>

    var cnum = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
    var e_num = ['zero','first','second','third']
    function rp (n) {
        var s = '';
        n = '' + n; // 数字转为字符串
        for (var i = 0; i < n.length; i++) {
            s += cnum[parseInt(n.charAt(i))];
        }
        return s;
    }
    function tpn(n) {
        var s = '';
        n = '' + n; // 数字转为字符串
        for (var i = 0; i < n.length; i++) {
            s += e_num[parseInt(n.charAt(i))];
        }
        return s;
    }

    var app = new Vue({
        el: "#app",
        data: {
            team:<?=$team?>,
            list: [],
            name: "",
            level: 1,
            num:1,
            jibie:'',
            xiaji:[],
            totalCount:10,
            user_id:0,
            keyword:''

        },
        methods: {
            goList: function(itemId){
                $('#app').show();
                app.user_id = itemId;
                pageBar.cur = 1;
                var url = "<?= $urlManager->createUrl(['mch/share/xiaji']) ?>";
                $.ajax({
                    url: url,
                    data:{user_id:itemId,page:1},
                    async:false,
                    success: function (res) {
                        result = eval('('+res+')');
                        team = result.list;
                    }
                });
                app.list = [];
                app.name = '';
                app.num = app.num + 1;
                app.list = team;
                app.name = team[0].sjnc
                app.level = "<?=$setting->third_name?>" || rp(app.num) +"级";
                app.jibie = rp(app.num+1)+"级";
                app.totalCount = result.page.totalCount;
                pageBar.all = Math.ceil(app.totalCount/10);
            },
            search:function () {
                keyword = $("#search").val();
                if(keyword){
                    app.keyword = keyword;
                }
                user_id = app.user_id;
                $.ajax({
                    url:"<?= $urlManager->createUrl(['mch/share/xiaji']) ?>",
                    data:{user_id:user_id,keyword:keyword,page:1},
                    async:false,
                    success:function (val) {
                        resu = eval('('+val+')');
                        team = resu.list;
                        app.totalCount = resu.page.totalCount;
                        pageBar.all = Math.ceil(app.totalCount/10);
                        pageBar.cur = 1;
                    }
                });
                app.list = [];
                app.name = '';
                app.list = team;
                app.name = team[0].sjnc;
                app.level = "<?=$setting->third_name?>" || rp(app.num) +"级";
                app.jibie = rp(app.num+1)+"级"
            }

        }
    });
    $('#app').show();
    function resetnum() {
        app.num = 1;
        app.totalCount = 10;
        pageBar.cur = 1;
    }
    $(document).on('click', '.team', function () {
        $('#app').show();
        var index = $(this).data('index');
        var level = $(this).data('level');
        var user_id = $(this).data('user_id');
        var url = $(this).data('url');
        app.user_id = user_id;
        $.ajax({
            url:"<?= $urlManager->createUrl(['mch/share/xiaji']) ?>",
            data:{user_id:user_id},
            async:false,
            success:function (val) {
                 resu = eval('('+val+')');
                 team = resu.list;
                 app.totalCount = resu.page.totalCount;
                pageBar.all = Math.ceil(app.totalCount/10);
            }
        })
        app.list = [];
        app.name = '';
        app.level = '';
        app.jibie = '二级';
        app.name = team[0].sjnc
//        $.each(result,function (i) {
//            if (result[i].id == index) {
//                app.xiaji = (result[i].first)
//            }
//        })
      //  $.each(team, function (i) {
    //        if (team[i].id == index) {
                if (level == 1) {
                    app.list = team;
//                    console.log(app.list)
//                    app.list = app.list.push(result[i].first)
//                    console.log(app.list)
                    app.level = "<?=$setting->first_name?>" || "一级";


                }
                if (level == 2) {
                    app.list = team[i].secondChildren;
                    app.level = "<?=$setting->second_name?>" || "二级";
                }
                if (level == 3) {
                    app.list = team[i].thirdChildren;
                    app.level = "<?=$setting->third_name?>" || "三级";
                }


    //        }
       // })
    });
    var pageBar = new Vue({
        el: '#page-bar',
        data: {
            all: 1 , //总页数
            cur: 1//当前页码
        },
        watch: {
            cur: function(oldValue , newValue){
                console.log(arguments);
            }
        },
        methods: {
            btnClick: function(data){//页码点击事件
                if(data != this.cur){
                    this.cur = data
                }
                var user_id = app.user_id;
                //console.log(data)
                $.ajax({
                    url:"<?= $urlManager->createUrl(['mch/share/xiaji']) ?>"+"&user_id="+user_id+"&page="+data+"&keyword="+app.keyword,
                    //data:{user_id:user_id,page:data},
                    async:false,
                    processData:false,
                    success:function (val) {
                        resu = eval('('+val+')');
                        team = resu.list;
                        console.log(team)
//                        app.totalCount = (resu.page.totalCount);
//                        pageBar.all = Math.ceil(app.totalCount/10);
                    }
                })

                app.list = [];
                app.name = '';
                app.level = '';
                app.jibie = '二级';
                app.list = team;
                app.level = "<?=$setting->first_name?>" || "一级";
                app.name = team[0].sjnc;
            },
            pageClick: function(){
                console.log('现在在'+this.cur+'页');
                var user_id = app.user_id;
                //console.log(data)
                $.ajax({
                    url:"<?= $urlManager->createUrl(['mch/share/xiaji']) ?>"+"&user_id="+user_id+"&page="+this.cur+"&keyword="+app.keyword,
                    //data:{user_id:user_id,page:data},
                    async:false,
                    processData:false,
                    success:function (val) {
                        resu = eval('('+val+')');
                        team = resu.list;
                        console.log(team)
//                        app.totalCount = (resu.page.totalCount);
//                        pageBar.all = Math.ceil(app.totalCount/10);
                    }
                })

                app.list = [];
                app.name = '';
                app.jibie = '二级';
                // app.name = team[0].sjnc
                app.list = team;
                app.level = "<?=$setting->first_name?>" || "一级";
                app.name = team[0].sjnc;
            }
        },

        computed: {
            indexs: function(){
                var left = 1;
                var right = this.all;
                var ar = [];
                if(this.all>= 5){
                    if(this.cur > 3 && this.cur < this.all-2){
                        left = this.cur - 2
                        right = this.cur + 2
                    }else{
                        if(this.cur<=3){
                            left = 1
                            right = 5
                        }else{
                            right = this.all
                            left = this.all -4
                        }
                    }
                }
                while (left <= right){
                    ar.push(left)
                    left ++
                }
                return ar
            }

        }
    })

    function add_comments(id, seller_comments) {
        $("#user_id").val(id);
        $("#seller_comments").val(seller_comments);
    }

    var AddCommentsUrl = "<?= $urlManager->createUrl(['mch/share/seller-comments']) ?>";
    function comments() {
        var user_id = $("#user_id").val();
        var seller_comments = $("#seller_comments").val();
        $.ajax({
            url: AddCommentsUrl,
            type: 'get',
            dataType: 'json',
            data: {
                user_id: user_id,
                seller_comments: seller_comments
            },
            success: function (res) {
                if (res.code == 0) {
                    $('#myModal').css('display', 'none');
                    $.myAlert({
                        content: "添加成功", confirm: function (e) {
                            window.location.reload();
                        }
                    });
                } else {
                    $.myAlert({
                        content: "添加失败"
                    });
                }
            }
        });
    }
</script>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
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
                content: "请先勾选商品"
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
                goods.user_id = $(all[i]).data('user_id');
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
                            window.location.reload();
                        } else {

                        }
                    },
                    complete: function () {
                        $.myLoadingHide();
                    }
                });
            }
        })
    });
</script>
