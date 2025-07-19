<?php

namespace App\Components\LabelaryZPL\Endpoint;

use GuzzleHttp\Exception\GuzzleException;

class Printers extends Base
{
    public const DEFAULT_ACCEPT_REQUEST = 'application/pdf';
    public const DEFAULT_DESIRED_PRINT_DENSITY = '8dpmm'; // 6dpmm, 8dpmm, 12dpmm, and 24dpmm
    public const DEFAULT_WIDTH = 4; // inches
    public const DEFAULT_HEIGHT = 6; // inches

    /**
     * Get label
     * @see http://labelary.com/service.html#parameters
     * @param array $options
     * @return mixed
     * @throws GuzzleException
     */
    public function labels(array $options)
    {
        if (!isset($options['dpmm'])) {
            $options['dpmm'] = self::DEFAULT_DESIRED_PRINT_DENSITY;
        }

        if (!isset($options['width'])) {
            $options['width'] = self::DEFAULT_WIDTH;
        }

        if (!isset($options['height'])) {
            $options['height'] = self::DEFAULT_HEIGHT;
        }

        if (!isset($options['accept_request'])) {
            $options['accept_request'] = self::DEFAULT_ACCEPT_REQUEST;
        }

        if (!isset($options['index']) && $options['accept_request'] !== 'application/pdf') {
            $options['index'] = 0;
        }

        if (!isset($options['zpl'])) {
            $this->mockException('ZPL label code is required!', 'POST');
        }

        return $options;
    }

    /**
     * @param array $options
     * @return string
     */
    public function getLabelPath(array $options): string
    {
        $path = 'printers/' . $options['dpmm'] . '/labels/' . $options['width'] . 'x' . $options['height'] . '/';
        if (isset($options['index']) && (int)$options['index'] >= 0) {
            $path .= $options['index'] . '/';
        }

        return $path;
    }
}
