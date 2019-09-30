<?php
/**
 * Created by PhpStorm.
 * User: Maple.xia
 * Date: 2019/9/21
 * Time: 2:07 PM
 */

namespace Streaming\Helpers;

use Streaming\Exception\Exception;

/**
 * 文件操作类
 * Class FileManager
 * @package Streaming\Helpers
 */
class FileManager
{

    /**
     * Get an array of all files in a directory.
     *
     * @param  string  $directory
     * @return array
     */
    public static function files($directory) {
        return array_slice(scandir($directory), 2);
    }

    /**
     * 下载文件
     * @param string $url 文件url
     * @param string $saveTo 保存目录
     * @return bool
     */
    public static function downloadFile($url, $saveTo) {
        if ($fpRemote = fopen($url, 'rb')) {
            if ($fpLocal = fopen($saveTo , 'wb')) {
                while ($buffer = fread($fpRemote, 8192)) {
                    fwrite($fpLocal, $buffer);
                }

                fclose($fpLocal);
            } else {
                fclose($fpRemote);
                return false;
            }
            fclose($fpRemote);
            return $saveTo;
        } else {
            return false;
        }
    }

    /**
     * 创建目录
     * @param string $path 目录名
     * @param int $mode 权限
     * @return bool
     */
    public static function makeDir($path, $mode = 0777)
    {
        if(file_exists($path)){
            return true;
        }
        return mkdir($path, $mode);
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function tmpFile(): string
    {
        return tempnam(static::tmpDirPath(), 'stream');
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function tmpDir(): string
    {
        $tmp_dir = static::tmpDirPath() . DIRECTORY_SEPARATOR . Helper::randomString() . DIRECTORY_SEPARATOR;
        static::makeDir($tmp_dir);

        return $tmp_dir;
    }

    /**
     * @return string
     */
    private static function tmpDirPath(): string
    {
        $tmp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "php_ffmpeg_video_streaming";
        static::makeDir($tmp_path);

        return $tmp_path;
    }

    /**
     * @param $source
     * @param $destination
     * @return bool
     */
    public static function moveDir($source, $destination)
    {
        !file_exists($destination) && static::makeDir($destination);
        return rename($source,$destination);
    }

    /**
     * 递归删除目录
     * @param $dir
     * @param bool $preserve 是否保存根目录
     * @return bool
     */
    public static function deleteDirectory($dir,$preserve = false)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return @unlink($dir);
        }

        foreach (static::files($dir) as $item) {
            static::deleteDirectory($dir . DIRECTORY_SEPARATOR . $item, false);
        }

        !$preserve && @rmdir($dir);

        return true;
    }
}