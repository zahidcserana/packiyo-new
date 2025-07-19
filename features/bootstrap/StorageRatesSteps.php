<?php

use App\Models\{BillingRate, CacheDocuments\StorageByLocationChargeCacheDocument, Customer, LocationType, RateCard, Warehouse};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Behat steps to test shipment rates.
 */
trait StorageRatesSteps
{
    private StorageByLocationChargeCacheDocument|null $chargeInScope = null;

    /**
     * @Given a storage by location rate :rateName on rate card :cardName
     */
    public function aStorageByLocationRateOnRateCard(string $rateName, string $cardName)
    {
        $settings = [
            'fee' => 1.50,
            'period' => 'day',
            'location_types' => json_encode([]),
        ];
        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        BillingRate::factory()->for($rateCard)->create([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName,
            'settings' => $settings
        ]);
    }

    /**
     * @Given a storage by location rate :rateName on rate card :cardName with fee :fee
     */
    public function aStorageByLocationRateOnRateCardWithFee(string $rateName, string $cardName, float $fee)
    {
        $settings = [
            'fee' => $fee,
            'period' => 'day',
            'location_types' => json_encode([]),
        ];
        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        BillingRate::factory()->for($rateCard)->create([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName,
            'settings' => $settings
        ]);
    }

    /**
     * @When a storage by location rate :rateName was updated at :date
     */
    public function aStorageByLocationRateWasUpdatedAt($rateName, $date)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName
        ])->firstOrFail();
        $rate->updated_at = (Carbon::parse($date));
        $rate->save();
    }

    /**
     * @When a storage by location rate :rateName was updated
     */
    public function aStorageByLocationRateWasUpdated($rateName)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName
        ])->firstOrFail();
        $rate->updated_at = (Carbon::now());
        $rate->save();
    }


    /**
     * @Given the storage by location rate :rateName applies to all location types
     */
    public function theStorageByLocationRateAppliesToAllLocationTypes(string $rateName)
    {
        $locationTypeIds = LocationType::all()->pluck('id');
        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['location_types'] = json_encode($locationTypeIds->toArray());
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the storage by location rate :rateName applies with fee :fee
     */
    public function theStorageByLocationRateAppliesWithFee(string $rateName, string $fee)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the storage by location rate :rateName applies to :locationTypeName
     */
    public function theStorageByLocationRateAppliesTo(string $rateName, string $locationTypeName)
    {
        $locationType = LocationType::where('name', $locationTypeName)->firstOrFail();
        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['location_types'] = json_encode(
            array_merge(
                json_decode($settings['location_types'], true),
                [$locationType->id]
            )
        );
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the storage by location rate :rateName invoices by :timeUnit
     */
    public function theStorageByLocationRateInvoicesBy(string $rateName, string $timeUnit)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['period'] = $timeUnit;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Then the client :clientName should have a storage by location charge that bills from :fromDate to :toDate for the warehouse :warehouseName, billing rate :billingRateName and location type :locationTypeName
     */
    public function theClientShouldHaveAStorageByLocationChargeForTheWarehouseBillingRateAndLocationType(
        string $clientName,
        string $fromDate,
        string $toDate,
        string $warehouseName,
        string $billingRateName,
        string $locationTypeName,
    ): void {
        $client = Customer::query()
            ->whereHas(
                'contactInformation',
                fn(Builder $query) => $query->where('name', $clientName)
            )
            ->firstOrFail();
        $warehouse = Warehouse::query()
            ->whereHas(
                'contactInformation',
                fn(Builder $query) => $query->where('name', $warehouseName)
            )
            ->firstOrFail();

        $billingRate = BillingRate::where('name', $billingRateName)->firstOrFail();

        $charge = StorageByLocationChargeCacheDocument::query()
            ->where('customer_id', $client->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('billing_rate_id', $billingRate->id)
            ->where('location_type_id', LocationType::where('name', $locationTypeName)->firstOrFail()->id)
            ->period(Carbon::createFromFormat('Y-m-d H:i:s',$fromDate), Carbon::createFromFormat('Y-m-d H:i:s',$toDate))
            ->firstOrFail();

        $this->assertNotNull($charge);
        $this->chargeInScope = $charge;
    }

    /**
     * @Then this charge should have a quantity of :quantity and total charge of :totalCharge
     */
    public function thisChargeShouldHaveQuantityOfAndTotalChargeOf(int $quantity, float $totalCharge): void
    {
        $this->assertNotNull($this->chargeInScope);
        $this->assertEquals($quantity, $this->chargeInScope->charge['quantity']);
        $this->assertEquals($totalCharge, $this->chargeInScope->charge['total_charge']);
    }

    /**
     * @Then this charge's description should be :description
     */
    public function thisChargeShouldHaveDescription(string $description): void
    {
        $this->assertNotNull($this->chargeInScope);
        $this->assertEquals($description, $this->chargeInScope->description);
    }

    /**
     * @Given the storage by location rate :rateLabelName applies to generic locations
     */
    public function theStorageByLocationRateAppliesToGenericLocations($rateLabelName)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateLabelName
        ])->firstOrFail();

        $settings = $rate->settings; // Copy array.
        $settings['no_location'] = true;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the storage by location rate :rateLabelName applies to :locationTypeName location type
     */
    public function theStorageByLocationRateAppliesToLocationType($rateLabelName, $locationTypeName)
    {
        $locationType = LocationType::whereName($locationTypeName)->first();

        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateLabelName
        ])->firstOrFail();

        $settings = $rate->settings; // Copy array.
        $newLocationType = array_merge(json_decode($settings['location_types']), ["$locationType->id"]);
        $settings['location_types'] = json_encode($newLocationType);
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the storage by location rate :rateName is for non-location type
     */
    public function theStorageByLocationRateIsForNotLocationType($rateName)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::STORAGE_BY_LOCATION,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['no_location'] = true;
        $rate->settings = $settings;
        $rate->save();
    }
}
