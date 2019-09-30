<?php
/**
 * Created by PhpStorm.
 * User: Maple.xia
 * Date: 2019/9/22
 * Time: 3:07 PM
 */

namespace Streaming\Helpers;

use Streaming\Exception\InvalidArgumentException;

/**
 * 公共帮助类
 * Class Helper
 * @package Streaming\Helpers
 */
class Helper {
    /**
     * round a number to nearest even number
     *
     * @param float $number
     * @return int
     */
    public static function roundToEven($number) {
        return (($number = intval($number)) % 2 == 0) ? $number : $number + 1;
    }

    /**
     * @param int $length
     * @return bool|string
     */
    public static function randomString($length = 10) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 1, $length);
    }

    /**
     * @param string $word
     * @return bool|string
     */
    public static function appendSlash($word) {
        if ($word) {
            return rtrim($word, '/') . '/';
        }
        return $word;
    }

    /**
     * @param string $url
     * @return bool
     */
    public static function isURL($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Your URL($url) is not valid! Your URL should start with (http://) or (https://).");
        }

        return true;
    }
}