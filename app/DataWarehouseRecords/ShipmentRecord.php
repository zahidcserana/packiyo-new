<?php

namespace App\DataWarehouseRecords;

class ShipmentRecord extends DataWarehouseRecord
{
    public function getSchema(): array
    {
        return [
            'key_names' => [
                'client_id'
            ],
            'table_name' => 'shipments',
            'schema' => [
                'properties' => [
                    'internal_id' => [
                        'type' => 'integer'
                    ],
                    'client' => [
                        'type' => 'string'
                    ],
                    'client_id' => [
                        'type' => 'string'
                    ],
                    'customer_id' => [
                        'type' => 'integer'
                    ],
                    'order_id' => [
                        'type' => 'integer'
                    ],
                    'customer_name' => [
                        'type' => 'string'
                    ],
                    'order_number' => [
                        'type' => 'string'
                    ],
                    'created_at' => [
                        'type' => 'string',
                        'format' => 'date'
                    ],
                    'updated_at' => [
                        'type' => 'string',
                        'format' => 'date'
                    ]
                ]
            ],
            'messages' => []
        ];
    }

    public function getData($record = null): array
    {
        $shipments = $record;
        $data = [];

        foreach ($shipments as $shipment) {
            $data[] = [
                'client' => preg_replace("(^https?://)", "", config('app.url')),
                'client_id' => preg_replace("(^https?://)", "", config('app.url')) . '-' . $shipment->id,
                'internal_id' => $shipment->id,
                'order_id' => $shipment->order_id,
                'customer_id' => $shipment->customer->id,
                'customer_name' => $shipment->customer->contactInformation->name ?? __('Not set'),
                'order_number' => $shipment->order->number,
                'created_at' => $shipment->created_at->toDateTimeString(),
                'updated_at' => $shipment->updated_at->toDateTimeString()
            ];
        }

        $schema = $this->getSchema();

        foreach ($data as $shipment) {
            $schema['messages'][] = [
                'action' => 'upsert',
                'sequence' => (int) (microtime(true) * 1000),
                'data' => $shipment
            ];
        }

        return $schema;
    }
}
