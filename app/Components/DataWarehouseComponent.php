<?php

namespace App\Components;

use GuzzleHttp\{Client, Exception\GuzzleException};
use Illuminate\Support\Facades\Log;

class DataWarehouseComponent extends BaseComponent
{
    const IMPORT_BATCH_URL_SUFFIX = '/v2/import/batch';

    /**
     * @param $data
     * @return bool
     */
    public function pushBatch($data): bool
    {
        Log::info('[DataWarehouse] push batch', [
            $data
        ]);

        $apiUrl = config('stitch.api_url') . self::IMPORT_BATCH_URL_SUFFIX;
        $apiToken = config('stitch.api_token');

        $client = new Client([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiToken,
            ],
        ]);

        try {
            Log::debug($apiUrl);

            $response = $client->request(
                'POST',
                $apiUrl,
                [
                    'body' => json_encode($data)
                ]
            );

            $body = json_decode($response->getBody()->getContents() ?? null, true);

            Log::info('[DataWarehouse] response', [$body]);

            return true;
        } catch (GuzzleException | \Exception $e) {
            Log::log('error', '[DataWarehouse] exception thrown', [$e->getMessage()]);

            return false;
        }
    }
}
