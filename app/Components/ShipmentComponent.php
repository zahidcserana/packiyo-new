<?php

namespace App\Components;

use App\Http\Requests\Shipment\ShipmentTrackingRequest;
use App\Http\Requests\Shipment\ShipItemRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Currency;
use App\Models\CustomerSetting;
use App\Models\EDI\Providers\CrstlASN;
use App\Models\EDI\Providers\CrstlPackingLabel;
use App\Models\PackageDocument;
use App\Models\Product;
use App\Models\ShipmentTracking;
use App\Models\Location;
use App\Models\LocationProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentLabel;
use App\Models\Tote;
use App\Models\Webhook;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;

class ShipmentComponent extends BaseComponent
{
    public function shipItem(ShipItemRequest $request, OrderItem $orderItem, Shipment $shipment)
    {
        $input = $request->validated();
        $quantityShipped = Arr::get($input, 'quantity');

        $shipmentItem = ShipmentItem::firstOrCreate([
            'shipment_id' => $shipment->id,
            'order_item_id' => $orderItem->id,
        ]);

        $shipmentItem->increment('quantity', $quantityShipped);

        $location = Location::find($input['location_id']);
        $tote = Tote::find($input['tote_id']);

        if ($tote) {
            $quantityRemainingInTote = $quantityShipped;

            $toteOrderItems = $tote->placedToteOrderItems()
                ->where('order_item_id', $orderItem->id)
                ->where('location_id', $location->id)
                ->get();

            foreach ($toteOrderItems as $toteOrderItem) {
                $quantityRemoveFromTote = min($quantityRemainingInTote, $toteOrderItem->quantity_remaining);

                $toteOrderItem->update([
                    'quantity_removed' => DB::raw('`quantity_removed` + ' . $quantityRemoveFromTote),
                    'quantity_remaining' => DB::raw('`quantity_remaining` - ' . $quantityRemoveFromTote),
                ]);

                $quantityRemainingInTote -= $quantityRemoveFromTote;
            }
        }

        $orderItem->touch();

        if ($orderItem->parentOrderItem) {
            $componentQuantityForKit = $orderItem->componentQuantityForKit();
            $kitShippedQuantity = $componentQuantityForKit > 0 ? ceil($shipmentItem->orderItem->quantity_shipped / $componentQuantityForKit) : 0;

            $parentOrderItem = $orderItem->parentOrderItem;

            /**
             * explanation for future generations (and myself after two days):
             * this is used to calculate if the kit item should get ShipmentItem.
             * it works by going through all (non-cancelled) component items of a parent item and checking if the
             * shipped quantity is how the kit is configured. If at least one of the components is not shipped to
             * what the kit expects, the kit will not receive a ShipmentItem.
             *
             * kitOrderItems actual means component items. Gonna change that globally eventually.
             *
             */
            foreach ($parentOrderItem->kitOrderItems as $componentOrderItem) {
                $componentQuantityForKit = $componentOrderItem->cancelled_at ? 0 : $componentOrderItem->componentQuantityForKit();
                if ($componentQuantityForKit > 0) {
                    $kitShippedQuantity = min($kitShippedQuantity, ceil($componentOrderItem->quantity_shipped / $componentQuantityForKit));
                }
            }

            if ($kitShippedQuantity > $parentOrderItem->quantity_shipped) {
                $shipmentItemParent = ShipmentItem::firstOrCreate([
                    'shipment_id' => $shipment->id,
                    'order_item_id' => $orderItem->order_item_kit_id,
                ]);

                $shipmentItemParent->quantity = $kitShippedQuantity - ShipmentItem::where('id', '!=', $shipmentItemParent->id)->where('order_item_id', $orderItem->order_item_kit_id)->sum('quantity');
                $shipmentItemParent->save();

                $parentOrderItem->quantity_shipped = $kitShippedQuantity;
                $parentOrderItem->save();
            }
        }

        app('inventoryLog')->adjustInventory(
            $location,
            $orderItem->product,
            -$quantityShipped,
            InventoryLogComponent::OPERATION_TYPE_SHIP,
            $shipment
        );

        return $shipmentItem;
    }

    public function filterOrders(Request $request)
    {
        $term = $request->get('term');
        $results = [];

        if ($term) {
            $orders = Order::where('id', '=', $term)->get();

            if ($orders->count() == 0) {
                $orders= Order::where('number', 'like', $term . '%')->get(['id', 'number']);
            }

            foreach ($orders as $order) {
                if ($order->count()) {
                    $results[] = [
                        'id' => $order->id,
                        'text' => $order->number
                    ];
                }
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function filterOrderProducts(Request $request, $orderId): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if ($term) {
            $orderItems = OrderItem::where('order_id', $orderId)->where('id', $term)->get();

            if ($orderItems->count() == 0) {
                $orderItems = OrderItem::where('order_id', '=', $orderId)->whereHas('product', function ($query) use ($term) {
                    $term = $term . '%';

                    $query->where('name', 'like', $term);
                    $query->orWhere('sku', 'like', $term);
                })->get();
            }

            foreach ($orderItems as $orderItem) {
                if ($orderItem->count()) {
                    $results[] = [
                        'id' => $orderItem->id,
                        'text' => 'SKU: ' . $orderItem->product->sku . ', NAME:' . $orderItem->product->name
                    ];
                }
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function filterOrderProductLocation(Request $request, $orderItemId)
    {
        $productId = OrderItem::where('id', $orderItemId)->first()->product_id;
        $term = $request->get('term');
        $results = [];

        if ($term) {
            $locationProducts = LocationProduct::where('product_id', $productId)
                ->whereHas('location', function($query) use ($term) {
                    $term = $term . '%';

                    $query->where('name', 'like', $term);
                })
                ->get();

            foreach ($locationProducts as $locationProduct) {
                if ($locationProduct->location->count()) {
                    $results[] = [
                        'id' => $locationProduct->location->id,
                        'text' => $locationProduct->location->name
                    ];
                }
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param Shipment $shipment
     * @param ShipmentLabel $shipmentLabel
     * @return Application|ResponseFactory|RedirectResponse|Response|Redirector
     */
    public function label(Shipment $shipment, ShipmentLabel $shipmentLabel) {
        if ($shipmentLabel->shipment_id !== $shipment->id) {
            abort('403');
        }

        if ($shipmentLabel->content && $shipmentLabel->document_type != 'zpl') {
            $contentType = $shipmentLabel->document_type == 'image' ? 'data:image' : 'application/pdf';

            return response(base64_decode($shipmentLabel->content))->header('Content-Type', $contentType);
        } elseif ($shipmentLabel->content && $shipmentLabel->document_type == 'zpl') {
            $contents = $shipmentLabel->content;

            return response()->streamDownload(function () use ($contents) {
                echo $contents;
            }, $shipment->external_shipment_id . '_' . $shipmentLabel->id . '.zpl');
        }

        if ($shipmentLabel->url) {
            return redirect($shipmentLabel->url);
        }

        abort(404);
    }

    public function ediPackingLabel(Shipment $shipment, CrstlASN $asn, CrstlPackingLabel $packingLabel) {
        if ($packingLabel->asn_id !== $asn->id || $asn->shipment_id !== $shipment->id) {
            abort('403');
        }

        // Content might be empty because download could've failed. Or could be an old label that was never downloaded.
        if (! is_null($packingLabel->content)) {
            return response(base64_decode($packingLabel->content))
                ->header('Content-Type', 'application/pdf');
        } else {
            return response()->json([
                'url' => $packingLabel->signed_url,
                'expires_at' => $packingLabel->signed_url_expires_at->toIso8601String()
            ]);
        }
    }

    /**
     * Looks awfully similar to the label method. At some point we should refactor everything that can be printed to
     * a polymorphic printable object.
     *
     * @param Shipment $shipment
     * @param PackageDocument $packageDocument
     * @return Application|ResponseFactory|RedirectResponse|Response|Redirector|\Symfony\Component\HttpFoundation\StreamedResponse|void
     */
    public function packageDocument(Shipment $shipment, PackageDocument $packageDocument) {
        if ($packageDocument->package->shipment_id !== $shipment->id) {
            abort('403');
        }

        if ($packageDocument->content && $packageDocument->document_type != 'zpl') {
            $contentType = $packageDocument->document_type == 'image' ? 'data:image' : 'application/pdf';

            return response(base64_decode($packageDocument->content))->header('Content-Type', $contentType);
        } elseif ($packageDocument->content && $packageDocument->document_type == 'zpl') {
            $contents = $packageDocument->content;

            return response()->streamDownload(function () use ($contents) {
                echo $contents;
            }, $shipment->external_shipment_id . '_' . $packageDocument->id . '.zpl');
        }

        if ($packageDocument->url) {
            return redirect($packageDocument->url);
        }

        abort(404);
    }

    public function getPackingSlip(Shipment $shipment)
    {
        $locale = customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_LOCALE);
        if ($locale) {
            app()->setLocale($locale);
        }

        $this->generatePackingSlip($shipment);

        return response()->file(Storage::path($shipment->packing_slip), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function generatePackingSlip(Shipment $shipment)
    {
        $shipment->refresh();
        $shipment = $shipment->load('packages', 'contactInformation.country', 'order.currency');
        $items = $shipment->order
            ->orderItems()
            ->with(['shipmentItems', 'product', 'parentOrderItem'])
            ->get();

        $path = 'public/packing_slips';
        $pdfName = sprintf("%011d", $shipment->id) . '_packing_slip.pdf';

        if (! Storage::exists($path)) {
            Storage::makeDirectory($path);
        }

        $path .= '/' . $pdfName;

        $paperWidth = paper_width($shipment->order->customer_id, 'document');
        $paperHeight = paper_height($shipment->order->customer_id, 'document');
        $footerHeight = paper_height($shipment->order->customer_id, 'footer');

        PDF::loadView('packing_slip.document', [
            'shipment' => $shipment,
            'items' => $items,
            'footerHeight' => $footerHeight,
            'showPricesOnSlip' => customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_SHOW_PRICES_ON_SLIPS),
            'showSkusOnSlip' => customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_SHOW_SKUS_ON_SLIPS),
            'currency' => $shipment->order->currency->symbol ?? Currency::find(customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_CURRENCY))->symbol ?? ''
        ])
        ->setPaper([0, 0, $paperWidth, $paperHeight])
        ->save(Storage::path($path));

        $shipment->update(['packing_slip' => $path]);
    }

    public function updateTracking(ShipmentTrackingRequest $request, Shipment $shipment)
    {
        $input = $request->validated();

        $shipment->shipping_method_name = $input['shipping_method_name'];
        $shipment->update();

        Arr::forget($input, 'shipping_method_name');

        if ($input['id']) {
            $shipmentTracking = ShipmentTracking::find($input['id']);

            return $shipmentTracking->update($input);
        } elseif (!$shipment->shipping_method_id && $shipment->shipmentTrackings()->doesntExist()) {
            return ShipmentTracking::create($input);
        }
    }

    public function triggerShipmentStoreWebhook($shipment)
    {
        $shipment->refresh();

        $this->webhook((new ShipmentResource($shipment))->toArray(request()), Shipment::class, Webhook::OPERATION_TYPE_STORE, $shipment->order->customer_id, $shipment->order->order_channel_id);
        $this->webhook((new ShipmentResource($shipment))->toArray(request()), Shipment::class, Webhook::OPERATION_TYPE_STORE, $shipment->order->customer_id);
    }

    /**
     * @param Order $order
     * @param Shipment $shipment
     * @return void
     */
    public function shipVirtualProducts(Order $order, Shipment $shipment): void
    {
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->product && $orderItem->product->type === Product::PRODUCT_TYPE_VIRTUAL) {
                Log::info('I am creating a shipment item');
                $shipmentItem = ShipmentItem::firstOrCreate([
                    'shipment_id' => $shipment->id,
                    'order_item_id' => $orderItem->id,
                ]);

                $shipmentItem->increment('quantity', $orderItem->quantity_shipped);
                $shipmentItem->save();
            }
        }
    }
}
