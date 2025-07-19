<?php

namespace App\Components\Invoice;

use App\Models\CacheDocuments\InvoiceCacheDocument;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument;
use App\Models\Invoice;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class MongoInvoiceSummaryGenerator
{
    public function generate(Invoice $invoice)
    {
        $invoice->calculated_at = Carbon::now()->toDateTimeString();
        $invoice->amount = $invoice->invoiceLineItems->sum('total_charge');
        $invoice->save();

        return; // TODO MOVE TO ANOTHER COMPONENT ON SUMMARY ISSUE

        $invoiceCacheDocument = InvoiceCacheDocument::whereId($invoice->id)->first();
        $billableOperationIds = Shipment::whereHas('order', static function (Builder $query) use ($invoice) {
            $query->where('customer_id', $invoice->customer_id);
        })->whereBetween('created_at', [
            Carbon::parse($invoiceCacheDocument->period_start),
            Carbon::parse($invoiceCacheDocument->period_end)
        ])->whereNull('voided_at')->pluck('id')->toArray();

        return;

        $resultOne = ShipmentCacheDocument::query()
            ->raw(fn($collection) => $collection->aggregate(
                [
                    [
                        '$match' => [
                            'shipments.id' => [
                                '$in' => $billableOperationIds
                            ]
                        ]
                    ],
                    [
                        '$unwind' => '$shipments',
                    ],
                    [
                        '$addFields' => [
                            'shipment_cost' => [
                                '$cond' => [
                                    [
                                        '$isArray' => ['$shipments.cost'],
                                    ],
                                    [
                                        '$sum' => '$shipments.cost',
                                    ],
                                    '$shipments.cost',
                                ],
                            ],
                            'package_cost' => [
                                '$map' => [
                                    'input' => '$shipments.packages',
                                    'in' => [
                                        '$reduce' => [
                                            'input' => '$$this.shipping_box.cost',
                                            'initialValue' => 0,
                                            'in' => [
                                                '$add' => ['$$value', '$$this']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'package_rate_shipment_cache_documents', // replace with the actual collection name
                            'localField' => 'shipments.id',
                            'foreignField' => 'shipment_id',
                            'as' => 'package_rate',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$package_rate',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'shipping_label_rate_shipment_cache_documents', // replace with the actual collection name
                            'localField' => 'shipments.id',
                            'foreignField' => 'shipment_id',
                            'as' => 'shipping_rate',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$shipping_rate',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'picking_billing_rate_shipment_cache_documents', // replace with the actual collection name
                            'localField' => 'shipments.id',
                            'foreignField' => 'shipment_id',
                            'as' => 'picking_rate',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$picking_rate',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$addFields' => [
                            'picking_total_charge' => [
                                '$reduce' => [
                                    'input' => '$picking_rate.charges',
                                    'initialValue' => 0,
                                    'in' => ['$add' => ['$$value', '$$this.total_charge']]
                                ],
                            ],
                            'package_total_charge' => [
                                '$reduce' => [
                                    'input' => '$package_rate.charges',
                                    'initialValue' => 0,
                                    'in' => ['$add' => ['$$value', '$$this.total_charge']]
                                ],
                            ],
                            'shipping_total_charge' => [
                                '$reduce' => [
                                    'input' => '$shipping_rate.charges',
                                    'initialValue' => 0,
                                    'in' => ['$add' => ['$$value', '$$this.total_charge']]
                                ],
                            ],
                        ],
                    ],
                    [
                        '$group' => [
                            '_id' => '$shipments.id',
                            'cost_shipment_label' => [
                                '$sum' => [
                                    '$ifNull' => ['$shipment_cost', 0],
                                ],
                            ],
                            'cost_packages' => [
                                '$sum' => [
                                    '$reduce' => [
                                        'input' => '$package_cost',
                                        'initialValue' => 0,
                                        'in' => ['$add' => ['$$value', '$$this']],
                                    ],
                                ],
                            ],
                            'package_total_charge' => [
                                '$sum' => [
                                    '$ifNull' => ['$package_total_charge', 0],
                                ],
                            ],
                            'shipping_total_charge' => [
                                '$sum' => [
                                    '$ifNull' => ['$shipping_total_charge', 0],
                                ],
                            ],
                            'picking_total_charge' => [
                                '$sum' => [
                                    '$ifNull' => ['$picking_total_charge', 0],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$group' => [
                            '_id' => 'null',
                            'total_shipment_cost' => [
                                '$sum' => '$cost_shipment_label',
                            ],
                            'total_package_cost' => [
                                '$sum' => '$cost_packages',
                            ],
                            'total_package_rate_charge' => [
                                '$sum' => '$package_total_charge',
                            ],
                            'total_shipping_rate_charge' => [
                                '$sum' => '$shipping_total_charge',
                            ],
                            'total_picking_rate_charge' => [
                                '$sum' => '$picking_total_charge',
                            ],
                        ],
                    ],
                ])
            );


        $resultTwo = ShipmentCacheDocument::query()
            ->raw(fn($collection) => $collection->aggregate(
                [
                    [
                        '$match' => [
                            'shipments.id' => [
                                '$in' => $billableOperationIds
                            ]
                        ]
                    ],
                    [
                        '$unwind' => '$shipments',
                    ],
                    [
                        '$lookup' => [
                            'from' => 'package_rate_shipment_cache_documents', // replace with actual collection name
                            'localField' => 'shipments.id',
                            'foreignField' => 'shipment_id',
                            'as' => 'package_rate',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$package_rate',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'shipping_label_rate_shipment_cache_documents', // replace with actual collection name
                            'localField' => 'shipments.id',
                            'foreignField' => 'shipment_id',
                            'as' => 'shipping_rate',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$shipping_rate',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'picking_billing_rate_shipment_cache_documents', // replace with actual collection name
                            'localField' => 'shipments.id',
                            'foreignField' => 'shipment_id',
                            'as' => 'picking_rate',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$picking_rate',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$group' => [
                            '_id' => '$shipments.id',
                            'package_rate_items' => [
                                '$push' => [
                                    'name' => '$package_rate.billing_rate.name',
                                    'charges' => '$package_rate.charges',
                                ],
                            ],
                            'shipping_rate_items' => [
                                '$push' => [
                                    'name' => '$shipping_rate.billing_rate.name',
                                    'charges' => '$shipping_rate.charges',
                                ],
                            ],
                            'picking_rate_items' => [
                                '$push' => [
                                    'name' => '$picking_rate.billing_rate.name',
                                    'charges' => '$picking_rate.charges',
                                ],
                            ],
                        ],
                    ],
                    //
                    [
                        '$project' => [
                            'rates' => [
                                '$concatArrays' => [
                                    '$package_rate_items',
                                    '$shipping_rate_items',
                                    '$picking_rate_items',
                                ],
                            ],
                        ],
                    ],
                    [
                        '$unwind' => '$rates',
                    ],
                    [
                        '$group' => [
                            '_id' => [
                                'shipment_id' => '$_id',
                                'rate_name' => '$rates.name',
                            ],
                            'merged_charges' => [
                                '$push' => '$rates.charges',
                            ],
                        ],
                    ],
                    [
                        '$project' => [
                            'shipment_id' => '$_id.shipment_id',
                            'rate_name' => '$_id.rate_name',
                            'charges' => [
                                '$reduce' => [
                                    'input' => '$merged_charges',
                                    'initialValue' => [],
                                    'in' => [
                                        '$concatArrays' => ['$$value', '$$this'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$group' => [
                            '_id' => '$shipment_id',
                            'rates' => [
                                '$push' => [
                                    'name' => '$rate_name',
                                    'charges' => '$charges',
                                ],
                            ],
                        ],
                    ],
                    [
                        '$group' => [
                            '_id' => null,
                            'shipments' => [
                                '$push' => [
                                    'shipment_id' => '$_id',
                                    'rates' => '$rates',
                                ],
                            ],
                        ],
                    ],
                    [
                        '$project' => [
                            '_id' => 0,
                            'shipments' => '$shipments',
                        ],
                    ],
                ]
            )
            )->toArray();

        $billing_rates = [];
        foreach ($resultTwo[0]['shipments'] as $shipments) {
            foreach ($shipments['rates'] as $rate) {
                $currentRate = [];
                if (!empty($rate['charges']) && $rate->offsetExists('name')) {
                    $currentRate['name'] = $rate['name'];
                    $totalCharge = 0;
                    foreach ($rate['charges'] as $charge) {
                        $totalCharge += $charge['total_charge'];
                    }
                } else {
                    continue;
                }
                $currentRate['total_charge'] = $totalCharge;
            }
        }
    }

}
