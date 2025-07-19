<?php

namespace App\Components;

use App\Models\Link;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\Shipment;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;

class LinkComponent extends BaseComponent
{
    public function store(array $data) : Link
    {
        return DB::transaction(function () use ($data) {
            $link = new Link([
                'name' => $data['name'],
                'url' => $data['url'],
                'is_printable' => $data['is_printable'],
                'printer_type' => $data['printer_type'] ?? UserSetting::USER_SETTING_LABEL_PRINTER_ID,
            ]);

            $shipment = Shipment::find($data['shipment_id']);
            $link = $shipment->links()->save($link);

            if ($link->is_printable) {
                $this->createPrintJob($shipment, $link);
            }

            return $link;
        });
    }

    private function createPrintJob(Shipment $shipment, Link $link)
    {
        $printer = $this->getPrinter($shipment, $link->printer_type);

        if ($printer) {
            PrintJob::create([
                'object_type' => Shipment::class,
                'object_id' => $shipment->id,
                'url' => $link->url,
                'printer_id' => $printer->id,
                'user_id' => auth()->user()->id
            ]);
        }
    }

    private function getPrinter(Shipment $shipment, string $printerType) : ?Printer
    {
        if ($printerType == UserSetting::USER_SETTING_LABEL_PRINTER_ID) {
            return app('printer')->getDefaultLabelPrinter($shipment->customer);
        }

        if ($printerType == UserSetting::USER_SETTING_BARCODE_PRINTER_ID) {
            return app('printer')->getDefaultBarcodePrinter($shipment->customer);
        }

        if ($printerType == UserSetting::USER_SETTING_SLIP_PRINTER_ID) {
            return app('printer')->getDefaultSlipPrinter($shipment->customer);
        }
    }
}
