<?php
/**
 * Created by IntelliJ IDEA.
 * User: luwei
 * Date: 2017/7/15
 * Time: 13:40
 */

namespace app\modules\api\models;


use app\models\Cart;
use app\models\Goods;
use app\models\LsSsds;
use yii\helpers\VarDumper;

class AddCartForm extends Model
{
    public $user_id;
    public $store_id;
    public $goods_id;
    public $attr;
    public $num;

    public function rules()
    {
        return [
            [['goods_id', 'attr', 'num'], 'required'],
            [['goods_id', 'num'], 'integer'],
            [['num'], 'integer', 'min' => 1],
        ];
    }

    public function save()
    {
        if (!$this->validate())
            return $this->getModelError();

        if($this->goods_id == 199 || $this->goods_id == 205){
            return [
                'code' => 1,
                'msg' => '套餐请直接购买',
            ];
        }
        if($this->goods_id == LsSsds::SHIPPING_FEE_GOODS){
            return [
                'code' => 1,
                'msg' => '该产品不用加入购物车，请直接购买',
            ];
        }
        $goods_query = Goods::find()->where([
            'id' => $this->goods_id,
            'store_id' => $this->store_id,
            'is_delete' => 0,
            'status' => 1,
        ]);
        $goods = $goods_query->one();
        if (!$goods) {
            return [
                'code' => 1,
                'msg' => '商品不存在或已下架...',
            ];
        }

        $this->attr = json_decode($this->attr, true);
        $attr = [];
        foreach ($this->attr as $item) {
            if (!empty($item['attr_id']))
                $attr[] = $item['attr_id'];
        }
        sort($attr);
        $attr = json_encode($attr, JSON_UNESCAPED_UNICODE);
        $cart = Cart::findOne([
            'store_id' => $this->store_id,
            'goods_id' => $this->goods_id,
            'user_id' => $this->user_id,
            'is_delete' => 0,
            'attr' => $attr,
        ]);
        if (!$cart) {
            $cart = new Cart();
            $cart->store_id = $this->store_id;
            $cart->goods_id = $this->goods_id;
            $cart->user_id = $this->user_id;
            $cart->num = 0;
            $cart->addtime = time();
            $cart->is_delete = 0;
            $cart->attr = $attr;
        }
        $cart->num += $this->num;
        if ($cart->save()) {
            return [
                'code' => 0,
                'msg' => '添加购物车成功',
            ];
        } else {
            return $this->getModelError($cart);
        }
    }
}