<?php

namespace App\Http\Controllers;

use App\Components\Shipping\Providers\GenericShippingProvider;
use App\Models\EDI\Providers\CrstlASN;
use App\Models\EDI\Providers\CrstlPackingLabel;
use App\Models\PackageDocument;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Models\ShipmentLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Requests\Shipment\ShipmentTrackingRequest;
use Illuminate\Http\JsonResponse;

class ShipmentController extends Controller
{
    public function label(Shipment $shipment, ShipmentLabel $shipmentLabel)
    {
        return app('shipment')->label($shipment, $shipmentLabel);
    }

    public function ediPackingLabel(Shipment $shipment, CrstlASN $asn, CrstlPackingLabel $packingLabel)
    {
        return app('shipment')->ediPackingLabel($shipment, $asn, $packingLabel);
    }

    public function packageDocument(Shipment $shipment, PackageDocument $packageDocument)
    {
        return app('shipment')->packageDocument($shipment, $packageDocument);
    }

    public function void(Request $request, Shipment $shipment)
    {
        $message = '';

        try {
            $response = app('shipping')->void($shipment);

            if ($request->ajax()) {
                return response()->json($response);
            }

            $message = $response['message'];

            if ($response['success']) {
                return redirect()->back()->withStatus($message);
            }
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
        }

        return redirect()->back()->withErrors($message);
    }

    public function getPackingSlip(Shipment $shipment)
    {
        return app('shipment')->getPackingSlip($shipment);
    }

    public function getShipmentTrackingModal(Shipment $shipment, ShipmentTracking $shipmentTracking = null): \Illuminate\Contracts\View\View
    {
        return View::make('shared.modals.components.orders.shipment_tracking', compact('shipment', 'shipmentTracking'));
    }

    public function updateTracking(ShipmentTrackingRequest $request, Shipment $shipment): JsonResponse
    {
        if (app('shipment')->updateTracking($request, $shipment)) {
            app(GenericShippingProvider::class)->regenerateShipmentLabels($shipment);

            return response()->json(['success' => true, 'message' => __('Data successfully saved.')]);
        }

        return response()->json(['success' => false, 'message' => __('Something went wrong!')]);
    }
}
