<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShowMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show metrics of the instance';

    protected $queueSelects = [];
    protected $queueAggregates = [];

    protected $allocationSelects = [];
    protected $allocationAggregates = [];

    protected $metrics = [];

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
        $this->setQueueSelects();
        $this->setQueueAggregates();
        $this->calculateQueueMetrics();

        $this->setAllocationSelects();
        $this->setAllocationAggregates();
        $this->calculateAllocationMetrics();

        $this->logMetrics();
        $this->drawMetrics();

        return 0;
    }

    protected function setQueueSelects(): void
    {
        $this->queueSelects = [
            'queue' => 'queue',
            'size_available' => DB::raw('COUNT(CASE WHEN available_at <= UNIX_TIMESTAMP(NOW()) THEN 1 END) AS size_available'),
            'size_reserved' => DB::raw('COUNT(CASE WHEN reserved_at IS NOT NULL THEN 1 END) AS size_reserved'),
            'size_total' => DB::raw('COUNT(*) AS size_total'),
            'age' => DB::raw('UNIX_TIMESTAMP(NOW()) - MIN(created_at) AS age')
        ];
    }

    protected function setQueueAggregates(): void
    {
        $this->queueAggregates = [
            'size_available_sum' => fn($data) => array_sum(Arr::pluck($data, 'size_available')),
            'size_reserved_sum' => fn($data) => array_sum(Arr::pluck($data, 'size_reserved')),
            'size_total_sum' => fn($data) => array_sum(Arr::pluck($data, 'size_total')),
            'age_max' => fn($data) => !empty(Arr::pluck($data, 'age')) ? max(Arr::pluck($data, 'age')) : 0
        ];
    }

    private function calculateQueueMetrics(): void
    {
        $queueConnection = config('queue.default');

        if ($queueConnection  !== 'database') {
            throw new \Exception('Metrics only implemented on database queue connection');
        }

        $table = config('queue.connections.database.table');

        $data = DB::table($table)
            ->select(array_values($this->queueSelects))
            ->groupBy('queue')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        $this->metrics['queue'] = [
            'data' => $data
        ];

        foreach ($this->queueAggregates as $key => $callback) {
            $this->metrics['queue'][$key] = $callback($data);
        }
    }

    protected function setAllocationSelects(): void
    {
        $this->allocationSelects = [
            'sku' => 'sku',
            'product_id' => 'product_id',
            'total_lines' => DB::raw('COUNT(*) AS total_lines'),
            'total_quantity_pending' => DB::raw('SUM(quantity_pending) AS total_quantity_pending'),
        ];
    }

    protected function setAllocationAggregates(): void
    {
        $this->allocationAggregates = [
            'products_count' => fn($data) => count($data),
            'total_lines_sum' => fn($data) => array_sum(Arr::pluck($data, 'total_lines')),
            'total_quantity_pending_sum' => fn($data) => array_sum(Arr::pluck($data, 'total_quantity_pending'))
        ];
    }

    protected function calculateAllocationMetrics(): void
    {
        $data = OrderItem::select($this->allocationSelects)
            ->whereDoesntHave('order', fn (Builder $query) => $query->where('allocation_hold', 1))
            ->whereNotNull('product_id')
            ->where('quantity_pending', '>', 0)
            ->where('quantity_pending', '>', DB::raw('quantity_allocated + quantity_backordered'))
            ->groupBy('product_id')
            ->getQuery()
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();

        $this->metrics['allocation'] = [
            'data' => $data
        ];

        foreach ($this->allocationAggregates as $key => $callback) {
            $this->metrics['allocation'][$key] = $callback($data);
        }
    }

    protected function logMetrics(): void
    {
        Log::info('Metrics logger', $this->metrics);
    }

    protected function drawMetrics(): void
    {
        $this->drawQueueMetrics();
        $this->drawAllocationMetrics();
    }

    protected function drawQueueMetrics(): void
    {
        $this->info(__('Queue metrics:'));
        $this->table(array_keys($this->queueSelects), $this->metrics['queue']['data']);
    }

    protected function drawAllocationMetrics(): void
    {
        $this->info(__('Allocation metrics:'));
        $this->table(array_keys($this->allocationSelects), $this->metrics['allocation']['data']);
    }
}
