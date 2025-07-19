<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnedProductReportTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $filterInputs = $request->get('filter_form');
        unset($resource);

        $resource['product_sku'] = $this->product_sku;
        $resource['orders_returned'] = $this->orders_returned;
        $resource['quantity_requested'] = $this->quantity_requested;
        $resource['quantity_returned'] = $this->quantity_returned;

        $resource['returnedOrders'] = [
            'url' => route('return.returnItemsByProduct', 
                    [
                        'product_id' => $this->product_id, 
                        'from_date_created' => $filterInputs['start_date'], 
                        'to_date_created' => $filterInputs['end_date']
                    ]
                ),
        ];

        return $resource;
    }
}
