<?php

namespace App\Components;

use App\Mail\InvoiceCalculationDone;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailComponent
{
    public function sendEmail(string $to, string $templateClass, array $data = []): bool
    {
        $mailObject = $this->buildEmail($templateClass, $data);

        if (!empty($mailObject)) {
            return $this->send($to, $mailObject);
        }

        return false;
    }

    public function buildEmail(string $templateClass, array $data): ?InvoiceCalculationDone
    {
        switch ($templateClass) {
            case InvoiceCalculationDone::class:

                return new InvoiceCalculationDone(
                    $data['client_name'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['link']
                );
            default:

                return null;
        }
    }

    public function send(string $to, Mailable $mail): bool
    {
        Mail::to($to)->send($mail);

        return true;
    }
}
