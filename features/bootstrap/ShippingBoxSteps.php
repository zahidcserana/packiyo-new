<?php

use App\Http\Requests\ShippingBox\StoreRequest as ShippingBoxStoreRequest;
use App\Models\Customer;
use App\Models\ShippingBox;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

trait ShippingBoxSteps
{
    public Exception|null $error;

    /**
     * @When customer :customerName send a request to create a shipping box with these values
     */
    public function customerSendARequestToCreateAShippingBoxWithTheseValues($customerName, TableNode $table): void
    {
        $customer = $this->getCustomerByName($customerName);
        foreach ($table->getRows() as $index => $box) {
            if ($index == 0) {
                continue; //header
            }
            [$name, $length, $width, $height, $cost] = $box;

            try {

                $request = ShippingBoxStoreRequest::make([
                    'customer_id' => $customer->id,
                    'name' => $name,
                    'length' => $length,
                    'weight' => '1',
                    'width' => $width,
                    'height' => $height,
                    'cost' => empty($cost) ? null : $cost,
                    'height_locked' => "0",
                    'length_locked' => "0",
                    'width_locked' => "0",
                ]);
                app('shippingBox')->store($request);
            } catch (Exception $e) {
                Log::debug($e->getMessage());
                $this->error = $e;
            }
        }
    }

    /**
     * @Then a shipping box with name :boxName for customer :customerName and cost :cost should be created
     */
    public function aShippingBoxWithNameXXBrownBoxForCustomerAndNoShouldBe($boxName, $customerName, $cost)
    {
        $customer = $this->getCustomerByName($customerName);
        $box = ShippingBox::where(['name' => $boxName, 'customer_id' => $customer->id])->firstOrFail();
        $this->assertEquals($cost, $box->cost);
    }

    /**
     * @Then a shipping box with name :boxName for customer :customerName and with no cost should be created
     */
    public function aShippingBoxWithNameXXBrownBoxForCustomerAndNoCostShouldBeCreated($boxName, $customerName)
    {
        $customer = $this->getCustomerByName($customerName);
        $box = ShippingBox::where(['name' => $boxName, 'customer_id' => $customer->id])->firstOrFail();
        $this->assertTrue(is_null($box->cost));
    }

    /**
     * @Then shipping box error should occur
     */
    public function shippingBoxErrorShouldOccur()
    {
        $this->assertInstanceOf(Exception::class, $this->error);
    }

    /**
     * @Then shipping boxes with name :boxName for client :customerName is not created
     */
    public function shippingBoxesWithNameForClientIsNotCreated($boxName, $customerName)
    {
        $customer = $this->getCustomerByName($customerName);
        $box = ShippingBox::where(['name' => $boxName, 'customer_id' => $customer->id])->first() ?? null;
        $this->assertTrue(is_null($box));
    }

    public function getCustomerByName($customerName)
    {
        return Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
    }
}
