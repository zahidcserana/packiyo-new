<?php

namespace App\Providers;

use App\Models\BulkInvoiceBatch;
use App\Models\BulkShipBatch;
use App\Models\ExternalCarrierCredential;
use App\Models\ContactInformation;
use App\Models\CustomerSetting;
use App\Models\Image;
use App\Models\Invoice;
use App\Models\BillingRate;
use App\Models\Link;
use App\Models\RateCard;
use App\Models\Customer;
use App\Models\EasypostCredential;
use App\Models\InventoryLog;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Lot;
use App\Models\Order;
use App\Models\AddressBook;
use App\Models\OrderChannel;
use App\Models\OrderStatus;
use App\Models\PickingCart;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderStatus;
use App\Models\Return_;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Tote;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserSetting;
use App\Models\Warehouse;
use App\Models\Webhook;
use App\Models\WebshipperCredential;
use App\Policies\BulkInvoiceBatchPolicy;
use App\Policies\ImagePolicy;
use App\Policies\BulkShipBatchPolicy;
use App\Policies\ContactInformationPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\CustomerSettingPolicy;
use App\Policies\EasypostCredentialPolicy;
use App\Policies\ExternalCarrierCredentialPolicy;
use App\Policies\InventoryLogPolicy;
use App\Policies\LinkPolicy;
use App\Policies\LocationPolicy;
use App\Policies\LocationTypesPolicy;
use App\Policies\OrderChannelPolicy;
use App\Policies\OrderPolicy;
use App\Policies\AddressBookPolicy;
use App\Policies\OrderStatusPolicy;
use App\Policies\PickingCartPolicy;
use App\Policies\PrinterPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseOrderStatusPolicy;
use App\Policies\ReturnsPolicy;
use App\Policies\ShippingCarrierPolicy;
use App\Policies\ShippingMethodPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskTypePolicy;
use App\Policies\TotePolicy;
use App\Policies\LotPolicy;
use App\Policies\UserPolicy;
use App\Policies\UserRolePolicy;
use App\Policies\UserSettingPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\WebhookPolicy;
use App\Policies\WebshipperCredentialPolicy;
use App\Policies\BillingRatePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\RateCardPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        UserRole::class => UserRolePolicy::class,
        TaskType::class => TaskTypePolicy::class,
        Task::class => TaskPolicy::class,
        Supplier::class => SupplierPolicy::class,
        OrderChannel::class => OrderChannelPolicy::class,
        Order::class => OrderPolicy::class,
        AddressBook::class => AddressBookPolicy::class,
        Printer::class => PrinterPolicy::class,
        PrintJob::class => PrinterPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        Return_::class => ReturnsPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        Location::class => LocationPolicy::class,
        Product::class => ProductPolicy::class,
        Webhook::class => WebhookPolicy::class,
        InventoryLog::class => InventoryLogPolicy::class,
        Customer::class => CustomerPolicy::class,
        OrderStatus::class => OrderStatusPolicy::class,
        PurchaseOrderStatus::class => PurchaseOrderStatusPolicy::class,
        WebshipperCredential::class => WebshipperCredentialPolicy::class,
        EasypostCredential::class => EasypostCredentialPolicy::class,
        Tote::class => TotePolicy::class,
        Lot::class => LotPolicy::class,
        PickingCart::class => PickingCartPolicy::class,
        LocationType::class => LocationTypesPolicy::class,
        ShippingMethod::class => ShippingMethodPolicy::class,
        BulkShipBatch::class => BulkShipBatchPolicy::class,
        ShippingCarrier::class => ShippingCarrierPolicy::class,
        RateCard::class => RateCardPolicy::class,
        BillingRate::class => BillingRatePolicy::class,
        Invoice::class => InvoicePolicy::class,
        ExternalCarrierCredential::class => ExternalCarrierCredentialPolicy::class,
        UserSetting::class => UserSettingPolicy::class,
        CustomerSetting::class => CustomerSettingPolicy::class,
        ContactInformation::class => ContactInformationPolicy::class,
        Image::class => ImagePolicy::class,
        Link::class => LinkPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
