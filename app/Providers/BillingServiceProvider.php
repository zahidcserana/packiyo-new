<?php

namespace App\Providers;

use App\Components\BillableOperationService;
use App\Components\BillingRates\Charges\StorageByLocation\LocationTypesCache;
use App\Components\BillingRates\Charges\StorageByLocation\StorageByLocationChargeComponent;
use App\Components\BillingRates\Processors\PackagingBillingRateCacheProcessor;
use App\Components\BillingRates\Processors\PickingBillingRateCacheProcessor;
use App\Components\BillingRates\Processors\ShippingBillingRateCacheProcessor;
use App\Components\BillingRates\RequestValidator\BillingRequestValidator;
use App\Components\InventoryLogComponent;
use App\Components\BillingRates\PackagingRateBillingRateComponent;
use App\Components\MailComponent;
use App\Components\BillingRates\StorageByLocationRate\MongoDbConnectionTester;
use App\Components\FulfillmentBillingCalculatorService;
use App\Components\Invoice\InvoiceProcessor;
use App\Components\Invoice\MongoInvoiceGenerator;
use App\Components\Invoice\MongoInvoiceSummaryGenerator;
use App\Components\Invoice\Strategies\InvoiceLegacyStrategy;
use App\Components\Invoice\Strategies\InvoiceMongoStrategy;
use App\Components\PurchaseOrderBillingCacheComponent;
use App\Components\RateCardComponent;
use App\Components\BillingRateComponent;
use App\Components\BillingRates\ShipmentsByPickingBillingRateComponentV2;
use App\Components\BillingRates\ShipmentsByShippingLabelBillingRateComponent;
use App\Components\BillingRates\StorageByLocationBillingRateComponent;
use App\Components\InvoiceComponent;
use App\Components\InvoiceExportComponent;
use App\Components\ReceivingBillingCalculatorComponent;
use App\Components\ShipmentBillingCacheService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('rateCard', function () {
            return new RateCardComponent();
        });
        $this->app->singleton('billingRate', function () {
            return new BillingRateComponent();
        });
        $this->app->singleton('invoiceExport', function () {
            return new InvoiceExportComponent();
        });
        $this->app->singleton('storage_by_location', function () {
            return new StorageByLocationBillingRateComponent();
        });
        $this->app->singleton('shipments_by_picking_rate_v2', function () {
            return new ShipmentsByPickingBillingRateComponentV2();
        });
        $this->app->singleton('shipments_by_shipping_label', function() {
            return new ShipmentsByShippingLabelBillingRateComponent();
        });

        $this->app->singleton(PackagingRateBillingRateComponent::class);

        $this->app->singleton(MailComponent::class);

        $this->app->singleton('invoice', function (Application $app) {
            return new InvoiceComponent(
                $app->make('invoiceExport'),
                $app->make(MailComponent::class),
                $app->make('storage_by_location'),
                $app->make('shipments_by_picking_rate_v2'),
                $app->make('shipments_by_shipping_label'),
                $app->make(PackagingRateBillingRateComponent::class)
            );
        });

        $this->app->singleton(BillingRequestValidator::class);
        $this->app->singleton(ShippingBillingRateCacheProcessor::class);
        $this->app->singleton(ShipmentBillingCacheService::class);
        $this->app->singleton(PurchaseOrderBillingCacheComponent::class);
        $this->app->singleton(ReceivingBillingCalculatorComponent::class);
        $this->app->singleton(StorageByLocationChargeComponent::class);
        $this->app->singleton(PickingBillingRateCacheProcessor::class);
        $this->app->singleton(PackagingBillingRateCacheProcessor::class);
        $this->app->singleton(FulfillmentBillingCalculatorService::class);

        $this->app->singleton(BillableOperationService::class);
        $this->app->singleton(InvoiceProcessor::class, function (Application $app) {
            return new InvoiceProcessor(
                new InvoiceLegacyStrategy(
                    $app->make('invoiceExport'),
                    $app->make('storage_by_location'),
                    $app->make('shipments_by_picking_rate_v2'),
                    $app->make('shipments_by_shipping_label'),
                    $app->make(PackagingRateBillingRateComponent::class)
                ),
                new InvoiceMongoStrategy,
                $app->make(MongoDbConnectionTester::class),
                $app->make(BillableOperationService::class),
                $app->make(InventoryLogComponent::class)
            );
        });
        $this->app->singleton(MongoInvoiceGenerator::class);
        $this->app->singleton(MongoInvoiceSummaryGenerator::class);

        $this->app->singleton(LocationTypesCache::class, LocationTypesCache::class);
    }

    public function provides()
    {
        return [
            'rateCard',
            'billingRate',
            'invoice',
            'invoiceExport'
        ];
    }
}
