<?php

/**
 * This file is part of the PHP-FFmpeg-video-streaming package.
 *
 * (c) Amin Yazdanpanah <contact@aminyazdanpanah.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streaming\Filters;

use Streaming\Helpers\FileManager;
use Streaming\Helpers\Helper;
use Streaming\HLS;
use Streaming\Representation;

class HLSFilter extends Filter
{
    /**
     * @param $media
     * @return mixed|void
     * @throws \Streaming\Exception\Exception
     */
    public function setFilter($media)
    {
        $this->filter = $this->HLSFilter($media);
    }

    /**
     * @param HLS $hls
     * @return array
     * @throws \Streaming\Exception\Exception
     */
    private function HLSFilter(HLS $hls)
    {
        $filter = [];
        $total_count = count($representations = $hls->getRepresentations());
        $counter = 0;
        $path_parts = $hls->getPathInfo();
        $dirname = str_replace("\\", "/", $path_parts["dirname"]);
        $filename = substr($path_parts["filename"], -50);
        $ts_sub_dir = Helper::appendSlash($hls->getTsSubDirectory());
        $base_url = Helper::appendSlash($hls->getHlsBaseUrl());

        if ($ts_sub_dir) {
            FileManager::makeDir($dirname . DIRECTORY_SEPARATOR . $ts_sub_dir);
            $base_url = $base_url . $hls->getTsSubDirectory() . "/";
        }

        foreach ($representations as $representation) {
            if ($representation instanceof Representation) {
                $filter[] = "-s:v";
                $filter[] = $representation->getResize();
                $filter[] = "-crf";
                $filter[] = "20";
                $filter[] = "-sc_threshold";
                $filter[] = "0";
                $filter[] = "-g";
                $filter[] = "48";
                $filter[] = "-keyint_min";
                $filter[] = "48";
                $filter[] = "-hls_list_size";
                $filter[] = "0";
                $filter[] = "-hls_time";
                $filter[] = $hls->getHlsTime();
                $filter[] = "-hls_allow_cache";
                $filter[] = (int)$hls->isHlsAllowCache();
                $filter[] = "-b:v";
                $filter[] = $representation->getKiloBitrate() . "k";
                $filter[] = "-maxrate";
                $filter[] = intval($representation->getKiloBitrate() * 1.2) . "k";
                $filter[] = "-hls_segment_filename";
                $filter[] = $dirname . "/" . $ts_sub_dir . $filename . "_" . $representation->getHeight() . "p_%04d.ts";

                if ($base_url) {
                    $filter[] = "-hls_base_url";
                    $filter[] = $base_url;
                }

                if ($hls->getHlsKeyInfoFile()) {
                    $filter[] = "-hls_key_info_file";
                    $filter[] = $hls->getHlsKeyInfoFile();
                }

                $filter[] = "-strict";
                $filter[] = $hls->getStrict();

                if (++$counter !== $total_count) {
                    $filter[] = $dirname . "/" . $filename . "_" . $representation->getHeight() . "p.m3u8";
                }
            }
        }
        return $filter;
    }
}