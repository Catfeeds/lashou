<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/8
 * Time: 12:20
 */

namespace app\modules\api\controllers;


use app\models\User;

class VideoController extends Controller
{
    /*public function actionTest(){

        $test = '<form enctype=”multipart/form-data” action="/index.php?store_id=1&r=api/video/upload&access_token=' . $_REQUEST['access_token'] . '&force=lashou123654">
    <input type="file" name="file" id="file"><br>
    <input type="submit" value="submit">
</form>';
        exit($test);
    }*/
    public function actionUpload(){
        $user_info = User::findOne(\Yii::$app->user->identity->id);
        if(empty($user_info)){
            $this->renderJson([
                'code' => -1,
                'msg' => '请先登录'
            ]);
        }
        print_r([1, $_FILES]);
        if(move_uploaded_file($_FILES['file']['tmp_name'],$_FILES['file']['name'])){
            $this->renderJson([
                'code' => 0,
                'msg' => '上传成功'
            ]);
        }else{
            $this->renderJson([
                'code' => 0,
                'msg' => '上传失败'
            ]);
        }
    }
}