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
class Vote extends \yii\db\ActiveRecord
{

//    public $username;
//    public $uid;
//    public $add_time;
//    public $avatar;
//    public $declaration;
//    public $extension_id;
//    public $val;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ls_vote}}';
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
