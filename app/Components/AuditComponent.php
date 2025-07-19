<?php

namespace App\Components;

use App\Models\{ContactInformation,
    LocationType,
    Order,
    OrderItem,
    PurchaseOrder,
    PurchaseOrderItem,
    Image,
    Product,
    ToteOrderItem};
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Resources\AuditTableResource;

class AuditComponent extends BaseComponent
{
    public static $objectTitle = [
        Order::class => 'Order',
        OrderItem::class => 'Product',
        ContactInformation::class => 'Address Info',
        Product::class => 'Product',
        Image::class => 'Image',
        LocationType::class => 'Location Type',
        PurchaseOrder::class => 'Purchase Order',
        PurchaseOrderItem::class => 'Product',
        ToteOrderItem::class => 'Tote',
    ];

    public function prepareEachAudits(Request $request, $audits)
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'audits.created_at';
        $sortDirection = 'desc';
        $term = $request->get('search')['value'];

        if ($term) {
            $audits = $audits->where('event', $term);
        }

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        if ($sortDirection == 'asc') {
            $audits = $audits->sortBy($sortColumnName);
        } else {
            $audits = $audits->sortByDesc($sortColumnName);
        }

        $audits->map(function($audit, $key) {
            $audit->object_name = Arr::get(self::$objectTitle, $audit->auditable_type, str_replace("App\Models\\", "", $audit->auditable_type));
        });

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $audits = $audits->skip($request->get('start'))->take($request->get('length'));
        }

        return AuditTableResource::collection($audits);
    }
}
