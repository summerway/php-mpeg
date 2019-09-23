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


class ExportHLSPlaylist
{
    /**
     * @param $filename
     * @param $representations
     * @param $basename
     */
    public static function savePlayList($filename, $representations, $basename)
    {
        file_put_contents($filename, static::generateContents($representations, $basename));
    }

    /**
     * @param $representations
     * @param $basename
     * @return string
     */
    private static function generateContents($representations, $basename)
    {
        $content[] = "#EXTM3U";
        $content[] = "#EXT-X-VERSION:3";

        foreach ($representations as $representation) {
            if ($representation instanceof Representation) {
                $content[] = "#EXT-X-STREAM-INF:BANDWIDTH=" . $representation->getKiloBitrate() * 1024 . ",RESOLUTION=" . $representation->getResize();
                $content[] = $basename . "_" . $representation->getHeight() . "p.m3u8";
            }
        }

        return implode(PHP_EOL, $content);
    }
}