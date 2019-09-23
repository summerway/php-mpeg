<?php
/**
 * Created by PhpStorm.
 * User: MapleSnow
 * Date: 2019/9/21
 * Time: 10:21 AM
 */

namespace Streaming\Helpers;


class Arr {

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    public static function filter($array,$keyword){
        return array_filter($array,function($var) use ($keyword){
            return !strpos($var,$keyword);
        });
    }

    /**
     * 获取数组第一个元素
     * @param array $array
     * @param null $callback
     * @param null $default
     * @return mixed
     */
    public static function first($array, $default = null) {
        if (empty($array)) {
            return value($default);
        }

        foreach ($array as $item) {
            return $item;
        }
    }
}