<?php

use App\Http\Controllers\Api\Storefront\CartController;
use App\Http\Controllers\Api\Storefront\HomeController;
use App\Http\Controllers\Api\Storefront\OrderController;
use App\Http\Controllers\Api\Storefront\ProductController;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ActionRegistrar;
use LaravelJsonApi\Laravel\Routing\Relationships;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['middleware' => ['api-log', 'auth:api', 'abilities:*', 'active.api'], 'as' => 'api.'], function() {
    Route::post('dashboard/statistics', 'Api\HomeController@statistics')->name('dashboard.statistics');
    Route::get('profile', 'Api\ProfileController@edit')->name('profile.edit');
    Route::post('profile/password', 'Api\ProfileController@password')->name('profile.password');
    Route::post('profile/update', 'Api\ProfileController@update')->name('profile.update');
    Route::post('profile/upload', 'Api\ProfileController@upload')->name('profile.upload');
    Route::get('profile/delete', 'Api\ProfileController@delete')->name('profile.delete');
    Route::get('profile/logout', 'Api\ProfileController@logout')->name('profile.logout');
    Route::get('ably/token', 'Api\AblyController@generateToken')->name('ably.token');

    JsonApiRoute::server('v1')
        ->namespace('Api')
        ->resources(function ($server) {
            $server->resource('users', UserController::class)
                ->actions(function ($actions) {
                    $actions->withId()->get('customers');
                    $actions->withId()->get('webhooks');
                    $actions->withId()->post('update');
                });
            $server->resource('tags', TagController::class);
            $server->resource('tasks', TaskController::class);
            $server->resource('tasks', BatchPickingController::class)->only()
                ->actions( function ($actions){
                    $actions->post('/single-item-batch-picking', 'singleItemBatchPicking');
                    $actions->post('/single-order-picking', 'singleOrderPicking');
                    $actions->post('/multi-order-picking', 'multiOrderPicking');
                    $actions->post('/close-picking-task', 'closePickingTask');
                }
                );
            $server->resource('picking-batches', BatchPickingController::class)->only()
                ->actions( function ($actions){
                    $actions->post('/existing-items', 'existingItems');
                    $actions->post('/pick', 'pick');
                });
            $server->resource('tasks', CycleCountBatchController::class)->only()
                ->actions( function ($actions){
                    $actions->post('/close-counting-task', 'closeCountingTask');
                }
                );
            $server->resource('cycle-count-batches', CycleCountBatchController::class)->only()
                ->actions( function ($actions){
                    $actions->post('/available-batch', 'availableCountingBatch');
                    $actions->post('/close', 'closeCountingTask');
                    $actions->post('/count', 'count');
                    $actions->post('/pick', 'pick');
                });
            $server->resource('order-channels', OrderChannelController::class)
                ->actions(function ($actions){
                    $actions->withId()->post('process_shipments/{syncFrom}', 'processShipments');
                });
            $server->resource('orders', OrderController::class)
                ->actions(function ($actions){
                    $actions->get('filter');
                    $actions->withId()->post('ship');
                    $actions->withId()->post('markAsFulfilled');
                    $actions->withId()->post('cancel');
                    $actions->withId()->post('archive');
                    $actions->withId()->post('unarchive');
                    $actions->withId()->get('history');
                    $actions->get('items/{order_item}/history', 'itemHistory');
                    $actions->withId()->post('pick_to_tote', 'pickOrderItems');
                });
            $server->resource('order-statuses', OrderStatusController::class);
            $server->resource('shipping-boxes', ShippingBoxController::class);
            $server->resource('purchase-orders', PurchaseOrderController::class)
                ->actions(function ($actions){
                    $actions->get('filter');
                    $actions->withId()->post('receive');
                    $actions->withId()->post('close');
                    $actions->post('reject/{purchase_order_item}', 'reject');
                    $actions->withId()->get('history');
                    $actions->get('items/{purchase_order_item}/history', 'itemHistory');
                });
            $server->resource('purchase-order-statuses', PurchaseOrderStatusController::class);
            $server->resource('task-types', TaskTypeController::class);
            $server->resource('suppliers', SupplierController::class);
            $server->resource('products', ProductController::class)
                ->actions(function ($actions){
                    $actions->get('filter');
                    $actions->withId()->get('history');
                    $actions->withId()->post('transfer');
                    $actions->withId()->post('update');
                    $actions->withId()->post('change_location_quantity', 'changeLocationQuantity');
                    $actions->withId()->post('add_to_location', 'addToLocation');
                });
            $server->resource('returns', ReturnController::class)
                ->actions(function ($actions){
                    $actions->get('filter');
                    $actions->withId()->post('receive');
                    $actions->withId()->get('history');
                    $actions->get('items/{return_item}/history', 'itemHistory');
                });
            $server->resource('return-statuses', ReturnStatusController::class);
            $server->resource('warehouses', WarehouseController::class);
            $server->resource('address-books', AddressBookController::class);
            $server->resource('locations', LocationController::class);
            $server->resource('webhooks', WebhookController::class);
            $server->resource('customers', CustomerController::class)
                ->actions(function ($actions){
                    $actions->withId()->get('warehouses');
                    $actions->withId()->get('users');
                    $actions->withId()->get('tasks');
                    $actions->withId()->get('products');
                    $actions->withId()->get('user', 'listUsers');
                    $actions->withId()->put('user', 'updateUsers');
                    $actions->withId()->delete('user/{user}', 'detachUser');
                });
            $server->resource('webshipper-credentials', WebshipperCredentialController::class);
            $server->resource('easypost-credentials', EasypostCredentialController::class);
            $server->resource('inventory-logs', InventoryLogController::class);
            $server->resource('picking-carts', PickingCartController::class);
            $server->resource('totes', ToteController::class)
                ->actions(function ($actions) {
                    $actions->withId()->get('order-items', 'toteOrderItems');
                    $actions->withId()->post('empty', 'emptyTote');
                    $actions->withId()->post('pick', 'pickOrderItems');
                });
            $server->resource('printers', PrinterController::class)
                ->actions(function ($actions) {
                    $actions->post('import');
                    $actions->get('userPrintersAndJobs', 'userPrintersAndJobs');
                    $actions->post('jobs/{printJob}/start', 'jobStart');
                    $actions->post('jobs/{printJob}/status', 'jobStatus');
                });
        });
});

Route::group(['middleware' => ['api-log', 'auth:api'], 'as' => 'api.'], function () {
    JsonApiRoute::server('publicv1')
        ->prefix('v1')
        ->namespace('Api\PublicV1')
        ->resources(function (ResourceRegistrar $server) {
            $server->resource('users', UserController::class)
                ->only('')
                ->actions(function (ActionRegistrar $actions) {
                    $actions->get('me');
                });
            $server->resource('products', ProductController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                    $relationships->hasMany('barcodes');
                })
                ->actions(function (ActionRegistrar $actions) {
                    $actions->withId()->post('inventory');
                });
            $server->resource('orders', OrderController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                    $relationships->hasOne('order_channel')->readOnly();
                    $relationships->hasOne('billing_contact_information');
                    $relationships->hasOne('shipping_contact_information');
                    $relationships->hasOne('shipping_box')->readOnly();
                    $relationships->hasMany('order_items');
                    $relationships->hasMany('shipments')->readOnly();
                    $relationships->hasMany('returns')->readOnly();
                })
                ->actions(function (ActionRegistrar $actions) {
                    $actions->withId()->post('cancel');
                });
            $server->resource('purchase-orders', PurchaseOrderController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                    $relationships->hasOne('warehouse')->readOnly();
                    $relationships->hasMany('purchase_order_items')->readOnly();
                });
            $server->resource('totes', ToteController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('warehouse')->readOnly();
                    $relationships->hasMany('order_items');
                })
                ->actions(function ($actions) {
                    $actions->withId()->post('empty', 'emptyTote');
                    $actions->withId()->post('pick', 'pickOrderItems');
                });
            $server->resource('webhooks', WebhookController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                });
            $server->resource('external-carrier-credentials', ExternalCarrierCredentialController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                });
            $server->resource('customers', CustomerController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('contact_information');
                    $relationships->hasMany('children')->readOnly();
                    $relationships->hasMany('customer_settings')->readOnly();
                });
            $server->resource('locations', LocationController::class)
                ->only('index', 'show')
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('warehouse')->readOnly();
                    $relationships->hasOne('location_type')->readOnly();
                });
            $server->resource('warehouses', WarehouseController::class)
                ->only('index', 'show')
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                    $relationships->hasMany('locations')->readOnly();
                });
            $server->resource('shipments', ShipmentController::class)
                ->readOnly()
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasMany('links')->readOnly();
                });

            $server->resource('links', LinkController::class)
                ->only('show', 'store', 'index')
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('shipment')->readOnly();
                });
        });
});

Route::middleware(['auth:sanctum', EnsureFrontendRequestsAreStateful::class, 'api-log'])->group(function () {
    JsonApiRoute::server('frontendv1')
        ->prefix('frontendv1')
        ->namespace('Api\FrontendV1')
        ->resources(function (ResourceRegistrar $server) {
            $server->resource('customers', CustomerController::class)
                ->actions(function (ActionRegistrar $actions) {
                    $actions->withId()->post('/upload-image', 'uploadImage');
                })
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('contact_information');
                });
            $server->resource('users', UserController::class)
                ->actions(function (ActionRegistrar $actions) {
                    $actions->get('me');
                    $actions->get('token');
                });
            $server->resource('images', ImageController::class);
            $server->resource('user-settings', UserSettingController::class);
            $server->resource('customer-settings', CustomerSettingController::class);
            $server->resource('contact-informations', ContactInformationController::class);
            $server->resource('easypost-credentials', EasypostCredentialController::class);
            $server->resource('webshipper-credentials', WebshipperCredentialController::class);
            $server->resource('order-channels', OrderChannelController::class)->actions(function (ActionRegistrar $actions) {
                $actions->get('/available-connections', 'availableConnections');
                $actions->get('/connection-fields/{type}', 'connectionFields');
                $actions->withId()->get('/order-channel', 'orderChannel');
                $actions->withId()->post('/update-source-configuration', 'updateSourceConfiguration');
                $actions->withId()->post('/recreate-order-channel-webhooks', 'recreateOrderChannelWebhooks');
                $actions->withId()->post('/create-packiyo-webhooks/{objectType}/{operation}', 'createPackiyoWebhook');
                $actions->withId()->post('/enable-scheduler', 'enableScheduler');
                $actions->withId()->post('/disable-scheduler', 'disableScheduler');
                $actions->withId()->post('/sync-products', 'syncProducts');
                $actions->withId()->post('/sync-inventories', 'syncInventories');
                $actions->withId()->post('/sync-order-by-number/{number}', 'syncOrderByNumber');
                $actions->withId()->post('/sync-orders-by-date/{order}', 'syncOrdersByDate');
                $actions->withId()->post('/sync-shipments/{syncFrom}', 'syncShipments');
                $actions->withId()->post('/sync-product-by-product-id/{productId}', 'syncProductByProductId');
                $actions->withId()->post('/sync-product-by-product-sku/{productSku}', 'syncProductByProductSku');
                $actions->withId()->post('/remove-order-channel-webhook/{id}', 'removeOrderChannelWebhook');
                $actions->post('/check-name/{customer}/{name}', 'checkOrderChannel');
                $actions->post('/get-oauth-url', 'getOauthUrl');
                $actions->post('/connect-commerce-with-oauth', 'checkOrderChannel');
                $actions->post('/connect', 'connect');
            });

            $server->resource('automatable-operations')->readOnly()
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasMany('supported_events')->readOnly();
                });
            $server->resource('automatable-events')->readOnly();
            $server->resource('automation-condition-types')->readOnly();
            $server->resource('automation-action-types')->readOnly();
            $server->resource('automations', AutomationController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                    $relationships->hasMany('applies_to_customers');
                    $relationships->hasMany('actions');
                    $relationships->hasMany('conditions');
                });

            $server->resource('order-automations', OrderAutomationController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                    $relationships->hasMany('applies_to_customers');
                    $relationships->hasMany('actions');
                    $relationships->hasMany('conditions');
                });
            $server->resource('purchase-order-automations', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('customer')->readOnly();
                    $relationships->hasMany('applies_to_customers');
                    $relationships->hasMany('actions');
                    $relationships->hasMany('conditions');
                });
            $server->resource('order-text-field-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('order-line-item-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                    $relationships->hasMany('matches_products')->readOnly();
                });
            $server->resource('order-tags-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('order-items-tags-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('order-text-pattern-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('ship-to-country-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('order-is-manual-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('order-number-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('sales-channel-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('quantity-distinct-sku-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });

            $server->resource('ship-to-customer-name-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('ship-to-country-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('ship-to-state-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-shipping-method-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                    $relationships->hasOne('shipping_method')->readOnly();
                });
            $server->resource('charge-ad-hoc-rate-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
             $server->resource('add-line-item-actions', JsonApiController::class)
                 ->relationships(static function (Relationships $relationships) {
                     $relationships->hasOne('automation')->readOnly();
                     $relationships->hasOne('products')->readOnly();
                 });
            $server->resource('set-shipping-box-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                    $relationships->hasOne('shipping-box')->readOnly();
                });
            $server->resource('set-packing-dimensions-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                    $relationships->hasOne('shipping-box')->readOnly();
                });
            $server->resource('set-date-field-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-priority-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-operator-hold-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-allocation-hold-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-text-field-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-fraud-hold-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-payment-hold-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('add-gift-note-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-incoterms-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('set-warehouse-actions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('quantity-distinct-sku-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('quantity-order-items-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });

            $server->resource('order-tags-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('shipping-option-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('subtotal-order-amount-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('total-order-amount-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
            $server->resource('order-weight-conditions', JsonApiController::class)
                ->relationships(static function (Relationships $relationships) {
                    $relationships->hasOne('automation')->readOnly();
                });
        });
});

Route::post('login', 'Api\LoginController@authenticate')->name('login.authenticate');


/** storefront Api */

// Domain-based or subdomain-based
Route::prefix('storefront')
    ->middleware('tenant')
    ->group(function () {
        Route::get('/', [HomeController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/product_search', [ProductController::class, 'productSearch']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/tag_products/{tagSlug}', [ProductController::class, 'getProductsByTag']);

        Route::post('/orders', [OrderController::class, 'store']);
        Route::post('/cart', [CartController::class, 'store']);
        Route::get('/cart/{cartToken}', [CartController::class, 'show']);
        Route::put('/cart/{cartToken}', [CartController::class, 'update']);

        Route::post('/checkout', [OrderController::class, 'checkout']);
    });

// Slug-based
Route::prefix('storefront/{tenantSlug}')
    ->middleware('tenant')
    ->group(function () {
        Route::get('/', [HomeController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/product_search', [ProductController::class, 'productSearch']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/tags/{tagSlug}/products', [ProductController::class, 'getProductsByTag']);

        Route::post('/orders', [OrderController::class, 'store']);
        Route::post('/cart', [CartController::class, 'store']);
        Route::get('/cart/{cartToken}', [CartController::class, 'show']);
        Route::put('/cart/{cartToken}', [CartController::class, 'update']);

        Route::post('/checkout', [OrderController::class, 'checkout']);
    });