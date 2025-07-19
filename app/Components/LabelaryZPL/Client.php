<?php

namespace App\Components\LabelaryZPL;

use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

class Client
{
    public const API_ENDPOINT = 'http://api.labelary.com/v1/';

    /** @var BaseClient $httpClient */
    private BaseClient $httpClient;

    /** @var Endpoint\Printers $events */
    public Endpoint\Printers $printers;

    /**
     * Client constructor.
     */
    public function __construct()
    {
        $this->setDefaultClient();
        $this->printers = new Endpoint\Printers($this);
    }

    /**
     * Set default client
     */
    private function setDefaultClient(): void
    {
        $this->httpClient = new BaseClient();
    }

    /**
     * Sets GuzzleHttp client.
     * @param BaseClient $client
     */
    public function setClient(BaseClient $client): void
    {
        $this->httpClient = $client;
    }

    /**
     * Sends POST request
     * @param string $endpoint
     * @param string $zpl
     * @param array $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function post(string $endpoint, string $zpl, array $headers = [])
    {
        return $this->httpClient->request('POST', self::API_ENDPOINT.$endpoint, [
            'headers' => $headers,
            'body' => $zpl,
        ]);
    }

    /**
     * @param array $options
     * @return array
     */
    public function requestHeaders(array $options): array
    {
        $headers = [
            'Accept' => $options['accept_request'],
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        if (isset($options['rotate'])) {
            $headers['X-Rotation'] = (int)$options['rotate'];
        }

        return $headers;
    }

    /**
     * @param ResponseInterface $response
     * @param bool $encoded
     * @return array
     */
    public function handleResponse(ResponseInterface $response, bool $encoded = false): array
    {
        $stream = Utils::streamFor($response->getBody());

        return [
            'type' => $response->getHeaders()['Content-Type'][0],
            'label' => $encoded ? base64_encode($stream->getContents()) : $stream->getContents(),
        ];
    }

    /**
     * Default body content will be in PDF format
     * @param ResponseInterface $response
     * @param bool $encoded
     * @return string
     */
    public function handleContentResponse(ResponseInterface $response, bool $encoded = false): string
    {
        $stream = Utils::streamFor($response->getBody());

        return $encoded ? base64_encode($stream->getContents()) : $stream->getContents();
    }
}
