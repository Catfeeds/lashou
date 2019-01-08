<?php

namespace app\models;

use Yii;
use Codeception\PHPUnit\ResultPrinter\HTML;

/**
 * This is the model class for table "{{%share}}".
 *
 * @property integer $id
 * @property integer $store_id
 * @property integer $user_id
 * @property integer $from_id
 * @property double  $price
 * @property integer $status
 * @property integer $type
 * @property string  $remarks
 * @property integer $is_del
 * @property integer $add_time

 */
class NexusLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%nexus_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['content'], 'required'],
            [['store_id'], 'integer'],
           [['time','content'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_id' => 'store_id',
            'content' => 'content',
            'time' => 'time',
        ];
    }
    

}
