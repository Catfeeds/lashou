<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%express}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $code
 * @property integer $sort
 * @property integer $is_delete
 */
class LsShare extends \yii\db\ActiveRecord
{
//    public $access_token;
//    public $user_id;
//    public $uid;
//    public $add_time;
//    public $type;
//    public $extension_code;
//    public $extension_id;
//    public $val;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ls_share}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            [['sort', 'is_delete'], 'integer'],
//            [['name', 'code'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
//            'id' => 'ID',
//            'name' => 'Name',
//            'code' => 'Code',
//            'sort' => 'Sort',
//            'is_delete' => 'Is Delete',
        ];
    }
}
