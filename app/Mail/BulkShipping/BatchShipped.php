<?php

namespace App\Mail\BulkShipping;

use App\Models\BulkShipBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class BatchShipped extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BulkShipBatch $bulkShipBatch) {
        $this->onQueue(get_distributed_queue_name('bulkshipping'));
    }

    public function build()
    {
        return $this->markdown('bulk_shipping.mail')
            ->attach(Storage::path($this->bulkShipBatch->label), [
                'mime' => 'application/pdf',
            ]);
    }
}
