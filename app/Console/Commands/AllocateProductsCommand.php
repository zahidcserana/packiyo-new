<?php

namespace App\Console\Commands;

use App\Jobs\AllocateInventoryJob;
use App\Models\Product;
use Illuminate\Console\Command;

class AllocateProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'allocate-inventory {--c|customer=} {--s|sku=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reallocates inventory';

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
        $productQuery = Product::query();

        if ($customerId = $this->option('customer')) {
            $productQuery->where('customer_id', $customerId);
        }

        if ($sku = $this->option('sku')) {
            $productQuery->where('sku', $sku);
        }

        foreach ($productQuery->cursor() as $product) {
            $this->line(__('Allocating :sku', ['sku' => $product->sku]));
            AllocateInventoryJob::dispatchSync($product);
        }

        return 0;
    }
}
