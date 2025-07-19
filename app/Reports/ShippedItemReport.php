<?php

namespace App\Reports;

use App\Http\Dto\Filters\Reports\ShippedItemReportFilterDto;
use App\Http\Resources\ExportResources\ShippedItemReportExportResource;
use App\Http\Resources\ShippedItemReportTableResource;
use App\Models\OrderChannel;
use App\Models\PackageOrderItem;
use App\Models\ShippingBox;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ShippedItemReport extends Report
{
    protected $reportId = 'shipped_item';
    protected $dataTableResourceClass = ShippedItemReportTableResource::class;
    protected $exportResourceClass = ShippedItemReportExportResource::class;

    protected function reportTitle(): string
    {
        return __('Shipped Items');
    }

    protected function getQuery(Request $request): ?Builder
    {
        list($customerIds, $sortColumnName, $sortDirection, $filterInputs, $term) = $this->dataFilter($request, 'package_order_items.created_at', 'desc');

        return PackageOrderItem::with([
            'lot',
            'location',
            'orderItem.order.orderChannel',
            'package.shipment.shipmentTrackings',
            'package.shipment.shippingMethod.shippingCarrier',
            'package.shipment.user.contactInformation',
            'package.shippingBox',
            'tote',
        ])
            ->leftJoin('order_items', 'package_order_items.order_item_id', '=', 'order_items.id')
            ->leftJoin('totes', 'package_order_items.tote_id', '=', 'totes.id')
            ->leftJoin('lots', 'package_order_items.lot_id', '=', 'lots.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('packages', 'package_order_items.package_id', '=', 'packages.id')
            ->leftJoin('shipping_boxes', 'packages.shipping_box_id', '=', 'shipping_boxes.id')
            ->leftJoin('shipments', 'packages.shipment_id', '=', 'shipments.id')
            ->leftJoin('shipment_trackings', 'shipments.id', '=', 'shipment_trackings.shipment_id')
            ->leftJoin('shipping_methods', 'shipments.shipping_method_id', '=', 'shipping_methods.id')
            ->leftJoin('shipping_carriers', 'shipping_methods.shipping_carrier_id', '=', 'shipping_carriers.id')
            ->leftJoin('contact_informations', 'shipments.user_id', '=', 'contact_informations.object_id')
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('order_channels', 'orders.order_channel_id', '=', 'order_channels.id')
            ->where('contact_informations.object_type', User::class)
            ->whereHas('orderItem.order', static function (Builder $query) use ($customerIds) {
                $query->whereIn('customer_id', $customerIds);
            })
            ->when($sortColumnName, static function (Builder $query) use ($sortColumnName, $filterInputs) {
                if (str_contains($sortColumnName, 'locations.') || Arr::exists($filterInputs, 'location')) {
                    $query->leftJoin('locations', 'package_order_items.location_id', '=', 'locations.id');
                }
            })
            ->when(!empty($filterInputs), static function (Builder $query) use ($filterInputs) {
                if (Arr::get($filterInputs, 'start_date') || Arr::get($filterInputs, 'end_date')) {
                    $startDate = Carbon::parse($filterInputs['start_date'] ?? '1970-01-01')->startOfDay();
                    $endDate = Carbon::parse($filterInputs['end_date'] ?? Carbon::now())->endOfDay();

                    $query->whereBetween('package_order_items.created_at', [$startDate, $endDate]);
                }

                if (Arr::get($filterInputs, 'packer')) {
                    $query->where('contact_informations.name', $filterInputs['packer']);
                }

                if (Arr::get($filterInputs, 'tote')) {
                    $query->where('totes.name', 'like', '%' . $filterInputs['tote'] . '%');
                }

                if (Arr::get($filterInputs, 'lot')) {
                    $query->where('lots.name', 'like', '%' . $filterInputs['lot'] . '%');
                }

                if (Arr::get($filterInputs, 'location')) {
                    $query->where('locations.name', 'like', '%' . $filterInputs['location'] . '%');
                }

                if (Arr::get($filterInputs, 'order_channel')) {
                    $query->where('order_channels.name', $filterInputs['order_channel']);
                }

                if (Arr::get($filterInputs, 'shipping_method')) {
                    $query->where('shipping_methods.name', $filterInputs['shipping_method']);
                }

                if (Arr::get($filterInputs, 'shipping_carrier')) {
                    $query->where('shipping_carriers.name', $filterInputs['shipping_carrier']);
                }

                if (Arr::get($filterInputs, 'shipping_box')) {
                    $query->where('shipping_boxes.name', $filterInputs['shipping_box']);
                }
            })
            ->when(!empty($term), static function (Builder $query) use ($term) {
                $query->where(static function ($query) use ($term) {
                    $query->whereHas('orderItem.product', static function ($query) use ($term) {
                        $query->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term);
                    })
                        ->orWhereHas('orderItem.order', static function ($query) use ($term) {
                            $query->where('number', 'like', $term);
                        })
                        ->orWhereHas('package.shipment.shipmentTrackings', static function ($query) use ($term) {
                            $query->where('tracking_number', 'like', $term);
                        })
                        ->orWhere('serial_number', 'like', $term);
                });
            })
            ->select('package_order_items.*')
            ->groupBy('package_order_items.id')
            ->orderBy($sortColumnName, $sortDirection);
    }

    protected function getFilterDto(Request $request): ?Arrayable
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id');
        $shippingCarriers = ShippingCarrier::whereIn('customer_id', $customerIds)->get();

        return new ShippedItemReportFilterDto(
            $shippingCarriers->pluck('name')->unique(),
            ShippingMethod::whereIn('shipping_carrier_id', $shippingCarriers->pluck('id'))->get()->pluck('name')->unique(),
            OrderChannel::whereIn('customer_id', $customerIds)->get()->pluck('name')->unique(),
            ShippingBox::whereIn('customer_id', $customerIds)->get()->pluck('name')->unique()
        );
    }
}
