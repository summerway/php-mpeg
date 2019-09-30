<?php

/**
 * This file is part of the PHP-FFmpeg-video-streaming package.
 *
 * (c) Amin Yazdanpanah <contact@aminyazdanpanah.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streaming;

use FFMpeg\Exception\ExceptionInterface;
use FFMpeg\FFMpeg as BFFMpeg;
use FFMpeg\FFProbe;
use Psr\Log\LoggerInterface;
use Streaming\Helpers\Helper;
use Streaming\Helpers\FileManager;
use Streaming\Qiniu;
use Streaming\Exception\Exception;
use Streaming\Exception\InvalidArgumentException;
use Streaming\Exception\RuntimeException;

class FFMpeg
{
    /** @var BFFMpeg */
    protected $ffmpeg;

    /**
     * @param $ffmpeg
     */
    public function __construct(BFFMpeg $ffmpeg)
    {
        $this->ffmpeg = $ffmpeg;
    }

    /**
     * @param string $path
     * @param bool $is_tmp
     * @return Media
     */
    public function open($path, $is_tmp = false)
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException("There is no file in this path: " . $path);
        }

        try {
            return new Media($this->ffmpeg->open($path), $path, $is_tmp);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException(sprintf("There was an error opening this file: \n\n reason: \n %s", $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param $url
     * @param null $save_to
     * @return Media
     * @throws Exception
     */
    public function fromURL($url, $save_to = null)
    {
        Helper::isURL($url);
        list($is_tmp, $save_to) = $this->isTmp($save_to);

        FileManager::downloadFile($url,$save_to);
        return $this->open($save_to, $is_tmp);
    }

    /**
     * @param Qiniu $qiniu
     * @param $url
     * @return Media
     * @throws Exception
     */
    public function fromQiniu(Qiniu $qiniu,$url) {
        $url = $qiniu->privateDownloadUrl($url);
        return $this->fromURL($url);
    }

    /**
     * @param $path
     * @return array
     * @throws Exception
     */
    private function isTmp($path)
    {
        $is_tmp = false;

        if (null === $path) {
            $is_tmp = true;
            $path = FileManager::tmpFile();
        }

        return [$is_tmp, $path];
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->ffmpeg, $method], $parameters);
    }

    /**
     * @param array $config
     * @param LoggerInterface $logger
     * @param FFProbe|null $probe
     * @return FFMpeg
     */
    public static function create($config = array(), LoggerInterface $logger = null, FFProbe $probe = null)
    {
        return new static(BFFMpeg::create($config, $logger, $probe));
    }
}