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

use Streaming\Exception\InvalidArgumentException;
use Streaming\Helpers\Helper;

class AutoRepresentations
{
    /** @var \FFMpeg\FFProbe\DataMapping\Stream $video */
    private $video;

    /** @var \FFMpeg\FFProbe\DataMapping\Format $format */
    private $format;

    /**
     * regular video's heights
     *
     * @var array side_values
     */
    private $side_values = [2160, 1080, 720, 480, 360, 240, 144];

    /**
     * AutoRepresentations constructor.
     * @param array $probe
     * @param null | array $side_values
     * @param array $k_bitrate_values
     */
    public function __construct(array $probe, array $side_values = null)
    {
        $this->video = $probe['streams']->videos()->first();
        $this->format = $probe['format'];
        $this->getSideValues($side_values);
    }

    /**
     * @return array
     */
    private function getDimensions(): array
    {
        $width = $this->video->get('width');
        $height = $this->video->get('height');

        return [$width, $height, $width / $height];
    }

    /**
     * @return array
     */
    public function get(): array
    {
        list($w, $h, $r) = $this->getDimensions();

        $reps[] = $this->addRep($w, $h);

        foreach ($this->side_values as $key => $height) {
            $reps[] = $this->addRep(Helper::roundToEven($r * $height), $height);
        }

        return array_reverse($reps);
    }

    /**
     * @param $width
     * @param $height
     * @return Representation
     * @throws InvalidArgumentException
     */
    private function addRep($width, $height)
    {
        return (new Representation())->setResize($width, $height);
    }

    /**
     * @param array|null $side_values
     */
    private function getSideValues($side_values)
    {
        if ($side_values) {
            $this->side_values = $side_values;
            return;
        }

        $h = $this->getDimensions()[1];

        $this->side_values = array_values(array_filter($this->side_values, function ($height) use ($h) {
            return $height < $h;
        }));
    }
}