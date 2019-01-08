<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/2
 * Time: 0:11
 */

namespace app\models;
class GoodsTest extends \yii\db\ActiveRecord
{
	public $goods_id;
	public $goods_name;
	public $goods_price;
	public $wooran_name = "hello";

	public static function tableName()
    {
        return '{{%goods_test}}';
    }
}