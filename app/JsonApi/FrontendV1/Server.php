<?php

namespace App\JsonApi\FrontendV1;

use App\JsonApi\FrontendV1\AutomatableEvents\AutomatableEventSchema;
use App\JsonApi\FrontendV1\AutomatableOperations\AutomatableOperationSchema;
use App\JsonApi\FrontendV1\AutomationActionTypes\AutomationActionTypeSchema;
use App\JsonApi\FrontendV1\Automations\Actions\AddGiftNoteActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\AddLineItemActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\AddPackingNoteActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\AddTagsActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\AutomationActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\CancelOrderActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\ChargeAdHocRateActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\MarkAsFulfilledActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetAllocationHoldActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetDateFieldActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetDeliveryConfirmationActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetFlagActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetFraudHoldActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetIncotermsActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetOperatorHoldActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetPackingDimensionsActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetPaymentHoldActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetPriorityActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetShippingBoxActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetShippingMethodActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetTextFieldActionSchema;
use App\JsonApi\FrontendV1\Automations\Actions\SetWarehouseActionSchema;
use App\JsonApi\FrontendV1\Automations\AutomationSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\ShippingOptionConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\ShipToCustomerNameConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderNumberConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\SalesChannelConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\ShipToCountryConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\ShipToStateConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\SubtotalOrderAmountConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\QuantityDistinctSkuConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\QuantityOrderItemsConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\TotalOrderAmountConditionSchema;
use App\JsonApi\FrontendV1\Automations\OrderAutomationSchema;
use App\JsonApi\FrontendV1\Automations\PurchaseOrderAutomationSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\AutomationConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderFlagFieldConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderIsManualConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderItemsTagsConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderLineItemsConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderLineItemConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderNumberFieldConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderTagsConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderTextFieldConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderTextPatternConditionSchema;
use App\JsonApi\FrontendV1\Automations\Conditions\OrderWeightConditionSchema;
use App\JsonApi\FrontendV1\AutomationConditionTypes\AutomationConditionTypeSchema;
use App\JsonApi\FrontendV1\ContactInformations\ContactInformationSchema;
use App\JsonApi\FrontendV1\Customers\CustomerSchema;
use App\JsonApi\FrontendV1\CustomerSettings\CustomerSettingSchema;
use App\JsonApi\FrontendV1\CustomerUsers\CustomerUserSchema;
use App\JsonApi\FrontendV1\EasypostCredentials\EasypostCredentialSchema;
use App\JsonApi\FrontendV1\Images\ImageSchema;
use App\JsonApi\FrontendV1\RateCards\RateCardSchema;
use App\JsonApi\FrontendV1\OrderChannels\OrderChannelSchema;
use App\JsonApi\FrontendV1\PersonalAccessTokens\PersonalAccessTokenSchema;
use App\JsonApi\FrontendV1\Printers\PrinterSchema;
use App\JsonApi\FrontendV1\ShippingBoxes\ShippingBoxSchema;
use App\JsonApi\FrontendV1\ShippingMethods\ShippingMethodSchema;
use App\JsonApi\FrontendV1\ShippingCarriers\ShippingCarrierSchema;
use App\JsonApi\FrontendV1\Users\UserSchema;
use App\JsonApi\FrontendV1\UserSettings\UserSettingSchema;
use App\JsonApi\FrontendV1\WebshipperCredentials\WebshipperCredentialSchema;
use App\JsonApi\PublicV1\Products\ProductSchema;
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
    protected string $baseUri = '/jsonapi/frontendv1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     *
     * @return void
     */
    public function serving(): void
    {
        // no-op
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
            WebshipperCredentialSchema::class,
            EasypostCredentialSchema::class,
            OrderChannelSchema::class,
            UserSchema::class,
            UserSettingSchema::class,
            CustomerSchema::class,
            CustomerSettingSchema::class,
            PersonalAccessTokenSchema::class,
            ImageSchema::class,
            PrinterSchema::class,
            RateCardSchema::class,
            ShippingBoxSchema::class,
            ShippingCarrierSchema::class,
            ShippingMethodSchema::class,
            CustomerUserSchema::class,
            AutomatableOperationSchema::class,
            AutomatableEventSchema::class,
            AutomationConditionTypeSchema::class,
            OrderTextFieldConditionSchema::class,
            OrderFlagFieldConditionSchema::class,
            OrderItemsTagsConditionSchema::class,
            OrderLineItemsConditionSchema::class,
            OrderLineItemConditionSchema::class,
            OrderNumberFieldConditionSchema::class,
            OrderTagsConditionSchema::class,
            OrderTextPatternConditionSchema::class,
            OrderWeightConditionSchema::class,
            OrderIsManualConditionSchema::class,
            AutomationActionTypeSchema::class,
            SetShippingBoxActionSchema::class,
            SetShippingMethodActionSchema::class,
            SetTextFieldActionSchema::class,
            AutomationSchema::class, // Needed even though it isn't used for rendering.
            OrderAutomationSchema::class,
            PurchaseOrderAutomationSchema::class,
            AutomationConditionSchema::class,
            AutomationActionSchema::class,
            AddLineItemActionSchema::class,
            AddPackingNoteActionSchema::class,
            AddTagsActionSchema::class,
            CancelOrderActionSchema::class,
            MarkAsFulfilledActionSchema::class,
            SetDateFieldActionSchema::class,
            SetDeliveryConfirmationActionSchema::class,
            SetFlagActionSchema::class,
            SetPackingDimensionsActionSchema::class,
            AutomationConditionSchema::class,
            SetWarehouseActionSchema::class,
            AutomationConditionSchema::class,
            ShipToCustomerNameConditionSchema::class,
            OrderNumberConditionSchema::class,
            SalesChannelConditionSchema::class,
            ShipToCountryConditionSchema::class,
            ShipToStateConditionSchema::class,
            QuantityDistinctSkuConditionSchema::class,
            QuantityOrderItemsConditionSchema::class,
            ShippingOptionConditionSchema::class,
            SubtotalOrderAmountConditionSchema::class,
            TotalOrderAmountConditionSchema::class,
            SetPriorityActionSchema::class,
            SetOperatorHoldActionSchema::class,
            SetAllocationHoldActionSchema::class,
            SetIncotermsActionSchema::class,
            SetFraudHoldActionSchema::class,
            SetPaymentHoldActionSchema::class,
            AddGiftNoteActionSchema::class,
            ChargeAdHocRateActionSchema::class,
            ProductSchema::class
        ];
    }
}
