<?php

namespace app\models;

use Yii;
use Codeception\PHPUnit\ResultPrinter\HTML;

/**
 * This is the model class for table "{{%form}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property string $user_id
 * @property string $url
 * @property integer $is_del
 * @property integer $addtime
 */
class lsPlayerSource extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ls_player_source}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'user_id','type', 'addtime'], 'integer'],
            [['val'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'Store ID',
            'user_id' => '用戶ID',
            'url' => '視頻地址',
            'is_del' => '是否刪除',
            'addtime' => '時間'
        ];
    }

}
