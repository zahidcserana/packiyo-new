<?php

namespace App\Components;

use App\Exceptions\ShippingException;
use App\Features\RequiredReadyToPickForPacking;
use App\Jobs\Order\RecalculateReadyToShipOrders;
use App\Models\BulkShipBatch;
use App\Models\CustomerSetting;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackageDocument;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShipmentLabel;
use App\Models\ShippingBox;
use App\Models\Tote;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use PDF;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\Tcpdf\Fpdi;

class PackingComponent extends BaseComponent
{
    /**
     * @throws ShippingException
     */
    public function packAndShip(Order $order, $storeRequest)
    {
        if ($order->fulfilled_at || $order->cancelled_at || $order->archived_at) {
            return null;
        }

        $input = $storeRequest->all();

        if (Arr::exists($input, 'shipping_contact_information')) {
            $order->shippingContactInformation->update(
                Arr::get($input, 'shipping_contact_information')
            );
        }

        if (! $order->ready_to_ship) {
            throw new ShippingException(__('Order is not ready to ship.'));
        }

        if (! $this->allItemsPacked($order, json_decode($input['packing_state'], true))) {
            throw new ShippingException(__('Some items were reallocated. Please refresh and pack again.'));
        }

        $shipments = app(ShippingComponent::class)->ship($order, $storeRequest);

        if ($shipments) {
            $printerId = Arr::get($input, 'printer_id');

            foreach ($shipments as $shipment) {
                if ($printerId) {
                    foreach ($shipment->shipmentLabels as $shipmentLabel) {
                        PrintJob::create([
                            'object_type' => ShipmentLabel::class,
                            'object_id' => $shipmentLabel->id,
                            'url' => route('shipment.label', [
                                'shipment' => $shipment,
                                'shipmentLabel' => $shipmentLabel,
                            ]),
                            'type' => $shipmentLabel->document_type,
                            'printer_id' => $printerId,
                            'user_id' => auth()->user()->id,
                        ]);
                    }
                }

                $printPackingSlip = Arr::get($input, 'print_packing_slip');

                if ($printPackingSlip) {
                    $defaultSlipPrinter = app('printer')->getDefaultSlipPrinter($order->customer);

                    if ($defaultSlipPrinter) {
                        PrintJob::create([
                            'object_type' => Shipment::class,
                            'object_id' => $shipment->id,
                            'url' => route('shipment.getPackingSlip', [
                                'shipment' => $shipment,
                            ]),
                            'printer_id' => $defaultSlipPrinter->id,
                            'user_id' => auth()->user()->id
                        ]);

                        if ($order->custom_invoice_url) {
                            PrintJob::create([
                                'object_type' => Shipment::class,
                                'object_id' => $shipment->id,
                                'url' => $order->custom_invoice_url,
                                'printer_id' => $defaultSlipPrinter->id,
                                'user_id' => auth()->user()->id
                            ]);
                        }
                    }
                }

                if ($printerId) {
                    $this->printShipmentPackageDocuments($shipment, $printerId);
                }

                app('shipment')->triggerShipmentStoreWebhook($shipment);
            }

            $order->updateQuietly([
                'ready_to_ship' => 0,
                'ready_to_pick' => 0
            ]);

            dispatch(new RecalculateReadyToShipOrders([$order->id]));
        }

        return $shipments;
    }

    private function allItemsPacked(Order $order, array $packingState): bool
    {
        // Get all order's packable items
        $mustBePackedOrderItems = $order->orderItems
            // We need to filter out the kits, virtual product, cancelled items because these items cannot be packed.
            // We can filter out quantity_allocated 0 because we already checked if the order is ready to ship,
            // meaning that if order doesn't allow partial and not all items are allocated,
            // the order wouldn't be ready to ship, and this method will not be called.
            ->filter(function (OrderItem $orderItem) {
                return $orderItem->product && ! $orderItem->product->isKit() && $orderItem->product->type == Product::PRODUCT_TYPE_REGULAR && ! $orderItem->cancelled_at && ! $orderItem->quantity_allocated <= 0;
            })
            ->mapWithKeys(function ($orderItem) {
                return [$orderItem->id => $orderItem];
            });

        // $packingState is an array of boxes, each box has an array of items, and each item has a orderItem key with the orderItem id
        // We have to count the amount of same orderItems packed
        $packedItemsCount = [];
        foreach ($packingState as $box) {
            foreach ($box['items'] as $item) {
                $orderItemId = $item['orderItem'];

                $startedCount = isset($packedItemsCount[$orderItemId]);
                if (!$startedCount) {
                    // Add the orderItem to the packedItemsCount array
                    $packedItemsCount[$orderItemId] = 0;
                }

                $packedItemsCount[$orderItemId]++;
            }
        }

        // Check if all order items are packed
        // We need to check if the amount of packed items is the same as the quantity of the orderItem
        foreach ($mustBePackedOrderItems as $mustBePackedOrderItem) {
            if (!isset($packedItemsCount[$mustBePackedOrderItem->id]) || $packedItemsCount[$mustBePackedOrderItem->id] < $mustBePackedOrderItem->quantity_allocated) {
                return false;
            }
        }

        return true; // All items are packed
    }

    /**
     * @param $barcode
     * @return mixed
     */
    public function barcodeSearch($barcode): mixed
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();
        $orderId = null;

        if (str_starts_with($barcode, 'scanid')) {
            $orderId = str_replace('scanid', '', $barcode);
        }

        $order = Order::whereIn('customer_id', $customerIds)
            ->when($orderId, function ($q) use ($orderId) {
                return $q->where('id', $orderId);
            })
            ->unless($orderId, function ($q) use ($barcode) {
                return $q->where('number', ltrim($barcode));
            })
            ->when(Feature::for('instance')->active(RequiredReadyToPickForPacking::class), function ($query) {
                $query->where('ready_to_pick', 1);
            })
            ->where('ready_to_ship', 1)
            ->first();

        if (!$order) {
            $tote = Tote::with('placedToteOrderItems.orderItem.order')
                ->join('warehouses', 'totes.warehouse_id', '=', 'warehouses.id')
                ->whereIn('warehouses.customer_id', $customerIds)
                ->where('barcode', $barcode)
                ->select('totes.*')
                ->first();

            if ($tote && !empty($tote->placedToteOrderItems)) {
                $order = $tote->placedToteOrderItems->first()->orderItem->order ?? null;
            }
        }

        if ($order) {
            return $order;
        }

        return null;
    }

    public function bulkShipBatchProgress(BulkShipBatch $bulkShipBatch, $limit): array
    {
        Log::info('bulkShipBatchProgress start');
        $orders = $bulkShipBatch->orders
            ->map->pivot
            ->keyBy->order_id
            ->map(function($order) {
                $order->status = $this->getBulkShipOrderStatus($order);

                return $order;
            });
        Log::info('bulkShipBatchProgress got orders');

        $limit = min($limit, $orders->count());

        $unprocessedOrdersCount = $orders->whereNull('finished_at')
            ->whereNull('status_message')
            ->count();
        $totalShippedOrdersCount = $orders->whereNotNull('shipment_id')->count();
        $shippedOrdersCount = $totalShippedOrdersCount - $bulkShipBatch->orders_shipped;
        $notShippedOrdersCount = $orders->count() - $totalShippedOrdersCount;

        Log::info('bulkShipBatchProgress counted');

        $response = [
            'processed' => false,
            'message' => __('Orders shipments are still being processed!'),
            'orders' => $orders,
            'statistics' => [
                'total' => $orders->count(),
                'total_shipped' => $totalShippedOrdersCount,
                'requested' => $limit,
                'failed' => $orders->where('status', 'Failed')->count(),
                'shipped' => $shippedOrdersCount,
                'remaining' => $orders->count() - $totalShippedOrdersCount,
            ],
        ];

        // PROCESSING
        if ($unprocessedOrdersCount > 0 && $shippedOrdersCount < $limit) {
            return $response;
        }

        $bulkShipBatch->update([
            'orders_shipped' => $totalShippedOrdersCount
        ]);

        Log::info('bulkShipBatchProgress updated');

        // PROCESSED
        $response['processed'] = true;

        if ($totalShippedOrdersCount > 0) {
            if ($notShippedOrdersCount === 0) {
                // Shipped
                $bulkShipBatch->update([
                    'in_progress' => false,
                    'shipped_at' => now(),
                ]);

                $bulkShipBatch->unlockOrders();

                $response['message'] = __('Batch was shipped successfully!');
                $response['labels'] = $this->getBulkShipPDF($bulkShipBatch);
            } else {
                // Shipped partially
                $response['limit_reached'] = true;
                $response['message'] = __('Batch shipped partially!');

                return $response;
            }
        } else {
            // Failed completely
            $response['message'] = __('Batch shipment failed!');
        }

        return $response;
    }

    private function getBulkShipOrderStatus($order)
    {
        if ($order->shipment_id) {
            return 'Shipped';
        }

        if ($order->status_message === __('Limit was reached')) {
            return 'Skip';
        }

        if ($order->status_message) {
            return 'Failed';
        }

        return 'On queue';
    }

    /**
     * @throws PdfParserException
     */
    public function getBulkShipPDF(BulkShipBatch $bulkShipBatch): array
    {
        // load only those package order items from the batch shipments
        // also load the shipments
        $bulkShipBatch = $bulkShipBatch->refresh()->loadMissing([
            'bulkShipBatchOrders.order',
            'bulkShipBatchOrders.shipment.packages.packageOrderItems.orderItem',
            'bulkShipBatchOrders.shipment.shipmentLabels',
        ]);

        if (count($bulkShipBatch->bulkShipBatchOrders) == 0) {
            return [];
        }

        $labelDirectory = 'public/bulk_ships/';
        $pdfName = sprintf("%d", $bulkShipBatch->id) . '_bulk_ship.pdf';

        if (!Storage::exists($labelDirectory)) {
            Storage::makeDirectory($labelDirectory);
        }

        $summaryPagePath = $labelDirectory . $pdfName;

        $outputLabels = [
            [
                'url' => asset(Storage::url($summaryPagePath)),
                'name' => 'Bulk label'
            ]
        ];

        $labelWidth = paper_width($bulkShipBatch->bulkShipBatchOrders->first()->order->customer_id, 'label');
        $labelHeight = paper_height($bulkShipBatch->bulkShipBatchOrders->first()->order->customer_id, 'label');

        PDF::loadView('bulk_shipping.pdf', [
            'ordersShipped' => $bulkShipBatch->ordersShippedAmount(),
            'bulkShipBatch' => $bulkShipBatch,
            'barcodeRows' => $this->getBarcodeRows($bulkShipBatch),
        ])->setPaper([0, 0, $labelWidth, $labelHeight])
            ->save(Storage::path($summaryPagePath));

        $fpdi = new Fpdi('P', 'pt', array($labelWidth, $labelHeight));
        $fpdi->setPrintHeader(false);
        $fpdi->setPrintFooter(false);

        $this->addLabelToBulkLabel($fpdi, Storage::path($summaryPagePath));

        $labelIndex = 0;

        // in case pivot table has duplicates we'll only merge the first shipment
        $shipmentsToMerge = [];

        foreach ($bulkShipBatch->bulkShipBatchOrders as $key => $bulkShipBatchOrder) {
            Log::channel('bulkshipping')->info('Adding label for order ' . $key . '/' . count($bulkShipBatch->bulkShipBatchOrders));

            $shipment = $bulkShipBatchOrder->shipment;

            if ($shipment) {
                if (in_array($shipment->id, $shipmentsToMerge)) {
                    continue;
                }

                if ($shipment->voided_at) {
                    $bulkShipBatchOrder->update([
                        'labels_merged' => true
                    ]);
                } else {
                    foreach ($shipment->shipmentLabels ?? [] as $shipmentLabel) {
                        $shippingLabelContent = $shipmentLabel->content;

                        if ($shippingLabelContent) {
                            $shippingLabelContent = base64_decode($shippingLabelContent);
                        } else if (!$shippingLabelContent && $shipmentLabel->url) {
                            $shippingLabelContent = file_get_contents($shipmentLabel->url);
                        }

                        if ($shippingLabelContent) {
                            $labelPath = Storage::path($labelDirectory . "label_{$labelIndex}_{$pdfName}");
                            file_put_contents($labelPath, $shippingLabelContent);

                            $labelIndex++;

                            if ($this->addLabelToBulkLabel($fpdi, $labelPath)) {
                                $bulkShipBatchOrder->update([
                                    'labels_merged' => true
                                ]);

                                if (customer_settings($bulkShipBatchOrder->order->customer->id, CustomerSetting::CUSTOMER_SETTING_PACKING_SLIP_IN_BULKSHIPPING)) {
                                    app('shipment')->generatePackingSlip($bulkShipBatchOrder->shipment);

                                    if ($bulkShipBatchOrder->shipment->packing_slip) {
                                        $this->addPackingSlipToBulkLabel($fpdi, Storage::path($bulkShipBatchOrder->shipment->packing_slip), $labelWidth, $labelHeight);
                                    }
                                }
                            } else {
                                $outputLabels[] = [
                                    'url' => route('shipment.label', [
                                        'shipment' => $shipment,
                                        'shipmentLabel' => $shipmentLabel
                                    ]),
                                    'name' => 'Single label for order ' . $bulkShipBatchOrder->order->number
                                ];
                            }
                        }
                    }
                }

                $shipmentsToMerge[] = $shipment->id;
            }
        }

        $this->addLabelToBulkLabel($fpdi, Storage::path($summaryPagePath));

        $fpdi->Output(Storage::path($summaryPagePath), 'F');

        $bulkShipBatch->update(['label' => $summaryPagePath]);

        return $outputLabels;
    }

    public function getBarcodeRows(BulkShipBatch $bulkShipBatch): array
    {
        $packageItems = $bulkShipBatch->bulkShipBatchOrders->pluck('shipment.packages.*.packageOrderItems')->flatten();

        $barcodeRows['ids'] = $packageItems->groupBy('location_id')
            ->map(function($locationPackageItems) {
                return $locationPackageItems->map(function($packageItem) {
                    return [
                        'product_id' => $packageItem->orderItem->product_id,
                        'quantity' => $packageItem->quantity,
                        'location_id' => $packageItem->location_id,
                    ];
                })->sortBy('product_id')
                    ->groupBy('product_id')
                    ->map(function($products) {
                        return $products->sum('quantity');
                    });
            })->toArray();

        $locations = array_keys($barcodeRows['ids']);
        $products = array_merge(...array_map(static function($products) {
            return array_keys($products);
        }, $barcodeRows['ids']));

        $barcodeRows['names'] = [
            'locations' => Location::whereIn('id', $locations)->get(['name', 'barcode', 'id'])->keyBy('id')->toArray(),
            'products' => Product::whereIn('id', $products)->get(['name', 'barcode', 'id'])->keyBy('id')->toArray(),
        ];

        $barcodeRows['box'] = ShippingBox::where('id', $packageItems->first()->package->shipping_box_id)
            ->selectRaw('id, name, CONCAT(length, " x ", width, " x ", height) AS size')
            ->first()
            ->toArray();

        return $barcodeRows;
    }

    /**
     * Get the sender warehouse from:
     * - Location of a packed item
     * - If it's not packed (but being returned) - location of items that were packed before
     * - If none return warehouse - first warehouse of a customer (or their 3PL)
     *
     * @param Order $order
     * @param array $package
     * @return Warehouse
     */
    public function getSenderWarehouse(Order $order, array $package): Warehouse
    {
        $items = Arr::get($package, 'items');

        foreach ($items as $item) {
            if ($locationId = Arr::get($item, 'location')) {
                return Location::find($locationId)->warehouse;
            } elseif ($orderItemId = Arr::get($item, 'orderItem')) {
                $warehouse = OrderItem::find($orderItemId)->packageOrderItems->last()->location->warehouse ?? null;

                if ($warehouse) {
                    return $warehouse;
                }
            }

        }

        return $order->customer->parent_id ? $order->customer->parent->warehouses->first() : $order->customer->warehouses->first();
    }

    private function addLabelToBulkLabel(Fpdi $fpdi, $labelPath): bool
    {
        $success = true;

        try {
            $pageCount = $fpdi->setSourceFile($labelPath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $fpdi->AddPage();
                $tplId = $fpdi->importPage($i, PageBoundaries::ART_BOX);
                $size = $fpdi->getTemplateSize($tplId);
                $fpdi->useTemplate($tplId, $size);
            }
        } catch (Exception $exception) {
            Log::error('[bulkship] Failed to merge label ' . $labelPath, [$exception->getMessage()]);
            $success = false;
        }

        return $success;
    }

    /**
     * @param Fpdi $fpdi
     * @param $packingSlipPath
     * @param $labelWidth
     * @param $labelHeight
     * @return void
     * @throws PdfParserException
     */
    private function addPackingSlipToBulkLabel(Fpdi $fpdi, $packingSlipPath, $labelWidth, $labelHeight): void
    {
        $pageCount = $fpdi->setSourceFile($packingSlipPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            try {
                $fpdi->AddPage();
                $tplId = $fpdi->importPage($i, PageBoundaries::ART_BOX);
                $size = $fpdi->getTemplateSize($tplId);
                $size['height'] = $labelHeight;
                $size['width'] = $labelWidth;
                $size['adjustPageSize'] = true;
                $fpdi->useTemplate($tplId, $size);
            } catch (Exception $exception) {
                Log::error('[bulkship] Failed to merge label ' . $packingSlipPath, [$exception->getMessage()]);
            }
        }
    }

    private function printShipmentPackageDocuments(Shipment $shipment, int $printerId): void
    {
        foreach ($shipment->packages as $package) {
            foreach ($package->documents as $packageDocument) {
                if ($packageDocument->print_with_label) {
                    PrintJob::create([
                        'object_type' => PackageDocument::class,
                        'object_id' => $packageDocument->id,
                        'url' => route('shipment.package_document', [
                            'shipment' => $shipment,
                            'packageDocument' => $packageDocument,
                        ]),
                        'type' => $packageDocument->document_type,
                        'printer_id' => $printerId,
                        'user_id' => auth()->user()->id,
                    ]);
                }
            }
        }
    }
}
