<?php

namespace App\Events;

use Illuminate\Broadcasting\{Channel, InteractsWithSockets};
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\PrintJobResource;

class NewPrintJobEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const EVENT_CHANNEL = 'print-jobs';
    const EVENT_NAME = 'new-print-job';

    public PrintJobResource $printJob;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PrintJobResource $printJob)
    {
        $this->printJob = $printJob;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return string[]
     */
    public function broadcastOn(): array
    {
        return [new Channel($this->channelName())];
    }

    /**
     * @return string
     */
    public function broadcastAs(): string
    {
        return self::EVENT_NAME;
    }

    /**
     * @return PrintJobResource[]
     */
    public function broadcastWith(): array
    {
        return [
            'data' => $this->printJob
        ];
    }

    /**
     * @return string
     */
    public static function channelName(): string
    {
        return join('-', [
            self::EVENT_CHANNEL,
            config('app.url')
        ]);
    }
}
