<?php

/**
 * This file is part of the PHP-FFmpeg-video-streaming package.
 *
 * (c) Amin Yazdanpanah <contact@aminyazdanpanah.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streaming\Format;

final class HEVC extends Video
{

    /**
     * HEVC constructor.
     * @param string $videoCodec
     */
    public function __construct($videoCodec = 'libx265')
    {
        $this->setVideoCodec($videoCodec);
    }

    /**
     * Returns the list of available audio codecs for this format.
     *
     * @return array
     */
    public function getAvailableAudioCodecs()
    {
        return array('libx265');
    }
}