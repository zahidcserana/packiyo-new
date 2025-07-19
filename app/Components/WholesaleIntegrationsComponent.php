<?php

namespace App\Components;

use App\Components\Wholesale\EDIProviders\Crstl\RequestBuilder;
use App\Exceptions\WholesaleException;
use App\Interfaces\RequestBuilderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Support\Facades\App;
use JsonException;
use stdClass;

class WholesaleIntegrationsComponent extends BaseComponent
{
    public function buildRequest(): RequestBuilderInterface
    {
        return new RequestBuilder(fn ($request, $options) => $this->send($request, $options));
    }

    public function send(Request $request, array $options): Response
    {
        $client = App::make(Client::class);

        try {
            return $client->send($request, $options);
        } catch (ServerException $exception) {
            throw new WholesaleException(
                message: $exception->getMessage() . "\nResponse:\n" . $exception->getResponse()->getBody(),
                previous: $exception
            );
        } catch (RequestException $exception) {
            if ($exception->hasResponse()) {
                throw $exception;
            } else {
                // Unsuccessful request - non-HTTP error.
                throw new WholesaleException(
                    message: 'Connection error: ' . $exception->getMessage(),
                    previous: $exception
                );
            }
        }
    }

    public function decodeBody(Stream $body): stdClass
    {
        try {
            return json_decode($body->getContents());
        } catch (JsonException $exception) {
            throw new WholesaleException(
                message: 'Could not decode JSON: ' . $exception->getMessage() . "\nContents:\n" . $body->getContents(),
                previous: $exception
            );
        }
    }
}
