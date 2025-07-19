<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageOrderItem;
use App\Models\Shipment;
use App\Models\ShipmentLabel;
use App\Models\ShipmentTracking;
use App\Models\User;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    public function definition()
    {
        $processingStatuses = [
            Shipment::PROCESSING_STATUS_PENDING,
            Shipment::PROCESSING_STATUS_IN_PROGRESS,
            Shipment::PROCESSING_STATUS_SUCCESS,
            Shipment::PROCESSING_STATUS_FAILED
        ];

        return [
            'order_id' => fn () => Order::factory()->create()->id,
            'shipping_method_id' => fn() => ShippingMethod::factory()->create()->id,
            'user_id' => fn () => User::factory()->create()->id,
            'processing_status' => $this->faker->randomElement($processingStatuses),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Shipment &$shipment) {
            if ($shipment->packages->count() === 0) {
                // Assume single package shipment.
                $package = Package::factory()->for($shipment)->create([
                    'order_id' => $shipment->order_id
                ]);

                foreach ($shipment->order->orderItems as $orderItem) {
                    PackageOrderItem::factory()->for($package)->create([
                        'order_item_id' => $orderItem->id,
                        'quantity' => $orderItem->quantity,
                        'location_id' => null,
                        'tote_id' => null,
                        'lot_id' => null
                    ]);
                }

                $shipment->refresh();
            }

            if ($shipment->shipmentLabels->count() === 0) {
                // Assume single label shipment.
                ShipmentLabel::factory()->for($shipment)->create([
                    'type' => ShipmentLabel::TYPE_SHIPPING
                ]);
                $shipment->refresh();
            }

            if ($shipment->shipmentTrackings->count() === 0) {
                // Assume single label shipment.
                ShipmentTracking::factory()->for($shipment)->create([
                    'tracking_number' => 'TN-' . $shipment->order->number,
                    'tracking_url' => $this->faker->url,
                    'type' => ShipmentTracking::TYPE_SHIPPING
                ]);
                $shipment->refresh();
            }
        });
    }

    public function withPackages(array $packages)
    {
        return $this->afterCreating(function (Shipment $shipment) use ($packages) {
            $shipment->packages()->delete();
            $shipment->packages()->saveMany($packages);
            $shipment->refresh();
        });
    }
}
