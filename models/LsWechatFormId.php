<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/6
 * Time: 17:48
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class LsWechatFormId
 * @package app\models
 *
 * @property string $open_id
 * @property integer $user_id
 * @property string $form_id
 * @property date_time $add_date_time
 * @property string $extension_code
 * @property int $extension_id
 * @property int $send_count
 *
 */
class LsWechatFormId extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%ls_wechat_form_id}}';
    }

    public function setParams($open_id, $form_id){
        $this->open_id = $open_id;
        $this->form_id = $form_id;
    }
}