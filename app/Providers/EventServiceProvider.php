<?php

namespace App\Providers;

use App\Events\OccupiedLocationsCalculatedEvent;
use App\Events\OrderAgedEvent;
use App\Events\OrderCreatedEvent;
use App\Events\OrderShippedEvent;
use App\Events\OrderUpdatedEvent;
use App\Events\PurchaseOrderClosedEvent;
use App\Events\PurchaseOrderCreatedEvent;
use App\Events\PurchaseOrderReceivedEvent;
use App\Listeners\AuditingListener;
use App\Listeners\AutomationListener;
use App\Listeners\BillingListener;
use App\Listeners\DataWarehouseListener;
use App\Listeners\WebhookCallEventSubscriber;
use App\Models\Customer;
use App\Models\CycleCountBatchItem;
use App\Models\EasypostCredential;
use App\Models\ExternalCarrierCredential;
use App\Models\KitItem;
use App\Models\PrintJob;
use App\Models\TribirdCredential;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\LotItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickingBatchItem;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\ShippingMethodMapping;
use App\Models\ToteOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WebshipperCredential;
use App\Observers\CustomerObserver;
use App\Observers\CycleCountBatchItemObserver;
use App\Observers\EasypostCredentialObserver;
use App\Observers\ExternalCarrierCredentialObserver;
use App\Observers\KitItemObserver;
use App\Observers\PrintJobObserver;
use App\Observers\TribirdCredentialObserver;
use App\Observers\LocationObserver;
use App\Observers\LocationTypeObserver;
use App\Observers\LotItemObserver;
use App\Observers\OrderItemObserver;
use App\Observers\OrderObserver;
use App\Observers\PickingBatchItemObserver;
use App\Observers\ProductObserver;
use App\Observers\PurchaseOrderItemObserver;
use App\Observers\ShippingMethodMappingObserver;
use App\Observers\ToteOrderItemObserver;
use App\Observers\UserObserver;
use App\Observers\WarehouseObserver;
use App\Observers\WebshipperCredentialObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use OwenIt\Auditing\Events\Auditing;
use App\Models\Lot;
use App\Observers\LotObserver;
use App\Listeners\WholesaleListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [SendEmailVerificationNotification::class],
        Auditing::class => [AuditingListener::class],
        OrderCreatedEvent::class => [AutomationListener::class],
        OrderUpdatedEvent::class => [AutomationListener::class],
        OrderAgedEvent::class => [AutomationListener::class],
        OrderShippedEvent::class => [AutomationListener::class, WholesaleListener::class, BillingListener::class, DataWarehouseListener::class],
        PurchaseOrderCreatedEvent::class => [AutomationListener::class],
        PurchaseOrderReceivedEvent::class => [AutomationListener::class],
        PurchaseOrderClosedEvent::class => [AutomationListener::class, BillingListener::class],
        'Illuminate\Auth\Events\Login' => ['App\Listeners\UserLoginAt'],
        'Illuminate\Auth\Events\Logout' => ['App\Listeners\UserLogoutListener'],
        OccupiedLocationsCalculatedEvent::class => [BillingListener::class],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        WebhookCallEventSubscriber::class
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        User::observe(UserObserver::class);
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
        OrderItem::observe(OrderItemObserver::class);
        PurchaseOrderItem::observe(PurchaseOrderItemObserver::class);
        Customer::observe(CustomerObserver::class);
        Warehouse::observe(WarehouseObserver::class);
        Location::observe(LocationObserver::class);
        KitItem::observe(KitItemObserver::class);
        LocationType::observe(LocationTypeObserver::class);
        LotItem::observe(LotItemObserver::class);
        Lot::observe(LotObserver::class);
        ShippingMethodMapping::observe(ShippingMethodMappingObserver::class);
        WebshipperCredential::observe(WebshipperCredentialObserver::class);
        EasypostCredential::observe(EasypostCredentialObserver::class);
        ExternalCarrierCredential::observe(ExternalCarrierCredentialObserver::class);
        TribirdCredential::observe(TribirdCredentialObserver::class);
        ToteOrderItem::observe(ToteOrderItemObserver::class);
        PickingBatchItem::observe(PickingBatchItemObserver::class);
        CycleCountBatchItem::observe(CycleCountBatchItemObserver::class);
        PrintJob::observe(PrintJobObserver::class);
    }
}
