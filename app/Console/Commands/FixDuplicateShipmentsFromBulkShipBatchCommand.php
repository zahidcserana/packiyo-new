<?php

namespace App\Console\Commands;

use App\Components\InventoryLogComponent;
use App\Http\Controllers\PackingController;
use App\Models\BulkShipBatch;
use App\Models\PackageOrderItem;
use App\Models\Shipment;
use Illuminate\Console\Command;

class FixDuplicateShipmentsFromBulkShipBatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bulk-ship-batch:fix-duplicates {bulk-ship-batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Voids duplicate labels, reverts inventory and re-merges bulk ship batch PDF';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $bulkShipBatch = BulkShipBatch::find($this->argument('bulk-ship-batch'));

        $bulkShipBatchOrders = $bulkShipBatch->orders;
        $shipmentIds = $bulkShipBatchOrders->pluck('pivot.shipment_id');

        foreach ($bulkShipBatchOrders as $order) {
            $this->line('Checking order ' . $order->number);

            if ($order->orderItems()->sum('quantity_reshipped')) {
                $this->line('Order was reshipped - skipping');
                continue;
            }

            /**
             * @var $shipment Shipment
             */
            foreach ($order->shipments as $shipment) {
                $this->line('Checking shipment ' . $shipment->shipmentTrackings->pluck('tracking_number')->first());

                if ($shipmentIds->contains($shipment->id)) {
                    $this->line('Correct shipment - skipping');
                } else if ($shipment->voided_at) {
                    $this->line('Already voided - skipping');
                } else {
                    if ($shipment->shippingMethod->shippingCarrier->carrier_service == 'easypost') {
                        try {
                            $this->line('Trying to void on EasyPost');

                            app('easypostShipping')->void($shipment);
                        } catch (\Exception $exception) {
                            $this->line($exception->getMessage());
                            $shipment->voided_at = now();
                            $shipment->save();
                        }
                    }

                    foreach ($shipment->packages as $package) {
                        /**
                         * @var $packageOrderItem PackageOrderItem
                         */
                        foreach ($package->packageOrderItems as $packageOrderItem) {
                            $product = $packageOrderItem->orderItem->product;
                            $location = $packageOrderItem->location;
                            $quantity = $packageOrderItem->quantity;

                            $this->line('Putting back ' . $quantity . ' of ' . $product->sku . ' to ' . $location->name);

                            app('inventoryLog')->adjustInventory(
                                $location,
                                $product,
                                $quantity,
                                InventoryLogComponent::OPERATION_TYPE_RESHIP
                            );
                        }
                    }
                }
            }
        }

        $packingController = new PackingController(app('packing'));

        $labels = $packingController->getBulkShipPDF($bulkShipBatch->refresh());

        $this->table(['url', 'name'], $labels);
    }
}
