<?php

namespace App\Components;

use App\Exceptions\WholesaleException;
use App\Models\EDI\Providers\CrstlASN;
use App\Models\EDI\Providers\CrstlEDIProvider;
use App\Models\EDI\Providers\CrstlPackingLabel;
use App\Models\EDIProvider;
use App\Models\Order;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\Shipment;
use App\Models\User;
use Closure;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WholesaleComponent extends BaseComponent
{
    protected const MAX_RETRIES = 2;

    public function __construct(protected WholesaleIntegrationsComponent $integrations)
    {
    }

    public function getProviderForOrder(Order $order): ?EDIProvider
    {
        return CrstlEDIProvider::where([
            'customer_id' => $order->customer_id,
            'active' => true
        ])->first(); // TODO: Select the one connected to the order.
    }

    public function createPackingLabels(EDIProvider $ediProvider, Order $order, int $try = 0, Shipment ...$shipments): CrstlASN
    {
        try {
            $response = $this->integrations->buildRequest()
                ->setCredentials($ediProvider)
                ->addRequestIdPart($order->number, 'order_number')
                ->createPackingLabels($order, ...$shipments)
                ->send();
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === Response::HTTP_UNAUTHORIZED && $try <= static::MAX_RETRIES) {
                return $this->attemptRefresh($ediProvider, fn () => $this->createPackingLabels($ediProvider, $order, ++$try, ...$shipments));
            }

            throw new WholesaleException(
                'Could not create Crstl packing labels: ' . $exception->getResponse()->getBody()->getContents()
            );
        }

        $data = $this->integrations->decodeBody($response->getBody())->data;

        if (property_exists($data, 'messages')) {
            Log::warning('Non-critical error from the Crstl API: ' . implode('; ', $data->messages));
        }

        $asn = new CrstlASN([
            'external_shipment_id' => $data->shipment_id,
            'request_labels_after_ms' => $data->request_labels_after_ms ?? null,
            'shipping_labels_status' => $data->shipping_labels_status,
            'asn_status' => $data->asn_status
        ]);
        $asn->order()->associate($order);
        $asn->shipment()->associate($shipments[0]); // TODO first shipment?
        $asn->save();

        return $asn;
    }

    public function getPackingLabels(EDIProvider $ediProvider, CrstlASN $asn, int $try = 0): CrstlASN
    {
        if (!$asn->areLabelsSupported()) {
            Log::warning('Packing labels not required by this EDI trading partner.');
            return $asn;
        }

        try {
            $response = $this->integrations->buildRequest()
                ->setCredentials($ediProvider)
                ->getPackingLabels($asn->external_shipment_id)
                ->send();
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === Response::HTTP_UNAUTHORIZED && $try <= static::MAX_RETRIES) {
                return $this->attemptRefresh($ediProvider, fn () => $this->getPackingLabels($ediProvider, $asn, ++$try));
            }

            throw new WholesaleException(
                'Could not get Crstl packing labels: ' . $exception->getResponse()->getBody()->getContents()
            );
        }

        $dataForLabels = $this->integrations->decodeBody($response->getBody())->data;
        $packingLabels = [];

        foreach ($dataForLabels as $data) {
            $file = $this->downloadFile($data->signed_url);

            $packingLabels[] = new CrstlPackingLabel([
                'label_type' => $data->label_type,
                'signed_url' => $data->signed_url,
                'signed_url_expires_at' => $data->expires,
                'content' => $file ? base64_encode($response->getBody()->getContents()) : null
            ]);
        }

        $asn->packingLabels()->saveMany($packingLabels);

        return $asn;
    }

    private function downloadFile(string $url, int $try = 0): ?\Illuminate\Http\Client\Response
    {
        try {
            $response = Http::get($url);
            $response->throw();
            return $response;
        } catch (RequestException $exception) {
            if ($try <= 0) {
                return $this->downloadFile($url, ++$try);
            }
            Log::warning('Could not download file from URL: ' . $url . ' Status code: ' . $exception->getCode() . ', Message: ' . $exception->getMessage());
            return null;
        }
    }

    protected function attemptRefresh(EDIProvider $ediProvider, Closure $callback)
    {
        try {
            $response = $this->integrations->buildRequest()
                ->setCredentials($ediProvider)
                ->refreshToken()
                ->send();
        } catch (ClientException $exception) {
            throw new WholesaleException(
                'Could not refresh the Crstl API token: ' . $exception->getResponse()->getBody()->getContents()
            );
        }

        $data = $this->integrations->decodeBody($response->getBody());
        $ediProvider->access_token = $data->access_token;
        $ediProvider->refresh_token = $data->refresh_token;
        $ediProvider->access_token_expires_at = $data->access_token_expires_at;
        $ediProvider->save();

        return $callback();
    }

    public function printPackingLabels(CrstlASN $asn, Printer $printer, ?User $user = null): void
    {
        $user ??= auth()->user(); // Default to authenticated user.

        foreach ($asn->packingLabels as $packingLabel) {
            if ($packingLabel->signed_url_expires_at->isPast()) {
                throw new WholesaleException("Cannot print GS1-128 label for order {$asn->order->number}, its URL expired.");
            }

            PrintJob::create([
                'object_type' => CrstlPackingLabel::class,
                'object_id' => $packingLabel->id,
                'url' => $packingLabel->signed_url,
                'printer_id' => $printer->id,
                'user_id' => $user->id
            ]);
        }
    }
}
