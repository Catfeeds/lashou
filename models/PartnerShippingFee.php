<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/7/24
 * Time: 下午8:26
 */

namespace app\models;


class PartnerShippingFee
{
    private static function getParams($province_id){
        //id:region, first,  then, weight
        $setting = [
            '3268' => [
                'region' => '其他',
                'first' => 100,
                'then' => 100,
                'weight' => 1000,
            ],

            '2' => [
                'region' => '北京',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '20' => [
                'region' => '天津',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '38' => [
                'region' => 'hebei',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '218' => [
                'region' => 'shanxi',
                'first' => 7,
                'then' => 7,
                'weight' => 1000,
            ],

            '349' => [
                'region' => 'neimenggu',
                'first' => 7,
                'then' => 7,
                'weight' => 1000,
            ],

            '465' => [
                'region' => 'liaoning',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '580' => [
                'region' => 'jilin',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '650' => [
                'region' => 'heilongjiang',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '793' => [
                'region' => 'shanghai',
                'first' => 6,
                'then' => 1,
                'weight' => 1000,
            ],

            '811' => [
                'region' => 'jiangshu',
                'first' => 6,
                'then' => 1,
                'weight' => 1000,
            ],

            '921' => [
                'region' => 'zhejiang',
                'first' => 6,
                'then' => 1,
                'weight' => 1000,
            ],

            '1022' => [
                'region' => 'anhui',
                'first' => 6,
                'then' => 1,
                'weight' => 1000,
            ],

            '1144' => [
                'region' => 'fujian',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '1239' => [
                'region' => 'jiangxi',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '1351' => [
                'region' => 'shandong',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '1506' => [
                'region' => 'henan',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '1683' => [
                'region' => 'hubei',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '1804' => [
                'region' => 'hunan',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '1941' => [
                'region' => 'guangdong',
                'first' => 7,
                'then' => 4,
                'weight' => 1000,
            ],

            '2088' => [
                'region' => 'guangxi',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '2214' => [
                'region' => 'hainan',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '2261' => [
                'region' => 'chongqing',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '2302' => [
                'region' => 'shichuan',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '2507' => [
                'region' => 'guizhou',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '2605' => [
                'region' => 'yunnan',
                'first' => 7,
                'then' => 5,
                'weight' => 1000,
            ],

            '2751' => [
                'region' => 'xizhang',
                'first' => 14,
                'then' => 17,
                'weight' => 1000,
            ],

            '2833' => [
                'region' => '陕西',
                'first' => 7,
                'then' => 7,
                'weight' => 1000,
            ],

            '2951' => [
                'region' => 'ganshu',
                'first' => 7,
                'then' => 7,
                'weight' => 1000,
            ],

            '3053' => [
                'region' => 'qinghai',
                'first' => 7,
                'then' => 7,
                'weight' => 1000,
            ],

            '3106' => [
                'region' => 'ningxia',
                'first' => 7,
                'then' => 7,
                'weight' => 1000,
            ],

            '3134' => [
                'region' => 'xinjiang',
                'first' => 14,
                'then' => 17,
                'weight' => 1000,
            ],
        ];

        if(isset($setting[$province_id])){
            return $setting[$province_id];
        }else{
            return [
                'region' => 'default',
                'first' => 100,
                'then' => 100,
                'weight' => 1000,
            ];
        }
    }
    public static function genShippingFee($weight, $address){
        $res = [];
        $res['log']['weight'] = $weight;
        $res['log']['province_id'] = $address->province_id;

        $params = self::getParams($address->province_id);
        $res['log']['params'] = $params;

        $shipping_fee = 0;
        if($weight <= $params['weight']){
            $shipping_fee = $params['first'];
        }else{
            $shipping_fee = $params['first']
                + ceil(($weight - $params['weight']) / $params['weight']) * $params['then'];
        }
        $res['shipping_fee'] = $shipping_fee;

        return $res;
    }
}