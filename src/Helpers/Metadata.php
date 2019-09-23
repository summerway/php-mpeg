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

use Streaming\Export;

/**
 * 格式化返回数据
 * Class Metadata
 * @package Streaming\Helpers
 */
class Metadata
{
    /**
     * @var Export
     */
    private $export;

    /**
     * Metadata constructor.
     * @param Export $export
     */
    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    /**
     * @return mixed
     */
    public function extract()
    {
        $metadata["video"] = $this->getVideoMetadata();
        $metadata["streams"] = $this->getStreamsMetadata();

        $name = $this->export->getPathInfo()["filename"] . "-" . Helper::randomString(12) . ".json";
        $filename = $this->export->getPathInfo()["dirname"] . DIRECTORY_SEPARATOR . $name;
        file_put_contents($filename, json_encode($metadata, JSON_PRETTY_PRINT));

        return [
            'filename' => $filename,
            'metadata' => $metadata
        ];
    }

    /**
     * @return mixed
     */
    private function getVideoMetadata()
    {
        $probe = $this->export->getMedia()->probe();
        $streams = $probe['streams']->all();
        $format = $probe['format']->all();

        foreach ($streams as $key => $stream) {
            $streams[$key] = $stream->all();
        }

        return [
            'format' => $format,
            'streams' => $streams
        ];
    }

    /**
     * @return mixed
     */
    private function getStreamsMetadata()
    {
        $stream_path = $this->export->getPathInfo();
        $metadata["filename"] = $stream_path["dirname"] . DIRECTORY_SEPARATOR . $stream_path["basename"];
        $metadata["created_at"] = date("Y-m-d H:i:s");

        $metadata["qualities"] = $this->getQualities();

        $format_class = explode("\\", get_class($this->export->getFormat()));
        $metadata["format"] = end($format_class);

        $export_class = explode("\\", get_class($this->export));
        $metadata["streaming_technique"] = end($export_class);

        if ($this->export instanceof DASH) {
            $metadata["dash_adaption"] = $this->export->getAdaption();
        } elseif ($this->export instanceof HLS) {
            $metadata["hls_time"] = $this->export->getHlsTime();
            $metadata["hls_cache"] = $this->export->isHlsAllowCache();
            $metadata["encrypted_hls"] = (bool)$this->export->getHlsKeyInfoFile();
            $metadata["ts_sub_directory"] = $this->export->getTsSubDirectory();
            $metadata["base_url"] = $this->export->getHlsBaseUrl();
        }

        return $metadata;
    }

    /**
     * @return array
     */
    private function getQualities()
    {
        $qualities = [];
        foreach ($this->export->getRepresentations() as $key => $representation) {
            if ($representation instanceof Representation) {
                $qualities[$key]["dimensions"] = strtoupper($representation->getResize());
                $qualities[$key]["video_bitrate"] = $representation->getKiloBitrate() * 1024;
            }
        }

        return $qualities;
    }
}