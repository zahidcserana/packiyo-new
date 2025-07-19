<?php

namespace App\JsonApi\PublicV1;

use App\JsonApi\FrontendV1\ShippingBoxes\ShippingBoxSchema;
use App\JsonApi\PublicV1\ContactInformations\ContactInformationSchema;
use App\JsonApi\PublicV1\Customers\CustomerSchema;
use App\JsonApi\PublicV1\CustomerSettings\CustomerSettingSchema;
use App\JsonApi\PublicV1\ExternalCarrierCredentials\ExternalCarrierCredentialSchema;
use App\JsonApi\PublicV1\KitItems\KitItemSchema;
use App\JsonApi\PublicV1\Links\LinkSchema;
use App\JsonApi\PublicV1\LocationProducts\LocationProductSchema;
use App\JsonApi\PublicV1\Locations\LocationSchema;
use App\JsonApi\PublicV1\LocationTypes\LocationTypeSchema;
use App\JsonApi\PublicV1\Lots\LotSchema;
use App\JsonApi\PublicV1\OrderChannels\OrderChannelSchema;
use App\JsonApi\PublicV1\OrderItems\OrderItemSchema;
use App\JsonApi\PublicV1\Orders\OrderSchema;
use App\JsonApi\PublicV1\PackageOrderItems\PackageOrderItemSchema;
use App\JsonApi\PublicV1\Packages\PackageSchema;
use App\JsonApi\PublicV1\ProductBarcodes\ProductBarcodeSchema;
use App\JsonApi\PublicV1\ProductImages\ProductImageSchema;
use App\JsonApi\PublicV1\Products\ProductSchema;
use App\JsonApi\PublicV1\PurchaseOrderItems\PurchaseOrderItemSchema;
use App\JsonApi\PublicV1\PurchaseOrders\PurchaseOrderSchema;
use App\JsonApi\PublicV1\ReturnItems\ReturnItemSchema;
use App\JsonApi\PublicV1\Returns\ReturnSchema;
use App\JsonApi\PublicV1\ShipmentItems\ShipmentItemSchema;
use App\JsonApi\PublicV1\ShipmentLabels\ShipmentLabelSchema;
use App\JsonApi\PublicV1\Shipments\ShipmentSchema;
use App\JsonApi\PublicV1\ShipmentTrackings\ShipmentTrackingSchema;
use App\JsonApi\PublicV1\ShippingCarriers\ShippingCarrierSchema;
use App\JsonApi\PublicV1\ShippingMethods\ShippingMethodSchema;
use App\JsonApi\PublicV1\ToteOrderItems\ToteOrderItemSchema;
use App\JsonApi\PublicV1\Totes\ToteSchema;
use App\JsonApi\PublicV1\Users\UserSchema;
use App\JsonApi\PublicV1\Warehouses\WarehouseSchema;
use App\JsonApi\PublicV1\Webhooks\WebhookSchema;
use App\Models\Automation;
use App\Models\Automations\OrderAutomation;
use App\Models\ExternalCarrierCredential;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Scopes\PublicApiCustomerScope;
use App\Models\Scopes\PublicApiOrderChannelScope;
use App\Models\Tote;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    public const DEFAULT_PAGE_SIZE = 100;
    public const MAX_PAGE_SIZE = 500;

    /**
     * The base URI namespace for this server.
     *
     * @var string
     */
    protected string $baseUri = '/api/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     *
     * @return void
     */
    public function serving(): void
    {
        ExternalCarrierCredential::addGlobalScope(new PublicApiCustomerScope());
        Location::addGlobalScope(new PublicApiCustomerScope());
        Order::addGlobalScope(new PublicApiCustomerScope());
        Order::addGlobalScope(new PublicApiOrderChannelScope());
        Product::addGlobalScope(new PublicApiCustomerScope());
        PurchaseOrder::addGlobalScope(new PublicApiCustomerScope());
        Tote::addGlobalScope(new PublicApiCustomerScope());
        Automation::addGlobalScope(new PublicApiCustomerScope());
        OrderAutomation::addGlobalScope(new PublicApiCustomerScope());
    }

    /**
     * Get the server's list of schemas.
     *
     * @return array
     */
    protected function allSchemas(): array
    {
        return [
            ContactInformationSchema::class,
            CustomerSchema::class,
            ExternalCarrierCredentialSchema::class,
            KitItemSchema::class,
            LocationProductSchema::class,
            LocationSchema::class,
            OrderChannelSchema::class,
            OrderItemSchema::class,
            OrderSchema::class,
            ProductImageSchema::class,
            ProductSchema::class,
            ProductBarcodeSchema::class,
            ProductImageSchema::class,
            PurchaseOrderItemSchema::class,
            PurchaseOrderSchema::class,
            ReturnItemSchema::class,
            ReturnSchema::class,
            ShipmentItemSchema::class,
            ShipmentLabelSchema::class,
            ShipmentSchema::class,
            ShipmentTrackingSchema::class,
            UserSchema::class,
            WebhookSchema::class,
            PurchaseOrderSchema::class,
            PurchaseOrderItemSchema::class,
            WarehouseSchema::class,
            ToteOrderItemSchema::class,
            ToteSchema::class,
            UserSchema::class,
            WarehouseSchema::class,
            WebhookSchema::class,
            ShippingMethodSchema::class,
            ShippingCarrierSchema::class,
            LinkSchema::class,
            ShippingBoxSchema::class,
            LocationTypeSchema::class,
            PackageSchema::class,
            PackageOrderItemSchema::class,
            LotSchema::class,
            CustomerSettingSchema::class
        ];
    }
}
