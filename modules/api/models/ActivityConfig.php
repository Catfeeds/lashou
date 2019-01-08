<?php
/**
 * Created by PhpStorm.
 * User: wuran
 * Date: 2018/8/4
 * Time: 上午8:21
 */

namespace app\modules\api\models;


class ActivityConfig
{
    const START_DATE_TIME = "2018-08-06 09:00:00";
    const END_DATE_TIME = "2018-08-08 09:00:00";

    const PINGTUAN_ID = 3;

    public static function getCanShareActivityUsers(){
    	return [];
        /*return [200001, 3287280, 429228, 416355, 443144,
            495660, 204606,
            8, 10, 19, 21, 49, 76, 122, 2539, 2569, 2589, 11862, 26693, 29227];*/
    }
}