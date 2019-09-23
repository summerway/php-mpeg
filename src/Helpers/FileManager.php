<?php

/**
 * This file is part of the PHP-FFmpeg-video-streaming package.
 *
 * (c) Amin Yazdanpanah <contact@aminyazdanpanah.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Streaming\Helpers;

use Streaming\Exception\Exception;
use Streaming\Exception\RuntimeException;

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
     * @param $url
     * @param $saveTo
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
     * @param $dirname
     * @param int $mode
     * @throws Exception
     */
    public static function makeDir($dirname, $mode = 0777)
    {
        if(file_exists($dirname)){
            return true;
        }
        return mkdir($dirname);
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
     * @throws Exception
     */
    private static function tmpDirPath(): string
    {
        $tmp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "php_ffmpeg_video_streaming";
        static::makeDir($tmp_path);

        return $tmp_path;
    }

    /**
     * @param string $source
     * @param string $destination
     * @throws Exception
     */
    public static function moveDir($source, $destination)
    {
        !file_exists($destination) && static::makeDir($destination);
        return rename($source,$destination);
    }

    /**
     * @param $dir
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
            if (!static::deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        if ( ! $preserve) @rmdir($directory);

        return true;
    }
}