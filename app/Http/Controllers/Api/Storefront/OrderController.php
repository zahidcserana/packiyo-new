<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\ApiController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Enums\Source;
use App\Http\Requests\Order\StoreRequest;

class OrderController extends ApiController
{
    public function store(Request $request)
    {
        try {
            
            return DB::transaction(function () use ($request) {
                $tenant = app('tenant');
                $input = $request->all();
                $input['customer_id'] = $tenant->id;
                $input['shipping_contact_information'] = $input['billing_contact_information'];
    
                $storeRequest = StoreRequest::make($input);

                $order = app('order')->store($storeRequest, source: Source::PUBLIC_API);
                
                return response()->json([
                    'success' => true,
                    'order_number' => $order->number,
                ]);
            });
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ], 500);
        }
    }
}
