<?php
defined('YII_RUN') or exit('Access Denied');
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/6/19
 * Time: 16:52
 */
$cat = [
    1 => '关于我们',
    2 => '服务中心',
];
$cat_id = Yii::$app->request->get('cat_id', 2);
$urlManager = Yii::$app->urlManager;
$this->title = $cat[$cat_id];
$returnUrl = Yii::$app->request->referrer;
if (!$returnUrl)
    $returnUrl = $urlManager->createUrl(['mch/article/index', 'cat_id' => $cat_id]);
$this->params['page_navs'] = [
    [
        'name' => '关于我们',
        'active' => $cat_id == 1,
        'url' => $urlManager->createUrl(['mch/article/index', 'cat_id' => 1,]),
    ],
    [
        'name' => '服务中心',
        'active' => $cat_id == 2,
        'url' => $urlManager->createUrl(['mch/article/index', 'cat_id' => 2,]),
    ],
];
?>

<div class="panel mb-3">
    <div class="panel-header"><?= $this->title ?></div>
    <div class="panel-body">
        <form class="auto-form" method="post" return="<?= $returnUrl ?>">
            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">标题</label>
                </div>
                <div class="col-sm-6">
                    <input class="form-control cat-name" name="title" value="<?= $model->title ?>">
                </div>
            </div>
            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label required">排序</label>
                </div>
                <div class="col-sm-6">
                    <input class="form-control cat-name" name="sort"
                           value="<?= $model->sort ? $model->sort : 100 ?>">
                </div>
            </div>
            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class=" col-form-label ">视频</label>
                </div>
                <div class="col-sm-6">
                    <div class="video-picker" data-url="<?= $urlManager->createUrl(['upload/video']) ?>">
                        <div class="input-group short-row">
                            <input class="video-picker-input video form-control" name="video_url"
                                   value="<?= $model->video_url ?>" placeholder="请输入视频源地址或者选择上传视频">
                            <a href="javascript:" class="btn btn-secondary video-picker-btn">选择视频</a>
                        </div>
                        <a class="video-check"
                           href="<?= $model->video_url ? $model->video_url : "javascript:" ?>"
                           target="_blank">视频预览</a>
                        <div class="video-preview"></div>
                        <div>
                            <span class="text-danger fs-sm">
                                支持格式mp4;支持编码H.264;视频大小不能超过<?= \app\models\UploadForm::getMaxUploadSize() ?>MB

                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                    <label class="col-form-label">内容</label>
                </div>
                <div class="col-sm-6">
                        <textarea id="editor" style="width: 100%"
                                  name="content"><?= $model->content ?></textarea>
                </div>
            </div>
            <div class="form-group row">
                <div class="form-group-label col-sm-2 text-right">
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary auto-form-btn" href="javascript:">保存</a>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="<?= $staticBaseUrl ?>/statics/mch/js/uploadVideo.js"></script>
<script src="<?= Yii::$app->request->baseUrl ?>/statics/ueditor/ueditor.config.js"></script>
<script src="<?= Yii::$app->request->baseUrl ?>/statics/ueditor/ueditor.all.min.js"></script>
<script>
    var ue = UE.getEditor('editor', {
        serverUrl: "<?=$urlManager->createUrl(['upload/ue'])?>",
    });
</script>
<script>
    $(document).on('change', '.video', function () {
        $('.video-check').attr('href', this.value);
    });
</script>