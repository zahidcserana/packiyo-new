<?php

namespace App\Http\Resources\ExportResources;

use App\Http\Resources\ShipmentReportTableResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ShipmentReportExportResource extends ExportResource
{
    /**
     * Transform the resource into an array.
     *
     *  @param  Request  $request
     *  @return array
     */
    public function toArray($request): array
    {
        $resource = new ShipmentReportTableResource($this);
        $resource = $resource->toArray($request);

        $shipmentLabels = [];
        $trackingNumbersWithUrls = [];
        $trackingNumbers = [];

        foreach ($this->shipmentLabels as $shipmentLabel) {
            $route = route('shipment.label', [
                'shipment' => $this,
                'shipmentLabel' => $shipmentLabel,
            ]);

            $shipmentLabels[] = $route;
        }

        foreach($this->shipmentTrackings as $tracking){
            $trackingNumbersWithUrls[] = $tracking->tracking_url . ' ' . $tracking->tracking_number;
            $trackingNumbers[] = $tracking->tracking_number;
        }

        unset($resource['id']);
        $resource['order'] = $resource['order']['number'];
        unset($resource['order_products']);
        $resource['shipment_labels'] = implode(', ', $shipmentLabels);
        $resource['tracking_number_without_url'] = implode(', ', $trackingNumbers);
        $resource['tracking_number'] = implode(', ', $trackingNumbersWithUrls);
        unset($resource['allow_void_label']);
        unset($resource['voided_at']);
        unset($resource['void_link']);
        unset($resource['shipping_box']);
        $resource['package'] = join(', ', $this->shippingBoxNames());
        $resource['status'] = $this->voided_at ? __('voided') : __('active');

        if (is_array($resource['customer']) && Arr::exists($resource['customer'], 'name')) {
            $resource['customer'] = $resource['customer']['name'];
        }

        $resource['warehouse'] = $this->order->warehouse->contactInformation->name ?? '';

        return $resource;
    }

    public static function columns(): array
    {
        $columns = [
            'order',
            'order_date',
            'shipment_date',
            'tracking_number',
            'shipment_labels',
            'shipping_carrier',
            'shipping_method',
            'name',
            'address',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'company',
            'phone',
            'distinct_items',
            'lines_shipped',
            'line_item_total',
            'email',
            'user',
            'cost',
            'tags',
            'order_weight',
            'package_weight',
            'customer',
            'tracking_number_without_url',
            'package',
            'status',
        ];

        $customer = app('user')->getSelectedCustomers()->first();
        $showCost = !$customer || !$customer->parent_id;

        if (!$showCost) {
            $columns = array_diff($columns, ['cost']);
        }

        return $columns;
    }
}
