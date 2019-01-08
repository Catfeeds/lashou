<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/5/14
 * Time: 17:45
 */

namespace app\models;


class Lashou
{
    const DAI_FA_START_TIME = "2018-07-26 00:00:00";
    public static function guessLike(){
        $res = [];

        $guess_like_cash = "guess_like_cash";
        if ($cache = \Yii::$app->cache->get($guess_like_cash)){
            return json_decode($cache, true);
        }

        $user_id = \Yii::$app->user->identity->id;

        //1.有订单 取分类中销量最好的
        $cats = null;

        $order_ids = Order::find()->where(["user_id" => $user_id])->select("id")->column();

        if($order_ids && false){
            $cat_ids = OrderDetail::find()->alias("og")->where(["in", "order_id", $order_ids])
                ->leftJoin(GoodsCat::tableName() . " as gc", "gc.goods_id = og.goods_id")
                ->select("gc.cat_id")
                ->distinct()
                ->column();

            if($cat_ids && $son_cat_ids = Cat::find()->select("id")->where(["in", "parent_id", $cat_ids])->column()){
                $cat_ids = array_merge($cat_ids, $son_cat_ids);
            }

            if($cat_ids){
                $order_goods_query = OrderDetail::find()->alias("og")
                    ->select("og.goods_id, sum(og.num) as total_num, g.id,g.name,g.price,g.original_price,g.cover_pic pic_url, g.virtual_sales,g.unit")
                    ->leftJoin(Goods::tableName() . " as g", "g.id=og.goods_id")
                    ->leftJoin(GoodsCat::tableName() . " as gc", "g.id = gc.goods_id")
                    ->where(["in", "gc.cat_id", $cat_ids])
                    ->andWhere(['not in', 'g.id', [199, 205, 209]])
                    ->andWhere("g.is_delete=0")
                    ->groupBy("og.goods_id")
                    ->orderBy("total_num desc")
                    ->limit(10);

                $sql = $order_goods_query->createCommand()->getRawSql();
                $res_goods = $order_goods_query->asArray()->all();

                foreach ($res_goods as $key => $goods) {
                    if (!$goods['pic_url']) {
                        $goods['pic_url'] = Goods::getGoodsPicStatic($goods['id'])->pic_url;
                    }
                    $res[] = $goods;
                }

                /*print_r([$sql, $res_goods, $res]);
                exit();*/
            }
        }else{
            $order_goods_query = OrderDetail::find()->alias("og")
                    ->select("og.goods_id, sum(og.num) as total_num, g.id,g.name,g.price,g.original_price,g.cover_pic pic_url, g.virtual_sales,g.unit")
                    ->leftJoin(Goods::tableName() . " as g", "g.id=og.goods_id")
                    ->where(['not in', 'g.id', [199, 205, 209]])
                    ->andWhere("g.is_delete=0")
                    ->andWhere("g.status=1")
                    ->groupBy("og.goods_id")
                    ->orderBy("total_num desc")
                    ->limit(10);

            $sql = $order_goods_query->createCommand()->getRawSql();
            $res_goods = $order_goods_query->asArray()->all();

            foreach ($res_goods as $key => $goods) {
                if (!$goods['pic_url']) {
                    $goods['pic_url'] = Goods::getGoodsPicStatic($goods['id'])->pic_url;
                }
                $res[] = $goods;
            }
        }

        \Yii::$app->cache->set($guess_like_cash, json_encode($res), 3600);

        return $res;
    }

    //return boolean
    public static function canJoinActivity(){
        $user_id = \Yii::$app->user->identity->id;

        $user_info = User::findOne($user_id);
        if($user_info && ($user_info->is_distributor == 1 || $user_info->is_partner == 1)){
            return false;
        }

        return true;
    }

    public static function hasNewerHongbao($user_id = 0){
        if($user_id == 0){
            $user_id = \Yii::$app->user->identity->id;
        }

        $log = LsBonusLog::find()->where(['uid' => $user_id, 'type' => 1, 'extension_id' => 1])->one();
        return empty($log) ? false : true;
    }

    public static function getShop($user_id = 0){
        $res = [
            'id' => 1,
            'name' => '拉手平台',
            'mobile' => '0579-86896450',
            'address' => '浙江省金华市东阳市 北麓西街672号'
        ];

        $user_info = User::findOne($user_id);
        $partner = self::getPartner($user_info->id);
        if($partner){
            $res['id'] = $partner->id;
            $res['name'] = $partner->nickname;
            $res['mobile'] = $partner->mobile;
            $res['address'] = $partner->partner_address;
        }

        return $res;
    }

    public static function getShopByPartner($partner_id=0){
        $res = [
            'name' => '拉手平台',
            'mobile' => '0579-86896450',
            'address' => '浙江省金华市东阳市 北麓西街672号',
            'longitude' => 0,
            'latitude' => 0
        ];

        if(!empty($partner_id)){
            $partner = User::findOne($partner_id);
            if($partner){
                $res['name'] = $partner->nickname;
                $res['mobile'] = $partner->mobile;
                $res['address'] = $partner->partner_address;
            }
        }

        return $res;
    }

    public static function getPartner($parent_id){

        $parentMember = User::findOne($parent_id);
        if(empty($parentMember)) return null;

        if($parentMember->is_partner != 1){
            $parent_id = $parentMember->parent_id;
            return self::getPartner($parent_id);
        }
        else
            return $parentMember;
    }

    public  static function getSsdsPlayerCount(){
        $goods_info = Goods::findOne(199);
        $virtual = $goods_info->virtual_sales;
        $actual = Ssdsplayer::find()->count();

        return $virtual + $actual;
    }

    public static function getSsdsPlayerShareResult(){
        $user_id = \Yii::$app->user->identity->id;
        $res = LsShareFrom::find()
            ->where(['from_id' => $user_id])
            ->groupBy('type')
            ->select('type, count(id) as num, from_id')
            ->asArray()
            ->all();

        $result = ['friend'=>0, 'group'=>0, 'timeline'=>0];//1.朋友 2.群 3.海报 朋友圈
        foreach($res as $item){
            switch ($item['type']){
                case 1:
                    $result['friend'] = $item['num'];
                    break;
                case 2:
                    $result['group'] = $item['num'];
                    break;
                case 3:
                    $result['timeline'] = $item['num'];
                    break;
            }

        }

        return $result;
    }

    public static function getGoodsCost($order_id){
        $query = OrderDetail::find()->alias('og')
            ->leftJoin(Goods::tableName() . ' as g', 'og.goods_id = g.id')
            ->where(['og.order_id' => $order_id])
            ->select("og.num, g.cost_price");

        $total = $query->sum('og.num * g.cost_price');

        return $total;
    }

    public static function getGoodsCostOld($order_id){
        $query = OrderDetail::find()->alias('og')
            ->leftJoin(Goods::tableName() . ' as g', 'og.goods_id = g.id')
            ->where(['og.order_id' => $order_id])
            ->select("og.num, g.old_cost_price");

        $total = $query->sum('og.num * g.old_cost_price');

        return $total;
    }
}