<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/9/7
 * Time: 下午12:13
 */

namespace app\modules\api\controllers;

use app\models\Cat;
use app\models\Goods;
use app\modules\api\models\AnmeilaCat;
use app\modules\api\models\AnmeilaGoods;

class AnmeilaGoodsController extends Controller
{
    /**
     * @return string
     *
     * id:
     * category:json
     *
     * model 要加public 和 rules
     */
    public function actionCategory(){
        /*echo json_encode([
            'parent_id' => 0,
            'name' => 'for test',
            'sort' => 100,
            'pic_url' => 'pic_url',
            'big_pic_url' => 'big_pic_url',
            'advert_pic' => 'advert_pic',
            'advert_url' => 'advert_url',
            'is_show' => 1
        ]);*/
        $anmeila_id = intval(\Yii::$app->request->post('id'));
        if(empty($anmeila_id)){
            $this->renderJson([
                'code' => 1,
                'msg' => '安美拉 ID 不能为空'
            ]);
        }
        $cat = Cat::findOne(['anmeila_id' => $anmeila_id]);
        if (!$cat) {
            $cat = new Cat();
        }
        $form = new AnmeilaCat();
        $model = \Yii::$app->request->post('category');
        $model = json_decode($model, true);
        $model['store_id'] = $this->store->id;
        $model['anmeila_id'] = $anmeila_id;

        $form->attributes = $model;

        $form->cat = $cat;
        return json_encode($form->save(), JSON_UNESCAPED_UNICODE);
    }

    public function actionGoods(){
        $anmeila_id = intval(\Yii::$app->request->post('id'));

        if(empty($anmeila_id)){
            $this->renderJson([
                'code' => 1,
                'msg' => '安美拉 ID 不能为空'
            ]);
        }

        $goods = Goods::findOne(['anmeila_id' => $anmeila_id, 'store_id' => $this->store->id]);
        if (!$goods) {
            $goods = new Goods();
        }
        $form = new AnmeilaGoods();

        $model = \Yii::$app->request->post('goods');
        $model = json_decode($model, true);
        if($model['quick_purchase'] == 0){
            $model['hot_cakes'] = 0;
        }
        if(count($model['cat_id']) > 0){
            $model['cat_id'] = Cat::find()->where(['in', 'anmeila_id', $model['cat_id']])
                ->select('id')
                ->column();
        }
        $model['store_id'] = $this->store->id;
        $model['anmeila_id'] = $anmeila_id;
        $form->attributes = $model;
        $form->attr = \Yii::$app->request->post('attr');
        $form->attr = json_decode($form->attr, true);
        $form->goods_card = \Yii::$app->request->post('goods_card');
        $form->full_cut = \Yii::$app->request->post('full_cut');
        $form->integral = \Yii::$app->request->post('integral');
        $form->goods = $goods;
        return json_encode($form->save(), JSON_UNESCAPED_UNICODE);
    }

}