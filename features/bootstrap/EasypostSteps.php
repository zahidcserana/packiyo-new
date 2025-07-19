<?php

use App\Components\Shipping\Providers\EasypostShippingProvider;
use App\Components\WholesaleComponent;
use App\Components\WholesaleIntegrationsComponent;
use App\Events\OrderShippedEvent;
use App\Http\Requests\Order\StoreRequest as OrderStoreRequest;
use App\Models\Customer;
use App\Models\EDI\Providers\CrstlASN;
use App\Models\EDI\Providers\CrstlEDIProvider;
use App\Models\EDI\Providers\CrstlPackingLabel;
use App\Models\EDIProvider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageOrderItem;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShippingBox;
use App\Models\ShippingMethod;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Behat steps to test customers.
 */
trait EasypostSteps
{
    private ?MockHandler $mockHandler = null;
    private ?array $rates = null;
    private ?array $cheapestRates = null;

    /**
     * @When the order :orderNumber shipping address is
     * @throws Exception
     */
    public function theOrderShippingAddressIs(string $orderNumber, TableNode $shippingAddress): void
    {
        $this->order = Order::where('number', $orderNumber)->firstOrFail();
        $baseComponent = App::make(\App\Components\BaseComponent::class);
        $shippingAddress = $shippingAddress->getHash()[0];

        if (is_null($shippingAddress)) {
            throw new Exception('Invalid JSON');
        }

        $address = $baseComponent->createContactInformation($shippingAddress, $this->order);
        $this->order->shippingContactInformation()->associate($address);
        $this->order->save();
        $this->order->refresh();
    }

    /**
     * @When I want to inspect the request for available shipping rates request made to Easypost
     */
    public function iWantToInspectAvailableShippingRatesRequestMadeToEasypost(): void
    {
        $order = $this->order;
        $input = $this->packingRequestData;

        $input['packing_state'] = json_encode($input['packing_state']);

        $params = [
            'carrier_service' => 'easypost',
            'credentials' => $order->customer->easypostCredentials,
        ];

        $this->mockHandler = new MockHandler([
            new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{}'),
            new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{}'),
        ]);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        App::bind(Client::class, fn() => $client);

        (new EasypostShippingProvider)->getShippingRates($order, $input, $params);
    }

    /**
     * @When I request the shipping rates for the order
     */
    public function iRequestTheShippingRatesForTheOrder(): void
    {
        $order = $this->order;
        $input = $this->packingRequestData;

        $input['packing_state'] = json_encode($input['packing_state']);

        $params = [
            'carrier_service' => 'easypost',
            'credentials' => $order->customer->easypostCredentials,
        ];

        $result = static::record(fn () => (new EasypostShippingProvider)->getShippingRates($order, $input, $params));
        $this->rates = $result;
    }

    /**
     * @When I request the cheapest shipping rates for the order
     */
    public function iRequestTheCheapestShippingRatesForTheOrder(): void
    {
        $order = $this->order;
        $input = $this->packingRequestData;

        $input['packing_state'] = json_encode($input['packing_state']);

        $params = [
            'carrier_service' => 'easypost',
            'credentials' => $order->customer->easypostCredentials,
        ];

        $result = static::record(fn () => (new EasypostShippingProvider)->getCheapestShippingRates($order, $input, $params));
        $this->cheapestRates = $result;
    }

    /**
     * @Then the request's parcel weight should be :weight oz
     */
    public function theSentParcelWeightShouldBe(string $weight): void
    {
        $labelsRequestContent = json_decode($this->mockHandler->getLastRequest()->getBody()->getContents(), true);
        $this->assertEquals((float) $weight, $labelsRequestContent['shipment']['parcel']['weight']);
    }

    /**
     * @Then the request's from address name should be :name
     */
    public function theSentSenderNameShouldBe(string $name): void
    {
        $labelsRequestContent = json_decode($this->mockHandler->getLastRequest()->getBody()->getContents(), true);
        $this->assertEquals($name, $labelsRequestContent['shipment']['from_address']['name']);
    }


    /**
     * @Then the cheapest found option should be the same as the cheapest option available among the rates
     */
    public function theCheapestFoundOptionShouldBeTheSameAsTheCheapestOptionAvailableAmongTheRates(): void
    {
        // Find the cheapest option in the $rates array
        $cheapestRate = $this->getCheapestRate($this->rates);



        $this->assertEquals($cheapestRate, $this->cheapestRates['cheapest']);
    }

    private function getCheapestRate(array $rates): array
    {
        $cheapestRate = [];

        foreach ($rates as $carrier => $rate) {
            foreach ($rate as $service) {
                if (isset($service['service']) && (empty($cheapestRate) || $cheapestRate['rate'] > $service['rate'])) {
                    $cheapestRate = [
                        'carrier' => $carrier,
                        'service' => $service['service'],
                        'rate' => Arr::get($service, 'rate'),
                        'currency' => $service['currency'],
                        'delivery_days' => $service['delivery_days'],
                        'shipping_method_id' => $service['shipping_method_id']
                    ];
                }
            }
        }

        return $cheapestRate;
    }

}
