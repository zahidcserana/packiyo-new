<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Export extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $file)
    {
    }

    public function build()
    {
        return $this->markdown('emails.export')->attach($this->file, ['mime' => 'text/csv']);
    }
}
