<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use stdClass;

class AutomationsByOrder extends Command
{
    protected const TABLE_HEADERS = ['ID', 'Name', 'Enabled', 'Applies To', 'Clients', 'Position', 'Events', 'Conditions', 'Actions'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:by-order {customer} {--S|skip=0} {--T|take=30} {--F|for-client=*} {--A|original-automation=*} {--R|automation-revision=*} {--O|order-id=*} {--N|order-number=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List orders acted on by automations';

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
        $ownerCustomer = Customer::findOrFail($this->argument('customer'));
        $fields = [
            'aaoo.created_at as timestamp' => 'Timestamp',
            'target_event' => 'Event',
            'o.customer_id' => 'Customer ID',
            'cci.name as customer' => 'Customer',
            'aaoo.original_revision_automation_id' => 'Automation Original ID',
            'automation_id' => 'Automation Revision ID',
            'a.name as automation' => 'Automation',
            'a.created_at as revision' => 'Revision',
            'operation_id as order_id' => 'Order ID',
            'o.number as order_number' => 'Order #'
        ];
        $query = DB::table('automation_acted_on_operation', 'aaoo')
            ->select(array_keys($fields))
            ->join('automations as a', 'a.id', '=', 'aaoo.automation_id')
            ->join('orders as o', function (JoinClause $join) {
                $join->on('o.id', '=', 'aaoo.operation_id')
                    ->where('aaoo.operation_type', '=', Order::class);
            })
            ->join('contact_informations as cci', function (JoinClause $join) {
                $join->on('o.customer_id', '=', 'cci.object_id')
                    ->where('cci.object_type', '=', Customer::class);
            })
            ->where('a.customer_id', '=', $ownerCustomer->id);

        $clientIds = $this->option('for-client');
        $originalRevisionIds = $this->option('original-automation');
        $automationIds = $this->option('automation-revision');
        $orderIds = $this->option('order-id');
        $orderNumbers = $this->option('order-number');

        if ($clientIds) {
            $query = $query->whereIn('o.customer_id', $clientIds);
        }

        if ($originalRevisionIds) {
            $query = $query->whereIn('aaoo.original_revision_automation_id', $originalRevisionIds);
        }

        if ($automationIds) {
            $query = $query->whereIn('aaoo.automation_id', $automationIds);
        }

        if ($orderIds) {
            $query = $query->whereIn('o.id', $orderIds);
        }

        if ($orderNumbers) {
            $query = $query->whereIn('o.number', $orderNumbers);
        }

        $results = $query->orderBy('timestamp', 'desc')
            ->skip($this->option('skip'))
            ->take($this->option('take'))
            ->get();
        $this->table(array_values($fields), $results->map(fn (stdClass $result) => (array) $result)->toArray());

        return 0;
    }
}
