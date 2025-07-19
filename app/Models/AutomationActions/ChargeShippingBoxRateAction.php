<?php

namespace App\Models\AutomationActions;

use App\Events\OrderShippedEvent;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToLineItems;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\DebitsBillingCharges;
use App\Models\BillingCharges\ShippingBoxCharge;
use App\Models\ShippingBox;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeShippingBoxRateAction extends AutomationAction
    implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany, DebitsBillingCharges;

    protected $fillable = [
        'applies_to',
        'amount'
    ];

    protected $casts = [
        'applies_to' => AppliesToLineItems::class
    ];

    public function shippingBox(): BelongsTo
    {
        return $this->belongsTo(ShippingBox::class, 'shipping_box_id'); // Although it doesn't belong to it.
    }

    public static function getSupportedEvents(): array
    {
        return [OrderShippedEvent::class];
    }

    public static function loadForCommand(): array
    {
        return ['shippingBox'];
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();
        $quantity = 0;

        foreach ($order->shipments as $shipment) {
            foreach ($shipment->packages as $package) {
                if ($this->applies_to == AppliesToLineItems::ALL || (
                    $this->applies_to == AppliesToLineItems::SOME
                    && !is_null($package->shippingBox)
                    && $package->shippingBox->id == $this->shippingBox->id
                )) {
                    ++$quantity;
                }
            }
        }

        $amount = $this->amount * $quantity;
        $description = $this->applies_to == AppliesToLineItems::ALL
            ? __('Packaging for order :order_number', ['order_number' => $order->number])
            : __('Order :order_number shipped using box :box_name', [
                'order_number' => $order->number, 'box_name' => $this->shippingBox->name
            ]);
        $charge = new ShippingBoxCharge([
            // TODO: Figure out descriptions.
            'description' => $description,
            'quantity' => $quantity,
            'amount' => $amount
        ]);
        $charge->automation()->associate($this->automation);
        $charge->shipment()->associate($shipment);

        // TODO: Why does the shipment not have a warehouse?
        $this->debitCharge($shipment->customer->parent->warehouses->first(), $order->customer, $charge);
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Charge Shipping Box';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
