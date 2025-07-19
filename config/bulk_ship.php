<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum similar orders
    |--------------------------------------------------------------------------
    |
    | Threshold for the number of similar orders. It makes no sense to create
    | bulk shipping batches when all orders are different. So we will
    | only create them when we have at least n similar orders.
    |
    */
    'min_similar_orders' => env('BULK_SHIP_MIN_SIMILAR_ORDERS', 10),

    /*
    |--------------------------------------------------------------------------
    | Batch order limit
    |--------------------------------------------------------------------------
    |
    | Threshold for the number of matched orders that can be in the same batch.
    |
    */
    'batch_order_limit' => env('BULK_SHIP_BATCH_ORDER_LIMIT', 300),

    /*
    |--------------------------------------------------------------------------
    | Batch shipping order limit
    |--------------------------------------------------------------------------
    |
    | Limit for the number of batch orders that can be shipped.
    |
    */
    'batch_shipping_limit' => env('BULK_SHIP_SHIPPING_LIMIT', 100),

];
