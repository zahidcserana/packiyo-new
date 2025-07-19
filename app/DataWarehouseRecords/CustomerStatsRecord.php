<?php

namespace App\DataWarehouseRecords;

use App\Models\Customer;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerStatsRecord extends DataWarehouseRecord
{
    public function getSchema(): array
    {
        return [
            'key_names' => [
                'client_id'
            ],
            'table_name' => 'customer_stats',
            'schema' => [
                'properties' => [
                    'internal_id' => [
                        'type' => 'integer'
                    ],
                    'customer_name' => [
                        'type' => 'string'
                    ],
                    'client' => [
                        'type' => 'string'
                    ],
                    'instance' => [
                        'type' => 'string'
                    ],
                    'client_id' => [
                        'type' => 'string'
                    ],
                    'users_count' => [
                        'type' => 'integer'
                    ],
                    'active_users_count' => [
                        'type' => 'integer'
                    ],
                    'shipments_counts' => [
                        'type' => 'integer'
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
        $customers = Customer::query()
            ->leftJoin('customer_user', 'customer_user.customer_id', '=', 'customers.id')
            ->leftJoin('users', static function (JoinClause $joinClause) {
                $joinClause
                    ->where('users.system_user', false)
                    ->on('customer_user.user_id', '=', 'users.id');
            })
            ->leftJoin('users AS active_users', static function (JoinClause $joinClause) {
                $joinClause
                    ->where('active_users.last_login_at', Carbon::today())
                    ->where('active_users.system_user', false)
                    ->on('customer_user.user_id', '=', 'users.id');
            })
            ->leftJoin('contact_informations AS customer_contact_information', static function (JoinClause $joinClause) {
                $joinClause
                    ->where('customer_contact_information.object_type', Customer::class)
                    ->on('customer_contact_information.object_id', 'customers.id');
            })
            ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('shipments', 'orders.id', '=', 'shipments.order_id')
            ->whereNull('customers.deleted_at')
            ->select(
                'customers.id as internal_id',
                'customer_contact_information.name AS customer_name',
                'customers.created_at',
                DB::raw('COUNT(DISTINCT users.id) as users_count'),
                DB::raw('COUNT(DISTINCT active_users.id) as active_users_count'),
                DB::raw('COUNT(DISTINCT shipments.id) as shipments_count'),
                'customers.parent_id AS parent_id',
            )
            ->groupBy('customers.id')
            ->get()
            ->toArray();

        $schema = $this->getSchema();

        foreach ($customers as $customer) {
            $customer['instance'] = preg_replace("(^https?://)", "", config('app.url'));
            $customer['client_id'] = $customer['instance'] . '-' . $customer['internal_id'] . '-' . Carbon::now()->format('Y-m-d');
            $customer['updated_at'] = Carbon::now();

            if (is_null($customer['parent_id'])) {
                $customer['client'] = '';
            } else {
                $customer['client'] = $customer['customer_name'];
                $customer['customer_name'] = Customer::find($customer['parent_id'])->contactInformation->name;
            }

            unset($customer['parent_id']);

            $schema['messages'][] = [
                'action' => 'upsert',
                'sequence' => (int) (microtime(true) * 1000),
                'data' => $customer
            ];
        }

        return $schema;
    }
}
