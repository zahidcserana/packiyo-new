<?php

namespace App\Components;

use App\Components\LabelaryZPL\Client as LabelaryZPLClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ZplConverterComponent extends BaseComponent
{
    /**
     * Convert ZPL with variety of formats such as PNG or PDF (default: PDF)
     * @param string $zplCode
     * @param array $options
     * @param bool $encoded
     * @param int $retries
     * @param int $delay
     * @return string|null
     * @throws GuzzleException
     */
    public function convert(string $zplCode,
                            array $options = [],
                            bool $encoded = false,
                            int $retries = 3,
                            int $delay = 60): ?string
    {
        $client = new LabelaryZPLClient();

        $options['zpl'] = $zplCode;
        $options = $client->printers->labels($options);
        $path = $client->printers->getLabelPath($options);

        $headers = $client->requestHeaders($options);

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = $client->post($path, $options['zpl'], $headers);
                if ($response->getStatusCode() === 200) {
                    // By default content response will be PDF format
                    return $client->handleContentResponse($response, $encoded);
                } elseif ($response->getStatusCode() === 429) {
                    // Handle rate limit (429) with exponential backoff
                    $sleepTime = $delay * pow(2, $attempt - 1); // Exponential backoff
                    sleep($sleepTime);
                } else {
                    Log::error('[ZPL converter] invalid ' . $response->getBody());
                    return null;
                }
            } catch (GuzzleException $exception) {
                Log::error('[ZPL converter] exception thrown ' . $exception->getMessage());
                return null;
            }
        }

        return null;
    }
}
