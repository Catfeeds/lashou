<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/3
 * Time: 13:42
 */
defined('YII_RUN') or exit('Access Denied');
use \app\models\User;
use \app\models\Level;

/* @var \app\models\User $user */
/* @var \app\models\Level[] $level */
$urlManager = Yii::$app->urlManager;
$this->title = '修改上级';
$this->params['active_nav_group'] = 4;
?>
<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <div class="">
            <form method="post" class="form auto-form" autocomplete="off"
                  return="<?= $urlManager->createUrl(['mch/user/index']) ?>">
                <div class="form-body">
                    <div class="form-group row">
                        <div class="form-group-label col-2 text-right">
                            <label class="col-form-label">会员</label>
                        </div>
                        <div class="col-5">
                            <div>
                                <img src="<?= $user->avatar_url ?>" style="width: 50px;height:50px;">
                                <span><?= $user->nickname ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="form-group-label col-2 text-right">
                            <label class="col-form-label">原上级id</label>
                        </div>
                        <div class="col-5">
                            <input type="text" class="form-control" disabled=""  name="" placeholder="" style="width:250px;" value="<?= $user->parent_id ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="form-group-label col-2 text-right">
                            <label class="col-form-label">请输入上级id</label>
                        </div>
                        <div class="col-5">
                            <input type="text" class="form-control" name="parent_id" placeholder="请输入上级id" style="width:250px;" value="">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="form-group-label col-2 text-right">
                            <label class="col-form-label">选择要修改的内容</label>
                        </div>
                        <div class="col-9 col-form-label"><label class="custom-control custom-checkbox"><input type="checkbox" name="uupdata[]" value="1" class="custom-control-input use-attr"> <span class="custom-control-indicator"></span> <span class="custom-control-description">替换原有上级</span></label></div>
                        <div class="form-group-label col-2 text-right">
                            <label class="col-form-label"></label>
                        </div>
                        <div class="col-9 col-form-label"><label class="custom-control custom-checkbox"><input type="checkbox" name="uupdata[]" value="2" class="custom-control-input use-attr"> <span class="custom-control-indicator"></span> <span class="custom-control-description">重计298佣金，加入拉手充值进来的</span></label></div>
                        <div class="form-group-label col-2 text-right">
                            <label class="col-form-label"></label>
                        </div>
                        <div class="col-9 col-form-label"><label class="custom-control custom-checkbox"><input type="checkbox" name="uupdata[]" value="3" class="custom-control-input use-attr"> <span class="custom-control-indicator"></span> <span class="custom-control-description">重计298佣金，充值过298的</span></label></div>
                        <div class="form-group-label col-2 text-right">
                            <label class="col-form-label"></label>
                        </div>
                        <div class="col-9 col-form-label"><label class="custom-control custom-checkbox"><input type="checkbox" name="uupdata[]" value="4" class="custom-control-input use-attr"> <span class="custom-control-indicator"></span> <span class="custom-control-description">重计已下订单佣金</span></label></div>
                     
                    </div>
                       
                    
                    
                    <div class="form-group row">
                        <div class="form-group-label col-2 text-right">
                        </div>
                        <div class="col-5">
                            <a class="btn btn-primary auto-form-btn" href="javascript:">保存</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
