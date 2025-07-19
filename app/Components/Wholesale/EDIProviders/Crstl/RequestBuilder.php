<?php

namespace App\Components\Wholesale\EDIProviders\Crstl;

use App\Interfaces\RequestBuilderInterface;
use App\Models\EDI\Providers\CrstlEDIProvider;
use App\Models\EDIProvider;
use App\Models\Order;
use App\Models\Shipment;
use Closure;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use LogicException;

class RequestBuilder implements RequestBuilderInterface
{
    protected ?CrstlEDIProvider $ediProvider;

    protected Closure $send;

    protected ?string $baseURI;

    private readonly string $CRSTL_API_BASE_URL;
    private readonly string $CRSTL_SANDBOX_API_BASE_URL;

    /**
     * This adds a header when testing that allows PHP-VCR to identify the request for replays.
     *
     * PHP-VCR (https://php-vcr.github.io) is a library used to record and replay HTTP requests.
     * In order to replay them, for instance, when testing, it needs to be able to identify them.
     * Even when interacting with external APIs (e.g. Crstl EDI) the HTTP body may include db IDs.
     * These db IDs will not reset between test runs, overstepping the replay mechanism.
     * This custom header lets us configure PHP-VCR to ignore HTTP request bodies entirely,
     * and instead allows us to define which (consistent) values to use to identify requests.
     */
    protected array $requestIdParts;

    protected ?Request $request;

    public function __construct(Closure $send)
    {
        $this->send = $send;
        $this->requestIdParts = [];

        $this->CRSTL_API_BASE_URL = config('crstl_edi.api_base_url');
        $this->CRSTL_SANDBOX_API_BASE_URL = config('crstl_edi.sandbox_api_base_url');
    }

    private function setBaseURI(string $baseURI): void
    {
        $this->baseURI = $baseURI;
    }

    public function setCredentials(EDIProvider $ediProvider): static
    {
        $this->ediProvider = $ediProvider;

        if ($this->ediProvider->is_sandbox) {
            $this->setBaseURI($this->CRSTL_SANDBOX_API_BASE_URL);
        } else {
            $this->setBaseURI($this->CRSTL_API_BASE_URL);
        }

        return $this;
    }

    public function login(string $email, string $password, bool $sandbox = false): static
    {
        $this->setBaseURI($sandbox ? $this->CRSTL_SANDBOX_API_BASE_URL : $this->CRSTL_API_BASE_URL);

        $this->request = new LoginRequest($email, $password);

        return $this;
    }

    public function refreshToken(): static
    {
        $this->checkIsEdiProviderSet();

        $this->request = new RefreshTokenRequest($this->ediProvider->refresh_token);

        return $this;
    }

    public function createPackingLabels(Order $order, Shipment ...$shipments): static
    {
        $this->checkIsEdiProviderSet();

        $this->request = new CreatePackingLabelsRequest($order, ...$shipments);

        return $this;
    }

    public function getPackingLabels(string $asnId): static
    {
        $this->checkIsEdiProviderSet();

        $this->request = new GetPackingLabelsRequest($asnId);

        return $this;
    }

    private function checkIsEdiProviderSet(): void
    {
        if (! $this->ediProvider) {
            throw new MissingEDIProviderException();
        }
    }

    public function addRequestIdPart(string $value, ?string $key = null): static
    {
        if (!empty($key)) {
            $value = $key . '=' . $value;
        }

        $this->requestIdParts []= $value;

        return $this;
    }

    public function send(): Response
    {
        if (!empty($this->requestIdParts)) {
            $this->request =  $this->request->withHeader('Packiyo-Id', implode('&', $this->requestIdParts));
        }

        if (!empty($this->ediProvider)) {
            $this->request =  $this->request->withHeader('Authorization', 'Bearer ' . $this->ediProvider->access_token);
        }

        return ($this->send)($this->request, ['base_uri' => $this->baseURI]);
    }
}

class LoginRequest extends Request
{
    protected const METHOD = 'POST';
    protected const PATH = '/v2/auth/token'; // The /v2 on the base URI is ignored by Guzzle.

    protected string $email;
    protected string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
        parent::__construct(static::METHOD, $this->composeURI(), $this->composeHeaders(), $this->composeBody());
    }

    public function composeURI(): string
    {
        return static::PATH;
    }

    public function composeHeaders(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    public function composeBody(): ?string
    {
        return json_encode([
            'email' => (string) $this->email,
            'password' => (string) $this->password
        ]);
    }
}

class RefreshTokenRequest extends Request
{
    protected const METHOD = 'POST';
    protected const PATH = '/v2/auth/refresh'; // The /v2 on the base URI is ignored by Guzzle.

    protected string $refreshToken;

    public function __construct(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;
        parent::__construct(static::METHOD, $this->composeURI(), $this->composeHeaders(), $this->composeBody());
    }

    public function composeURI(): string
    {
        return static::PATH;
    }

    public function composeHeaders(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    public function composeBody(): ?string
    {
        return json_encode([
            'refresh_token' => (string) $this->refreshToken
        ]);
    }
}

class CreatePackingLabelsRequest extends Request
{
    protected const METHOD = 'POST';
    protected const PATH = '/v2/shipment'; // The /v2 on the base URI is ignored by Guzzle.

    protected array $shipments;

    public function __construct(protected Order $order, Shipment ...$shipments)
    {
        $this->shipments = $shipments;

        parent::__construct(static::METHOD, $this->composeURI(), $this->composeHeaders(), $this->composeBody());
    }

    public function composeURI(): string
    {
        return static::PATH;
    }

    public function composeHeaders(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    public function composeBody(): string
    {
        $serializer = new PackingLabelsSerializer($this->order, ...$this->shipments);

        return json_encode($serializer->serialize());
    }
}

class GetPackingLabelsRequest extends Request
{
    protected const METHOD = 'GET';
    protected const PATH = '/v2/shipping-labels?asn_id=:asn_id:'; // The /v2 on the base URI is ignored by Guzzle.

    protected string $asnId;

    public function __construct(string $asnId)
    {
        $this->asnId = $asnId;
        parent::__construct(static::METHOD, $this->composeURI(), $this->composeHeaders(), $this->composeBody());
    }

    public function composeURI(): string
    {
        return render_small_template(static::PATH, ['asn_id' => $this->asnId]);
    }

    public function composeHeaders(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    public function composeBody(): ?string
    {
        return null;
    }
}

class MissingEDIProviderException extends LogicException
{
    public function __construct()
    {
        parent::__construct('An EDI provider is required to send this request');
    }
}
