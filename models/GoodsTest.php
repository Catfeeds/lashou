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
	private $_wooranname = "hello99";
	public $wooran = "wooran99";

	public static function tableName()
    {
        return '{{%goods_test}}';
    }

    public function setWooranname(){
    	$this->_wooranname = "hello wooran i99";
    }

    public function getWooranname(){
    	return $this->_wooranname;
    }
}