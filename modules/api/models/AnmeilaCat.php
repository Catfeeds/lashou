<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/9/7
 * Time: 下午1:04
 */

namespace app\modules\api\models;

use app\models\Cat;
use app\models\Model;

class AnmeilaCat extends Model
{
    public $cat;

    public $store_id;
    public $parent_id;
    public $name;
    public $pic_url;
    public $big_pic_url;
    public $sort;
    public $advert_pic;
    public $advert_url;
    public $is_show;

    public $anmeila_id;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'store_id', 'parent_id'], 'required'],
            [['sort', 'store_id', 'is_show', 'anmeila_id'], 'integer'],
            [['pic_url', 'big_pic_url', 'advert_pic', 'advert_url'], 'string'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => '分类名称',
            'pic_url' => '分类图片url',
            'sort' => '排序，升序',
            'advert_pic' => '广告图片',
            'advert_url' => '广告链接',
            'is_show' => '是否显示',
        ];
    }

    /**
     * 编辑
     * @return array
     */
    public function save()
    {
        if ($this->validate()) {
            $parent_cat_exist = true;
            if ($this->parent_id)
                $parent_cat_exist = Cat::find()->where([
                    'id' => $this->parent_id,
                    'store_id' => $this->store_id,
                    'is_delete' => 0,
                ])->exists();
            if (!$parent_cat_exist)
                return [
                    'code' => 1,
                    'msg' => '上级分类不存在，请重新选择'
                ];
            $cat = $this->cat;
            if ($cat->isNewRecord) {
                $cat->is_delete = 0;
                $cat->addtime = time();
            }
            $cat->attributes = $this->attributes;
            return $cat->saveCat();
        } else {
            return $this->getModelError();
        }
    }
}