<?php

namespace App\Jobs;

use App\Components\PackingComponent;
use App\Exceptions\ShippingException;
use App\Http\Requests\FormRequest;
use App\Models\BulkShipBatch;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateBulkShipOrderShipmentJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DEFAULT_WAIT_TIME = 2;
    private const MAX_ATTEMPTS = 10;

    private const LOCK_TIME = 10;
    private const BLOCK_TIME = 10;

    private $lockCacheKey;
    private $finishedCacheKey;
    private $successfulCacheKey;

    public function __construct(
        protected int           $userId,
        protected BulkShipBatch $bulkShipBatch,
        protected Order         $order,
        protected ?array        $input,
        protected int           $shippingLimit = 0,
    ) {
        $this->bulkShipBatch = $bulkShipBatch->withoutRelations();
        $this->order = $order->withoutRelations();

        $this->shippingLimit = Arr::get(
            $this->input,
            'batch_shipping_limit',
            config('bulk_ship.batch_shipping_limit'),
        );
    }

    public function handle(): void
    {
        $this->lockCacheKey = 'bulkshipping.lock.' . $this->batch()->id;
        $this->finishedCacheKey = 'bulkshipping.finished.' . $this->batch()->id;
        $this->successfulCacheKey = 'bulkshipping.successful.' . $this->batch()->id;

        $this->log('Job started...');

        // we need to log the user in so that the shipment is associated with the user
        auth()->loginUsingId($this->userId);

        $jobCanBeRun = $this->checkIfJobCanBeRun();

        if (!$jobCanBeRun) {
            $this->log('Limit is reached. Canceling rest of the batch...');

            if (!$this->batch()->cancelled()) {
                $this->batch()->cancel();
            }

            $this->bulkShipBatch
                ->orders()
                ->updateExistingPivot($this->order, [
                    'status_message' => __('Limit was reached'),
                ]);

            return;
        }

        try {
            $this->log('Starting pack and ship...');

            $this->bulkShipBatch
                ->orders()
                ->updateExistingPivot($this->order, [
                    'started_at' => now(),
                ]);

            $shipments = app('packing')->packAndShip(
                $this->order,
                new FormRequest($this->input),
            );

            if (!$shipments) {
                throw new ShippingException(__('Couldn\'t bulk ship order'));
            }

            $this->orderShippedSuccessfully();

            foreach ($shipments as $shipment) {
                $this->bulkShipBatch
                    ->orders()
                    ->updateExistingPivot($this->order, [
                        'shipment_id' => $shipment->id,
                        'finished_at' => now(),
                    ]);
            }

            app('bulkShip')->updateTags(['BULK-' . $this->bulkShipBatch->id], $this->order);
        } catch (Exception $e) {
            Cache::lock($this->lockCacheKey, self::LOCK_TIME)->block(self::BLOCK_TIME, function () {
                $this->log('>>> Decreasing finished...');

                Cache::decrement($this->finishedCacheKey);
            });

            app('bulkShip')->updateTags(['FAILED-BULK-' . $this->bulkShipBatch->id], $this->order);

            $orderId = $this->order->id;

            $this->log('Order: ' . $orderId . ' shipment failed.');
            $this->log('Exception: ' . $e->getMessage());

            $this->bulkShipBatch
                ->orders()
                ->updateExistingPivot($this->order, [
                    'status_message' => $e->getMessage(),
                ]);

            $this->fail();
        }

        auth()->logout();
    }

    private function checkIfJobCanBeRun($sleepTime = 0, $attempt = 1): bool
    {
        if ($attempt > self::MAX_ATTEMPTS) {
            $this->log('>>> Reached (' . $attempt . '/' . self::MAX_ATTEMPTS . ' attempt. Cancelling.)');
            return false;
        }

        if ($this->batch()->cancelled() || Cache::get($this->successfulCacheKey) >= $this->shippingLimit) {
            return false;
        }

        if ($sleepTime > 0) {
            $this->log('>>> Sleeping... (' . $sleepTime . ' seconds)');
            sleep($sleepTime);
        }

        $inProgressLock = Cache::lock($this->lockCacheKey, self::LOCK_TIME);

        if ($inProgressLock->get()) {
            $successfulJobs = Cache::get($this->successfulCacheKey, 0);

            if ($successfulJobs >= $this->shippingLimit) {
                $this->log('Successful equals the limit.');
                $inProgressLock->release();

                return false;
            }

            $finishedJobs = Cache::get($this->finishedCacheKey, 0);

            $this->log(
                ' ### finishedJobs: ' . $finishedJobs .
                ' ### successfulJobs: ' . $successfulJobs .
                ' ### shippingLimit:  ' . $this->shippingLimit
            );

            if ($finishedJobs < $this->shippingLimit) {
                $this->log('Increasing finished...');
                Cache::increment($this->finishedCacheKey);
                $inProgressLock->release();

                return true;
            }

            $inProgressLock->release();
        } else {
            $this->log('Couldn\'t acquire lock.');
        }

        return $this->checkIfJobCanBeRun(self::DEFAULT_WAIT_TIME, $attempt + 1);
    }

    private function orderShippedSuccessfully(): void
    {
        $this->log('Order shipped successfully.');

        Cache::lock($this->lockCacheKey, self::LOCK_TIME)->block(self::BLOCK_TIME, function() {
            $this->log('>>> Increasing successful...');

            Cache::increment($this->successfulCacheKey);
        });
    }

    private function log($message, $level = 'info'): void
    {
        Log::channel('bulkshipping')->log($level, $message, [
            'job-batch-id' => $this->batch()->id,
            'bulk-ship-batch-id' => $this->bulkShipBatch->id,
            'order-id' => $this->order->id,
            'order-number' => $this->order->number
        ]);
    }
}






























