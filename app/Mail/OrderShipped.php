<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Shipment $shipment;
    public string $headerImage;

    /**
     * Create a new message instance.
     *
     * @param Shipment $shipment
     * @return void
     */
    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
        $this->headerImage = asset('/img/email_header_img1.png');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject('Your Order Has Shipped')
            ->markdown('emails.order_shipped');
    }
}
