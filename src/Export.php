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
use Streaming\Qiniu;
use Qiniu\Storage\UploadManager;
use Streaming\Exception\Exception;
use Streaming\Exception\InvalidArgumentException;
use Streaming\Exception\RuntimeException;
use Streaming\Filters\Filter;
use Streaming\Traits\Formats;
use Streaming\Helpers\Helper;
use Streaming\Helpers\Metadata;
use Streaming\Helpers\FileManager;

abstract class Export
{
    use Formats;

    /** @var object */
    protected $media;

    /** @var array */
    protected $path_info;

    /** @var string */
    protected $strict = "-2";

    /**
     * Export constructor.
     * @param Media $media
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
        $this->path_info = pathinfo($media->getPath());
    }

    /**
     * @param string $path
     * @param bool $metadata
     * @return mixed
     * @throws Exception
     */
    public function save(string $path = null, $metadata = false)
    {
        $path = $this->getPath($path);

        try {
            is_null($this->getFilter()) ?
                $this->media : $this->media->addFilter($this->getFilter());

            $this->media->save($this->getFormat(), $path);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException(sprintf("There was an error saving files: \n\n reason: \n %s", $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        $response = ($metadata) ? (new Metadata($this))->extract() : $path;

        if ($this->media->isTmp()) {
            $this->deleteOriginalFile();
        }

        return $response;
    }

    /**
     * @param Qiniu $qiniu
     * @param string $bucket 上传空间
     * @param string $stashPath 暂存地址
     * @return bool|mixed
     * @throws Exception
     */
    public function save2qiniu(Qiniu $qiniu, $bucket, $stashPath = null) {
        if(is_null($stashPath)){
            list(, $stashPath) = $this->saveToTemporaryFolder($stashPath);
        }else{
            $this->save($stashPath);
        }

        sleep(1);

        return $qiniu->upload($bucket,$stashPath,false);
    }

    /**
     * @return Filter|null
     */
    protected function getFilter(){
        return null;
    }

    /**
     * @param $path
     * @return string
     * @throws Exception
     */
    private function getPath($path): string
    {
        if (null !== $path) {
            $this->path_info = pathinfo($path);
        }

        if (null === $path && $this->media->isTmp()) {
            $this->deleteOriginalFile();
            throw new InvalidArgumentException("You need to specify a path. It is not possible to save to a tmp directory");
        }

        $dirname = str_replace("\\", "/", $this->path_info["dirname"]);
        $filename = substr($this->path_info["filename"], -50);

        FileManager::makeDir($dirname);

        switch (true){
            case $this instanceof DASH:
                $path = $dirname . "/" . $filename . ".mpd";
                break;
            case $this instanceof HLS:
                $representations = $this->getRepresentations();
                $path = $dirname . "/" . $filename . "_" . end($representations)->getHeight() . "p.m3u8";
                ExportHLSPlaylist::savePlayList($dirname . DIRECTORY_SEPARATOR . $filename . ".m3u8", $this->getRepresentations(), $filename);
                break;
            case $this instanceof MP4:
                $path = $dirname . "/" . $filename . ".mp4";
                break;
        }

        return $path;
    }

    /**
     * @return array
     */
    public function getPathInfo(): array
    {
        return $this->path_info;
    }

    /**
     * @return object|Media
     */
    public function getMedia(): Media
    {
        return $this->media;
    }

    private function deleteOriginalFile()
    {
        sleep(1);
        @unlink($this->media->getPath());
    }

    /**
     * @param $path
     * @param $metadata
     * @return array
     * @throws Exception
     */
    private function saveToTemporaryFolder($path)
    {
        $basename = Helper::randomString();

        if (null !== $path) {
            $basename = pathinfo($path, PATHINFO_BASENAME);
        }

        $tmp_dir = FileManager::tmpDir();
        $tmp_file = $tmp_dir . $basename;

        return [$this->save($tmp_file), $tmp_dir];
    }

    /**
     * @param string|null $path
     * @param $tmp_dir
     * @throws Exception
     */
    private function moveTmpFolder($path, $tmp_dir)
    {
        if (null !== $path) {
            FileManager::moveDir($tmp_dir, pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR);
        } else {
            FileManager::deleteDirectory($tmp_dir);
        }
    }

    /**
     * @param string $strict
     * @return Export
     */
    public function setStrict(string $strict): Export
    {
        $this->strict = $strict;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrict(): string
    {
        return $this->strict;
    }
}