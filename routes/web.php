<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect(RouteServiceProvider::HOME);
    }

    return redirect('login');
})->name('welcome');

Auth::routes(['register' => false]);

Route::get('pricing', 'PageController@pricing')->name('page.pricing');
Route::get('lock', 'PageController@lock')->name('page.lock');

Route::group(['middleware' => ['auth', 'active']], static function () {
    Route::group(['middleware' => 'is-admin'], static function () {
        Route::get('features', 'FeatureController@show')->name('features.show');
        Route::post('features', 'FeatureController@update')->name('features.update');
    });

    Route::get('dashboard', 'HomeController@index')->name('home');
    Route::post('search', 'SearchController@getSearch')->name('search.form');
    Route::get('search/{keyword}', 'SearchController@index')->name('search');

    // widgets stuff
    Route::get('dashboard/orders_revenue', 'HomeController@totalRevenue')->name('dashboard.orders_revenue');
    Route::get('dashboard/purchase_orders_received', 'HomeController@purchaseOrdersReceived')->name('dashboard.purchase_orders_received');
    Route::get('dashboard/late_orders', 'HomeController@lateOrders')->name('dashboard.late_orders');
    Route::get('orders/orders_by_cities', 'HomeController@ordersByCities');

    Route::get('site/filterCountries', 'SiteController@filterCountries')->name('site.filterCountries');

    Route::post('bulk_print', 'BulkPrintingController@bulkPrint')->name('bulk_print');

    Route::get('orders/orders_by_cities_limited', 'HomeController@ordersByCities');
    Route::get('orders/orders_by_country', 'HomeController@ordersByCountry');
    Route::get('orders/orders_received_count', 'HomeController@ordersReceivedCalc');
    Route::get('orders/orders_shipped_count', 'HomeController@shipmentsCalc');

    Route::get('returns/returns-count', 'HomeController@returnsCalc');
    Route::get('purchase_orders/coming_in', 'HomeController@purchaseOrdersCalc');
    Route::get('purchase_orders/quantity_calc', 'HomeController@purchaseOrdersQuantityCalc');
    Route::get('image_delete/{image}', 'HomeController@deleteImage')->name('image_delete');

	Route::resource('role', 'RoleController', ['except' => ['show', 'destroy']]);
    Route::get('user/data-table', 'UserController@dataTable')->name('user.dataTable');
    Route::get('user/getCreateUserModal', 'UserController@getCreateUserModal')->name('user.getCreateUserModal');
    Route::get('user/getEditUserModal/{user?}', 'UserController@getEditUserModal')->name('user.getEditUserModal');
    Route::get('user/{user}/delete', 'UserController@destroy')->name('user.destroy');
    Route::get('user/{user}/disable', 'UserController@disable')->name('user.disable');
    Route::get('user/{user}/enable', 'UserController@enable')->name('user.enable');
    Route::resource('user', 'UserController', ['except' => ['show']]);

    Route::group(['middleware' => '3pl'], static function () {
        Route::get('rate_cards/data_table', 'RateCardController@dataTable')->name('rate_cards.data_table');
        Route::get('rate_cards/fees_data_table', 'RateCardController@feesDataTable')->name('rate_cards.fees_data_table');
        Route::resource('rate_cards', 'RateCardController');
        Route::post('rate_cards/{id}/clone', 'RateCardController@clone')->name('rate_cards.clone');

        Route::get('bulk_invoice_batches/data_table', 'BulkInvoiceBatchController@dataTable')->name('bulk_invoice_batches.data_table');
        Route::get('bulk_invoice_batches/{bulk_invoice_batch}/export', 'InvoiceController@exportBatchInvoiceToCsv')->name('bulk_invoice_batches.export');
        Route::patch('bulk_invoice_batches/{bulk_invoice_batch}/finalize', 'BulkInvoiceBatchController@finalize')->name('bulk_invoice_batches.finalize');
        Route::patch('bulk_invoice_batches/{bulk_invoice_batch}/recalculate', 'BulkInvoiceBatchController@recalculate')->name('bulk_invoice_batches.recalculate');
        Route::get('bulk_invoice_batches/{bulk_invoice_batch}/items_data_table', 'BulkInvoiceBatchItemsDataTableController')->name('bulk_invoice_batches.items_data_table');

        Route::get('bulk_invoice_batches/{bulk_invoice_batch}/customers/{customer}/billing_rate/available_ad_hoc_rates', 'CustomerAdHocRatesController@index')->name('bulk_invoice_batches.customers.billing_rate.available_ad_hoc_rates');
        Route::post('bulk_invoice_batches/{bulk_invoice_batch}/invoices/ad_hoc', 'CustomerAdHocRatesController@store')->name('bulk_invoice_batches.invoices.ad_hoc');
        Route::get('bulk_invoice_batches/{bulk_invoice_batch}/rate_cards/{rate_card}/customers', 'BulkInvoiceBatchController@customersByRateCard')->name('bulk_invoice_batches.rate_cards.customers');
        Route::resource('bulk_invoice_batches', 'BulkInvoiceBatchController')->only(['index', 'store', 'destroy', 'edit']);

        Route::get('billings', 'BillingController@index')->name('billings.index');
        Route::get('billings/customers', 'BillingController@customers')->name('billings.customers');
        Route::get('billings/customers/data-table', 'BillingController@customersDataTable')->name('billings.customers.data_table');
        Route::get('billings/customers/{customer}/invoices', 'BillingController@customerInvoicesEdit')->name('billings.customer_invoices');
        Route::get('billings/customers/{customer}/invoices/data_table', 'BillingController@customerInvoicesDataTable');
        Route::get('billings/customers/{customer}/invoices/{invoice?}/items', 'BillingController@customerInvoiceLineItems')->name('billings.customer_invoice_line_items');
        Route::get('billings/customers/{customer}/invoices/{invoice}/items_data_table', 'BillingController@customerInvoiceLineItemsDataTable')->name('billings.customer_invoice_line_items_data_table');

        Route::get('billings/invoices', 'BillingController@invoices')->name('billings.invoices');
        Route::get('billings/invoices/data_table', 'BillingController@invoicesDataTable');

        Route::get('billings/rate_cards', 'BillingController@rateCards')->name('billings.rate_cards');
        Route::get('billings/exports', 'BillingController@exports')->name('billings.exports');

        Route::get('billing_rates/data_table', 'BillingRateController@dataTable');
        Route::get('billing_rates', 'BillingRateController@index');
        Route::get('billing_rates/rate_card/{rate_card}/add_rate/{type}', 'BillingRateController@create')->name('billing_rates.create');
        Route::get('billing_rates/rate_card/{rate_card}/billing_rate/{billing_rate}/edit', 'BillingRateController@edit')->name('billing_rates.edit');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/recurring', 'BillingRateController@recurringStore')->name('billing_rates.recurring.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/recurring', 'BillingRateController@recurringUpdate')->name('billing_rates.recurring.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/receiving_by_hour', 'BillingRateController@receivingByHourStore')->name('billing_rates.receiving_by_hour.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/receiving_by_hour', 'BillingRateController@receivingByHourUpdate')->name('billing_rates.receiving_by_hour.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/receiving_by_po', 'BillingRateController@receivingByPoStore')->name('billing_rates.receiving_by_po.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/receiving_by_po', 'BillingRateController@receivingByPoUpdate')->name('billing_rates.receiving_by_po.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/receiving_by_item', 'BillingRateController@receivingByItemStore')->name('billing_rates.receiving_by_item.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/receiving_by_item', 'BillingRateController@receivingByItemUpdate')->name('billing_rates.receiving_by_item.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/receiving_by_line', 'BillingRateController@receivingByLineStore')->name('billing_rates.receiving_by_line.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/receiving_by_line', 'BillingRateController@receivingByLineUpdate')->name('billing_rates.receiving_by_line.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/storage_by_location', 'BillingRateController@storageByLocationStore')->name('billing_rates.storage_by_location.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/storage_by_location', 'BillingRateController@storageByLocationUpdate')->name('billing_rates.storage_by_location.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/storage_by_product', 'BillingRateController@storageByProductStore')->name('billing_rates.storage_by_product.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/storage_by_product', 'BillingRateController@storageByProductUpdate')->name('billing_rates.storage_by_product.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/shipments_by_box', 'BillingRateController@shipmentByBoxtStore')->name('billing_rates.shipments_by_box.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/shipments_by_box', 'BillingRateController@shipmentByBoxUpdate')->name('billing_rates.shipments_by_box.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/shipments_by_shipping_label', 'BillingRateController@shipmentsByShippingLabelStore')->name('billing_rates.shipments_by_shipping_label.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/shipments_by_shipping_label', 'BillingRateController@shipmentsByShippingLabelUpdate')->name('billing_rates.shipments_by_shipping_label.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/packaging_rate', 'BillingRateController@packagingRateStore')->name('billing_rates.packaging_rate.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/packaging_rate', 'BillingRateController@packagingRateUpdate')->name('billing_rates.packaging_rate.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/shipments_by_picking_rate', 'BillingRateController@shipmentsByPickingRateStore')->name('billing_rates.shipments_by_picking_rate.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/shipments_by_picking_rate', 'BillingRateController@shipmentsByPickingRateUpdate')->name('billing_rates.shipments_by_picking_rate.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/shipments_by_picking_rate_v2', 'BillingRateController@shipmentsByPickingRateStoreV2')->name('billing_rates.shipments_by_picking_rate_v2.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/shipments_by_picking_rate_v2', 'BillingRateController@shipmentsByPickingRateUpdateV2')->name('billing_rates.shipments_by_picking_rate_v2.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/shipments_by_pickup_picking_rate', 'BillingRateController@shipmentsByPickupPickingRateStore')->name('billing_rates.shipments_by_pickup_picking_rate.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/shipments_by_pickup_picking_rate', 'BillingRateController@shipmentsByPickupPickingRateUpdate')->name('billing_rates.shipments_by_pickup_picking_rate.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/returns', 'BillingRateController@returnsStore')->name('billing_rates.returns.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/returns', 'BillingRateController@returnsUpdate')->name('billing_rates.returns.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/ad_hoc', 'BillingRateController@adHocStore')->name('billing_rates.ad_hoc.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/ad_hoc', 'BillingRateController@adHocUpdate')->name('billing_rates.ad_hoc.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/shipping_rates', 'BillingRateController@shippingRatesStore')->name('billing_rates.shipping_rates.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/shipping_rates', 'BillingRateController@shippingRatesUpdate')->name('billing_rates.shipping_rates.update');

        Route::post('billing_rates/rate_card/{rate_card}/add_rate/purchase_order', 'BillingRateController@purchaseOrderStore')->name('billing_rates.purchase_order.store');
        Route::post('billing_rates/rate_card/{rate_card}/update_rate/{billing_rate}/purchase_order', 'BillingRateController@purchaseOrderUpdate')->name('billing_rates.purchase_order.update');

        Route::post('billing_rates/rate_card/{rate_card}/import/{type}', 'BillingRateController@import')->name('billing_rates.import');
        Route::get('billing_rates/rate_card/{rate_card}/export/{type}', 'BillingRateController@export')->name('billing_rates.export');

        Route::delete('billing_rates/destroy/{billing_rate}/rate_card/{rate_card}/', 'BillingRateController@destroy')->name('billing_rates.destroy');
        Route::get('billing_rates/carriers_and_methods', 'BillingRateController@carriersAndMethods');
        Route::get('billing_rates/customers_and_shipping_boxes', 'BillingRateController@customerAndShippingBox');
        Route::get('billing_rates/{customer}/shipping_boxes', 'BillingRateController@getCustomerShippingBoxes');
        Route::get('billing_rates/{billing_rate}/carrier/{shipping_carrier}', 'BillingRateController@getCarrierMethods');

        Route::post('invoices/batch_store', 'InvoiceController@batchStore')->name('invoices.batchStore');
        Route::post('invoices/batch_recalculate', 'InvoiceController@batchRecalculate')->name('invoices.batchRecalculate');
        Route::get('invoices/{invoice}/generate_csv', 'InvoiceController@generateCsv')->name('invoices.generateCsv');
        Route::get('invoices/{invoice}/download_generate_csv', 'InvoiceController@downloadGeneratedCsv')->name('invoices.downloadGeneratedCsv');
        Route::get('invoices/{invoice}/export_csv', 'InvoiceController@exportToCsv')->name('invoices.exportCsv');
        Route::post('invoices/{invoice}/ad_hoc', 'InvoiceController@adHoc')->name('invoices.ad_hoc');
        Route::get('invoices/{invoice}/getEditInvoiceLineItemForm/{invoice_line_item}', 'InvoiceController@getEditInvoiceLineItemForm')->name('invoices.getEditInvoiceLineItemForm');
        Route::post('invoices/{invoice}/update_ad_hoc/{invoice_line_item}', 'InvoiceController@updateAdHoc')->name('invoices.update_ad_hoc');
        Route::delete('invoices/{invoice}/delete_ad_hoc/{invoice_line_item}', 'InvoiceController@deleteAdHoc')->name('invoices.delete_ad_hoc');
        Route::post('invoices/{invoice}/recalculate', 'InvoiceController@recalculate')->name('invoices.recalculate');

        Route::post('invoices/{invoice}/finalize', 'InvoiceController@finalize')->name('invoices.finalize');
        Route::get('invoices/{invoice}/export_invoice_summary', 'InvoiceController@exportInvoiceSummary')->name('invoices.export_invoice_summary');
        Route::get('invoices/export_invoice_lines', 'InvoiceController@exportInvoiceLines')->name('invoices.export_invoice_lines');
        Route::get('invoices/export_invoice_header', 'InvoiceController@exportInvoiceHeader')->name('invoices.export_invoice_header');
        Route::get('invoices/{invoice}/export_invoice_pdf', 'InvoiceController@exportInvoicePDF')->name('invoices.export_invoice_pdf');
        Route::resource('invoices', 'InvoiceController', ['except' => ['create', 'edit', 'update', 'show']]);
    });

    Route::get('profile', ['as' => 'profile.edit', 'uses' => 'ProfileController@edit']);
    Route::get('profile/activity', ['as' => 'profile.activity', 'uses' => 'ProfileController@activity']);
    Route::get('profile/activity/data-table', ['as' => 'profile.activity.datatable', 'uses' => 'ProfileController@dataTableActivity']);
	Route::put('profile', ['as' => 'profile.update', 'uses' => 'ProfileController@update']);
	Route::put('profile/password', ['as' => 'profile.password', 'uses' => 'ProfileController@password']);
	Route::post('profile/access_token', 'ProfileController@createAccessToken')->name('profile.create_access_token');
	Route::delete('profile/access_tokens/{token}', 'ProfileController@deleteAccessToken')->name('profile.delete_access_token');

    Route::get('customer/data-table', 'CustomerController@dataTable')->name('customer.dataTable');
    Route::delete('customer/{customer}/detachUser/{user}', 'CustomerController@detachUser')->name('customer.detachUser');

    Route::get('customer/{customer}/users', 'CustomerController@edit')->name('customer.editUsers');
    Route::post('customer/{customer}/users/update', 'CustomerController@updateUsers')->name('customer.updateUsers');

    Route::get('customer/{customer}/rate_cards/edit', 'CustomerController@editRateCards')->name('customers.rate_cards.edit');
    Route::post('customer/{customer}/rate_cards/update', 'CustomerController@updateRateCards')->name('customers.rate_cards.update');

    Route::get('customers/{customer}/easypost_credentials/{easypost_credential}/batches', 'EasypostCredentialController@batches')->name('customers.easypost_credentials.batches');
    Route::resource('customers.easypost_credentials', 'EasypostCredentialController');

    Route::resource('customers.webshipper_credentials', 'WebshipperCredentialController');
    Route::resource('customers.pathao_credentials', 'PathaoCredentialController');

    Route::get('customer/{customer}/cssOverrides', 'CustomerController@edit')->name('customer.cssOverrides');

    Route::get('customer/{customer}/filterUsers', 'CustomerController@filterusers')->name('customer.filterUsers');
    Route::get('customer/{customer}/dimension_units', 'CustomerController@getDimensionUnits')->name('customer.dimensionUnits');
    Route::resource('customer', 'CustomerController');

    // TODO: remove after lots are no longer a pain
    Route::get('product/missing-lots', 'ProductController@missingLots')->name('product.missing_lots');
    Route::get('product/data-table', 'ProductController@dataTable')->name('product.dataTable');
    Route::get('product/filterCustomers', 'ProductController@filterCustomers')->name('product.filterCustomers');
    Route::get('product/filterSuppliers/{customer?}', 'ProductController@filterSuppliers')->name('product.filterSuppliers');
    Route::get('product/filter/{customer?}', 'ProductController@filter')->name('product.filter');
    Route::get('product/filterBySupplier/{supplier?}', 'ProductController@filterBySupplier')->name('product.filterBySupplier');
    Route::get('product/getProduct/{product}', 'ProductController@getItem')->name('product.getProduct');
    Route::get('product/deleteKitProduct/{product}', 'ProductController@deleteItem')->name('product.deleteKitProduct');
    Route::post('product/remove_component/{kit}/{component}', 'ProductController@removeComponent')->name('product.remove_component');
    Route::post('product/updateKitProduct/{product}', 'ProductController@updateItem')->name('product.updateKitProduct');
    Route::get('product/{product}/getLog', 'ProductController@getLog')->name('product.getLog');
    Route::get('product/filterLocations/{product}', 'ProductController@filterLocations')->name('product.filterLocations');
    Route::post('product/transfer/{product}', 'ProductController@transfer')->name('product.transfer');
    Route::post('product/removeFromLocation/{product}', 'ProductController@removeFromLocation')->name('product.removeFromLocation');
    Route::post('product/addToLocation/{product}', 'ProductController@addToLocation')->name('product.addToLocation');
    Route::post('product/{product}/change_location_lot', 'ProductController@changeLocationLot')->name('product.change_location_lot');
    Route::get('product/{product?}/filterKitProducts/{customer?}', 'ProductController@filterKitProducts')->name('product.filterKitProducts');
    Route::get('product/filterKitProducts/{customer?}', 'ProductController@filterKitProducts')->name('product.filterKitProducts.all');
    Route::get('product/locations', 'LocationController@productLocations')->name('productLocation.index');
    Route::get('locations/empty', 'LocationController@getEmptyLocations')->name('location.empty');
    Route::delete('locations/empty', 'LocationController@deleteEmptyLocations')->name('location.empty.delete');
    Route::get('product/locations/{product}', 'ProductController@locationsDataTable')->name('product.locationsDataTable');
    Route::get('product/{product}/locations', 'ProductController@getLocations')->name('productLocation.getLocations');
    Route::post('product/import/csv', 'ProductController@importCsv')->name('product.importCsv');
    Route::post('product/export/csv', 'ProductController@exportCsv')->name('product.exportCsv');
    Route::post('product/{product}/recover', 'ProductController@recover')->name('product.recover');
    Route::get('product/search/{keyword}', 'ProductController@index')->name('product.search');
    Route::get('product/order-items-data-table/{product}', 'ProductController@orderItemsDataTable')->name('product.orderItemsDataTable');
    Route::get('product/shipped-items-data-table/{product}', 'ProductController@shippedItemsDataTable')->name('product.shippedItemsDataTable');
    Route::get('product/tote-items-data-table/{product}', 'ProductController@toteItemsDataTable')->name('product.toteItemsDataTable');
    Route::get('product/kits-data-table/{product}', 'ProductController@kitsDataTable')->name('product.kitsDataTable');
    Route::post('product/bulkSelectionStatus', 'ProductController@getBulkSelectionStatus')->name('product.bulk_status');
    Route::post('product/bulk-edit', 'ProductController@bulkEdit')->name('product.bulk_edit');
    Route::post('product/bulk-delete', 'ProductController@bulkDelete')->name('product.bulk_delete');
    Route::post('product/bulk-recover', 'ProductController@bulkRecover')->name('product.bulk_recover');
    Route::resource('product', 'ProductController');
    Route::post('product/{product}/barcodes', 'ProductController@barcodes')->name('product.barcodes');
    Route::get('product/{product}/customer-printers', 'ProductController@getCustomerPrinters')->name('product.getCustomerPrinters');
    Route::get('product/{product}/barcode-pdf', 'ProductController@getBarcodePDF')->name('product.getBarcodePDF');
    Route::get('product/{product}/filterLots', 'ProductController@filterLots')->name('product.filterLots');

    Route::get('/orders/get_order_status/{customer?}', 'OrderController@getOrderStatus')->name('order.filterOrderStatuses');
    Route::post('order/countRecords', 'OrderController@countRecords')->name('order.countRecords');
    Route::post('order/bulkOrderStatus', 'OrderController@getBulkOrderStatus')->name('order.bulkOrderStatus');
    Route::get('order/{order}/delete', 'OrderController@destroy')->name('order.filterCustomers');
    Route::post('order/{order}/cancel', 'OrderController@cancelOrder')->name('order.cancel');
    Route::post('order/{order}/uncancel', 'OrderController@uncancelOrder')->name('order.uncancel');
    Route::post('order/{order}/reship', 'OrderController@reship')->name('order.reship');
    Route::post('order/{order}/{orderItem}/cancel', 'OrderController@cancelOrderItem')->name('orderItem.cancel');
    Route::post('order/{order}/{orderItem}/uncancel', 'OrderController@uncancelOrderItem')->name('orderItem.uncancel');
    Route::post('order/{order}/fulfill', 'OrderController@fulfillOrder')->name('order.fulfill');
    Route::post('order/{order}/unfulfill', 'OrderController@unfulfillOrder')->name('order.unfulfill');
    Route::post('order/{order}/archive', 'OrderController@archiveOrder')->name('order.archive');
    Route::post('order/{order}/unarchive', 'OrderController@unarchiveOrder')->name('order.unarchive');
    Route::post('order/{order}/unlock', 'OrderController@unlockOrder')->name('order.unlock');
    Route::get('order/getOrder/{order}', 'OrderController@getItem')->name('product.getOrder');
    Route::get('order/getKitItems/{orderItem}', 'OrderController@getKitItems')->name('product.getKitItems');
    Route::get('order/edit/{order}', 'OrderController@edit')->name('order.edit');
    Route::get('order/filterCustomers', 'OrderController@filterCustomers')->name('order.filterCustomers');
    Route::post('order/import/csv', 'OrderController@importCsv')->name('order.importCsv');
    Route::post('order/export/csv', 'OrderController@exportCsv')->name('order.exportCsv');
    Route::get('order/filterProducts/{customer?}', 'OrderController@filterProducts')->name('order.filterProducts');
    Route::get('order/getOrderReturnForm/{order}', 'OrderController@getOrderReturnForm');
    Route::get('order/data-table', 'OrderController@dataTable')->name('order.dataTable');
    Route::post('order/bulk-edit', 'OrderController@bulkEdit')->name('order.bulkEdit');
    Route::post('order/bulk-cancel', 'OrderController@bulkCancel')->name('order.bulk_cancel');
    Route::post('order/bulk-mark-as-fulfilled', 'OrderController@bulkFulfill')->name('order.bulk_mark_as_fulfilled');
    Route::post('order/bulk-archive', 'OrderController@bulkArchive')->name('order.bulk_archive');
    Route::post('order/bulk-unarchive', 'OrderController@bulkUnarchive')->name('order.bulk_unarchive');
    Route::put('order/{order}/return', 'OrderController@return')->name('order.return');
    Route::get('order/{order}/order_channel_payload', 'OrderController@showOrderChannelPayload')->name('order.order_channel_payload');
    Route::get('order/{order}/raw_data', 'OrderController@showRawData')->name('order.raw_data');

    Route::get('order/get_shipping_address/{order}', 'OrderController@getShippingAddress')->name('order.get_shipping_address');

    Route::get('order/{order}/webshipper/shipping_rates', 'OrderController@webshipperShippingRates');
    Route::get('order/search/{keyword}', 'OrderController@index')->name('order.search');

    Route::post('shipments/{shipment}/void', 'ShipmentController@void')->name('shipments.void');

    Route::resource('order', 'OrderController');

    Route::get('order_status/filterCustomers', 'OrderStatusController@filterCustomers')->name('order_status.filterCustomers');
    Route::get('order_status/data-table', 'OrderStatusController@dataTable')->name('order_status.dataTable');
    Route::resource('order_status', 'OrderStatusController');

    Route::get('shipping_box/filterCustomers', 'ShippingBoxController@filterCustomers')->name('shipping_box.filterCustomers');
    Route::get('shipping_box/data-table', 'ShippingBoxController@dataTable')->name('shipping_box.dataTable');
    Route::post('shipping_box/import/csv', 'ShippingBoxController@importCsv')->name('shipping_box.importCsv');
    Route::post('shipping_box/export/csv', 'ShippingBoxController@exportCsv')->name('shipping_box.exportCsv');
    Route::resource('shipping_box', 'ShippingBoxController');

    Route::prefix('packing')->middleware('3pl')->group(function() {
        Route::prefix('single_order_shipping')->group(function() {
            Route::get('/', 'PackingController@singleOrderShippingDataTable')->name('packing.single_order_shipping.dataTable');
            Route::get('{order}', 'PackingController@singleOrderShipping')->name('packing.single_order_shipping');
            Route::post('ship/{order}', 'PackingController@singleOrderShip')->name('packing.ship');
            Route::get('barcode_search/{barcode}', 'PackingController@barcodeSearch')->name('packing.barcodeSearch');
        });

        Route::prefix('bulk_shipping')->group(function() {
            Route::get('/', 'BulkShippingController@index')->name('bulk_shipping.index');
            Route::get('batches', 'BulkShippingController@batches')->name('bulk_shipping.batches');
            Route::get('in_progress', 'BulkShippingController@inProgress')->name('bulk_shipping.inProgress');
            Route::get('{bulkShipBatch}', 'PackingController@bulkShipBatchShipping')->name('bulk_shipping.shipping');
            Route::post('{bulkShipBatch}', 'PackingController@bulkShipBatchShip')->name('bulk_shipping.ship');
            // TODO: These URL bits should be snake_case.
            Route::get('bulkShipBatchProgress/{bulkShipBatch}', 'PackingController@bulkShipBatchProgress')->name('bulk_shipping.bulkShipBatchProgress');
            Route::get('remove/{bulkShipBatch}/{order}', 'PackingController@removeBatchOrder')->name('bulk_shipping.removeBatchOrder');
            Route::post('close/{bulkShipBatch}', 'PackingController@closeBulkShipBatch')->name('bulk_shipping.closeBulkShipBatch');
            Route::post('markAsPrinted/{bulkShipBatch}', 'BulkShippingController@markAsPrinted')->name('bulk_shipping.markAsPrinted');
            Route::post('markAsPacked/{bulkShipBatch}', 'BulkShippingController@markAsPacked')->name('bulk_shipping.markAsPacked');
            Route::post('unlock/{bulkShipBatch}', 'BulkShippingController@unlock')->name('bulk_shipping.unlock');
            Route::get('{bulkShipBatch}/data-table', 'BulkShippingController@dataTable')->name('bulk_shipping.data_table');
        });

        Route::post('/{order}/shipping_rates', 'PackingController@getShippingRatesView')->name('packing.shipping_rates');

        Route::prefix('wholesale_shipping')->group(function() {
            Route::get('edi_labels/{shipment}', 'PackingController@getEDILabels')->name('packing.getEDILabels');
            Route::post('edi_labels/{shipment}/print', 'PackingController@printEDILabels')->name('packing.printEDILabels');
        });

    });

    Route::resource('packing', 'PackingController')->middleware('3pl');

    Route::get('purchase_order_status/filterCustomers', 'PurchaseOrderStatusController@filterCustomers')->name('purchase_order_status.filterCustomers');
    Route::resource('purchase_order_status', 'PurchaseOrderStatusController');

    Route::get('kits/data-table', 'KitController@dataTable')->name('kits.datatable');
    Route::post('kits/import/csv', 'KitController@importCsv')->name('kits.import');
    Route::post('kits/export/csv', 'KitController@exportCsv')->name('kits.export');
    Route::resource('kits', 'KitController');

    Route::get('purchase_orders/data-table', 'PurchaseOrderController@dataTable')->name('purchase_order.dataTable');
    Route::get('/purchase_orders/get_order_status/{customer}', 'PurchaseOrderController@getOrderStatus');
    Route::get('purchase_orders/filterProducts', 'PurchaseOrderController@filterProducts')->name('purchase_order.filterProducts');
    Route::get('purchase_orders/filterCustomers', 'PurchaseOrderController@filterCustomers')->name('purchase_order.filterCustomers');
    Route::get('purchase_orders/filterLocations/{warehouse}', 'PurchaseOrderController@filterLocations')->name('purchase_order.filterLocations');
    Route::get('purchase_orders/filterWarehouses/{customer?}', 'PurchaseOrderController@filterWarehouses')->name('purchase_order.filterWarehouses');
    Route::get('purchase_orders/filterSuppliers/{customer?}', 'PurchaseOrderController@filterSuppliers')->name('purchase_order.filterSuppliers');
    Route::get('purchase_orders/getPurchaseOrderModal/{purchaseOrder}', 'PurchaseOrderController@getPurchaseOrderModal')->name('purchase_order.getPurchaseOrderModal');
    Route::get('purchase_orders/receive/{purchaseOrder}', 'PurchaseOrderController@receivePurchaseOrder')->name('purchase_order.receive')->middleware('3pl');
    Route::post('purchase_orders/update/{purchaseOrder}', 'PurchaseOrderController@updatePurchaseOrder')->name('purchase_order.updatePurchaseOrder');
    Route::post('purchase_orders/close/{purchaseOrder}', 'PurchaseOrderController@close')->name('purchase_order.close');
    Route::get('purchase_orders/getRejectedPurchaseOrderItemModal/{purchaseOrderItem}', 'PurchaseOrderController@getRejectedPurchaseOrderItemModal')->name('purchase_order.getRejectedPurchaseOrderItemModal');
    Route::post('purchase_orders/reject/{purchaseOrderItem}', 'PurchaseOrderController@reject')->name('purchase_order.reject');
    Route::get('purchase_orders/search/{keyword}', 'PurchaseOrderController@index')->name('purchase_orders.search');
    Route::post('purchase_orders/import/csv', 'PurchaseOrderController@importCsv')->name('purchase_order.importCsv');
    Route::post('purchase_orders/export/csv', 'PurchaseOrderController@exportCsv')->name('purchase_order.exportCsv');
    Route::post('purchase_orders/bulk-edit', 'PurchaseOrderController@bulkEdit')->name('purchase_order.bulkEdit');
    Route::resource('purchase_orders', 'PurchaseOrderController');

    Route::get('return/return-items-by-product', 'ReturnController@returnItemsByProduct')->name('return.returnItemsByProduct');
    Route::get('return/product-data-table/{product}', 'ReturnController@productDataTable')->name('return.productDataTable');
    Route::get('return/data-table', 'ReturnController@dataTable')->name('return.dataTable');
    Route::get('return/filterOrderProducts/{orderId}', 'ReturnController@filterOrderProducts')->name('return.filterOrderProducts');
    Route::get('return/filterOrders', 'ReturnController@filterOrders')->name('return.filterOrders');
    Route::get('return/filterStatuses', 'ReturnController@filterStatuses')->name('return.filterStatuses');
    Route::get('return/filterLocations', 'ReturnController@filterLocations')->name('return.filterLocations');
    Route::get('return/create/{order?}', 'ReturnController@create')->name('return.create');
    Route::get('return/status/{return}', 'ReturnController@status')->name('return.status');
    Route::put('return/status/{return}', 'ReturnController@statusUpdate')->name('return.statusUpdate');
    Route::post('return/bulk-edit', 'ReturnController@bulkEdit')->name('return.bulkEdit');
    Route::post('return/export/csv', 'ReturnController@exportCsv')->name('return.exportCsv');

    Route::get('return/create_from_tracking/{shipmentTracking}', 'ReturnController@createFromTracking')->name('return.createFromTracking');
    Route::get('return/search/{keyword}', 'ReturnController@index')->name('return.search');
    Route::get('return-by-tracking-number/{trackingNumber}', 'ReturnController@getReturnByTrackingNumber')->name('return.orderByReturnTracking');
    Route::resource('return', 'ReturnController', ['except' => ['create']]);

    Route::get('return_status/data-table', 'ReturnStatusController@dataTable')->name('return_status.dataTable');
    Route::resource('return_status', 'ReturnStatusController');

    Route::get('lot/data-table', 'LotController@dataTable')->name('lot.dataTable');
    Route::get('lot/filterLots', 'LotController@filterLots')->name('lot.filterLots');
    Route::resource('lot', 'LotController');

    Route::get('warehouses/data-table', 'WarehouseController@dataTable')->name('warehouse.dataTable');
    Route::get('warehouses/{warehouse}/edit/location', 'WarehouseController@edit')->name('warehouses.editWarehouseLocation');
    Route::post('warehouses/{warehouse}/addUsers', 'WarehouseController@addCustomers')->name('warehouse.addCustomers');
    Route::get('warehouses/filterCustomers', 'WarehouseController@filterCustomers')->name('warehouses.filterCustomers');
    Route::get('warehouses/getWarehouseModal/{warehouse?}', 'WarehouseController@getWarehouseModal')->name('warehouses.getWarehouseModal');
    Route::get('warehouses/filter/{customer?}', 'WarehouseController@filterWarehouses')->name('warehouses.filterWarehouses');
    Route::get('warehouses/{warehouse}/address', 'WarehouseController@getWarehouseAddress')->name('warehouses.getAddress');
    Route::resource('warehouses', 'WarehouseController', ['except' => ['show']]);

//    Session customer
    Route::get('user_customer/set/{customer}', 'UserController@setSessionCustomer')->name('user.setSessionCustomer');
    Route::get('user_customer/forget', 'UserController@removeSessionCustomer')->name('user.removeSessionCustomer');
    Route::get('user_customer/all', 'UserController@getCustomers')->name('user.getCustomers');
    Route::get('user_customer/3pl', 'UserController@get3plCustomers')->name('user.get3plCustomers');

    Route::get('task_type/data-table', 'TaskTypeController@dataTable')->name('task_type.dataTable');
    Route::get('task_type/filterCustomers', 'TaskTypeController@filterCustomers')->name('task_type.filterCustomers');
    Route::resource('task_type', 'TaskTypeController');

    Route::get('task/data-table', 'TaskController@dataTable')->name('task.dataTable');
    Route::get('task/filterUsers', 'TaskController@filterUsers')->name('task.filterUsers');
    Route::get('task/filterCustomers', 'TaskController@filterCustomers')->name('task.filterCustomers');
    Route::resource('task', 'TaskController');

    Route::get('supplier/data-table', 'SupplierController@dataTable')->name('supplier.dataTable');
    Route::get('supplier/filterCustomers', 'SupplierController@filterCustomers')->name('supplier.filterCustomers');
    Route::get('supplier/filterProducts/{customer}', 'SupplierController@filterProducts')->name('supplier.filterProducts');
    Route::get('supplier/filterByProduct/{product?}', 'SupplierController@filterByProduct')->name('supplier.filterByProduct');

    Route::post('supplier/export/csv', 'SupplierController@exportCsv')->name('supplier.exportCsv');
    Route::post('supplier/import/csv', 'SupplierController@importCsv')->name('supplier.importCsv')->middleware('3pl');
    Route::get('supplier/getVendorModal/{supplier}', 'SupplierController@getVendorModal')->name('supplier.getVendorModal');
    Route::resource('supplier', 'SupplierController');

    Route::get('profile/webhook/filterUsers', 'WebhookController@filterUsers')->name('webhook.filterUsers');
    Route::resource('profile/webhook', 'WebhookController');

    Route::get('locations/data-table', 'LocationController@dataTable')->name('location.dataTable');
    Route::get('locations/product/data-table', 'LocationController@productLocationDataTable')->name('productLocation.dataTable');
    Route::get('location/types/data-table', 'LocationTypeController@dataTable')->name('locationType.dataTable');
    Route::get('location/filterLocations', 'LocationController@filterLocations')->name('location.filterLocations');
    Route::get('location/filterProducts/{customer?}', 'LocationController@filterProducts')->name('location.filterProducts');

    Route::get('location/getLocationModal/{location?}', 'LocationController@getLocationModal')->name('location.getLocationModal');
    Route::post('location/product/import', 'LocationController@importInventory')->name('location.importInventory')->middleware('3pl');
    Route::post('location/product/export', 'LocationController@exportInventory')->name('location.exportInventory');
    Route::get('location/types/filter/{customer?}', 'LocationTypeController@getTypes')->name('location.types');
    Route::post('location/export/csv', 'LocationController@exportCsv')->name('location.exportCsv');
    Route::post('location/import/csv', 'LocationController@importCsv')->name('location.importCsv')->middleware('3pl');

    Route::resource('location', 'LocationController');
    Route::patch('location/{locationId}/product/{productId}/quantity', 'LocationController@adjustInventory')
        ->middleware('3pl')
        ->name('location.product.adjustInventory');

    Route::get('location/{location}/audit', 'LocationController@audit')->name('location.audit');
    Route::post('location/bulk-delete', 'LocationController@bulkDelete')->name('location.bulk_delete');

    Route::post('location_type/import/csv', 'LocationTypeController@importCsv')->name('location_type.importCsv');
    Route::post('location_type/export/csv', 'LocationTypeController@exportCsv')->name('location_type.exportCsv');
    Route::resource('location_type', 'LocationTypeController')->middleware('3pl');
    Route::post('location_type/bulk-delete', 'LocationTypeController@bulkDelete')->name('location_type.bulk_delete');

    Route::get('inventory_log/data-table', 'InventoryLogController@dataTable')->name('inventory_log.dataTable');
    Route::post('inventory_log/export', 'InventoryLogController@exportInventory')->name('inventory_log.exportInventory');
    Route::resource('inventory_log', 'InventoryLogController');

    Route::get('shipments/data-table', 'ShipmentController@dataTable')->name('shipment.dataTable');
    Route::get('shipment/filterOrderProductLocation/{productId}', 'ShipmentController@filterOrderProductLocation')->name('shipment.filterOrderProductLocation');
    Route::get('shipment/filterOrderProducts/{orderId}', 'ShipmentController@filterOrderProducts')->name('shipment.filterOrderProducts');
    Route::get('shipment/filterOrders', 'ShipmentController@filterOrders')->name('shipment.filterOrders');
    Route::get('shipment/{shipment}/reship/', 'ShipmentController@reship')->name('shipment.reship');
    Route::get('shipment/methods/', 'ShipmentController@methods')->name('shipment.methods');
    Route::resource('shipment', 'ShipmentController');
    Route::get('shipment/{shipment}/tracking/{shipmentTracking?}', 'ShipmentController@getShipmentTrackingModal')->name('shipment.tracking_modal');
    Route::post('shipment/{shipment}/tracking', 'ShipmentController@updateTracking')->name('shipment.tracking');

    Route::get('shipping_carrier/filterCustomers', 'ShippingCarrierController@filterCustomers')->name('shipping_carrier.filterCustomers');
    Route::get('shipping_carrier/data-table', 'ShippingCarrierController@dataTable')->name('shipping_carrier.dataTable');
    Route::get('shipping_carrier/tribird', 'ShippingCarrierController@getTribirdCarriers')->name('shipping_carrier.getTribirdCarriers');
    Route::get('shipping_carrier/tribird/{type}', 'ShippingCarrierController@getTribirdCarrierConfigurations')->name('shipping_carrier.getTribirdCarrierConfigurations');
    Route::post('shipping_carrier/{shipping_carrier}/disconnect', 'ShippingCarrierController@disconnectCarrier')->name('shipping_carrier.disconnectCarrier');
    Route::get('shipping_carrier/{shipping_carrier}/connect', 'ShippingCarrierController@connectCarrier')->name('shipping_carrier.connectCarrier');
    Route::resource('shipping_carrier', 'ShippingCarrierController');

    Route::resource('shipping_method', 'ShippingMethodController')->only(['index', 'edit', 'update']);
    Route::get('shipping_method/drop-points', 'ShippingMethodController@getDropPoints')->name('shipping_method.getDropPoints');
    Route::get('shipping_method/data-table', 'ShippingMethodController@dataTable')->name('shipping_method.dataTable');

    Route::get('shipping_method_mapping/data-table', 'ShippingMethodMappingController@dataTable')->name('shipping_method_mapping.dataTable');
    Route::get('shipping_method_mapping/filter-customers', 'ShippingMethodMappingController@filterCustomers')->name('shipping_method_mapping.filterCustomers');
    Route::get('shipping_method_mapping/filter-shipping-methods/{customer?}', 'ShippingMethodMappingController@filterShippingMethods')->name('shipping_method_mapping.filterShippingMethods');
    Route::get('shipping_method_mapping/create/{shipping_method_name?}', 'ShippingMethodMappingController@create')->name('shipping_method_mapping.create');
    Route::resource('shipping_method_mapping', 'ShippingMethodMappingController')->except(['create']);

    Route::get('shipping_carriers/{shipping_carrier}/shipping_methods', 'CustomerController@shippingMethods')->name('shipping_carriers.shipping_methods');

    Route::get('totes/data-table', 'ToteController@dataTable')->name('tote.dataTable');
    Route::get('totes/tote-items-data-table/{tote}', 'ToteController@toteItemsDataTable')->name('tote.toteItemsDataTable');

    Route::get('totes/filterWarehouses', 'ToteController@filterWarehouses')->name('tote.filterWarehouses');
    Route::get('totes/filterPickingCarts', 'ToteController@filterPickingCarts')->name('tote.filterPickingCarts');
    Route::post('totes/import/csv', 'ToteController@importCsv')->name('tote.importCsv');
    Route::post('totes/export/csv', 'ToteController@exportCsv')->name('tote.exportCsv');
    Route::post('tote/clear/{tote}', 'ToteController@clearItems')->name('tote.clear')->middleware('3pl');
    Route::post('tote/bulk-delete', 'ToteController@bulkDelete')->name('tote.bulk_delete');
    Route::resource('tote', 'ToteController')->middleware('3pl');

    Route::get('picking_carts/data-table', 'PickingCartController@dataTable')->name('pickingCart.dataTable');
    Route::get('picking_carts/filterWarehouses', 'PickingCartController@filterWarehouses')->name('pickingCart.filterWarehouses');
    Route::get('picking_carts/filterTotes', 'PickingCartController@filterTotes')->name('pickingCart.filterTotes');
    Route::resource('picking_carts', 'PickingCartController')->middleware('3pl');

    Route::get('address_book/data-table', 'AddressBookController@dataTable')->name('address_book.dataTable');
    Route::get('address_book/modal/{addressBook?}', 'AddressBookController@modal')->name('address_book.modal');
    Route::resource('address_book', 'AddressBookController');

    Route::get('edit_columns/update', 'EditColumnController@update')->name('editColumn.update');

    Route::post('user_settings/dashboard_settings', 'UserSettingController@dashboardSettingsUpdate')->name('user_settings.dashboard_settings');

    Route::post('user_widgets/save', 'UserWidgetController@createUpdate')->name('user_widgets.save');
    Route::get('user_widgets/get', 'UserWidgetController@getWidgets')->name('user_widgets.get_widgets')->middleware('widget-shortcode');
    Route::get('user_widgets/get_sales', 'UserWidgetController@getDashboardSalesWidget')->name('user_widgets.get_dashboard_sales');
    Route::get('user_widgets/get_top_selling_items', 'UserWidgetController@getDashboardTopSellingWidget')->name('user_widgets.get_top_selling_items');
    Route::get('user_widgets/get_info', 'UserWidgetController@getDashboardInfoWidget')->name('user_widgets.get_info');

    Route::get('user_settings/edit', 'UserSettingController@edit')->name('user_settings.edit');
    Route::put('user_settings/update', 'UserSettingController@update')->name('user_settings.update');
    Route::get('settings/manage_users', 'UserController@index')->name('settings.manageUsers');
    Route::get('settings/manage_carriers', 'UserWidgetController@getWidgets')->name('settings.manageCarriers');
    Route::get('settings/manage_stores', 'UserWidgetController@getWidgets')->name('settings.manageStores');
    Route::get('settings/add_card', 'UserWidgetController@getWidgets')->name('settings.addCard');
    Route::get('settings/notifications', 'UserWidgetController@getWidgets')->name('settings.notifications');

    Route::get('tag/filterInputTags', 'TagController@filterInputTags')->name('tag.filterInputTags');

    Route::get('printer', 'PrinterController@index')->name('printer.index')->middleware('3pl');
    Route::get('printer/data-table', 'PrinterController@dataTable')->name('printer.dataTable');
    Route::get('printer/{printer}/disable', 'PrinterController@disable')->name('printer.disable')->middleware('3pl');
    Route::get('printer/{printer}/enable', 'PrinterController@enable')->name('printer.enable')->middleware('3pl');

    Route::get('printer/{printer}/jobs', 'PrinterController@jobs')->name('printer.jobs')->middleware('3pl');
    Route::get('printer/{printer}/jobs-data-table', 'PrinterController@jobsDataTable')->name('printer.jobs.dataTable');
    Route::get('print_job/{printJob}/repeat', 'PrinterController@jobRepeat')->name('printer.job.repeat')->middleware('3pl');

    Route::get('location_layout', 'LocationLayoutController@customerIndex')->name('location_layout.customers.index')->can('viewAny', \App\Models\Customer::class);
    Route::get('location_layout/{customer}/warehouses', 'LocationLayoutController@warehouseIndex')->name('location_layout.warehouse.index')->can('view', 'customer');
    Route::get('location_layout/{warehouse}/locations', 'LocationLayoutController@locationIndex')->name('location_layout.location.index')->can('view', 'warehouse');
    Route::get('location_layout/{location}/products', 'LocationLayoutController@productIndex')->name('location_layout.product.index')->can('view', 'location');

    Route::get('account/settings', 'AccountController@settings')->name('account.settings');
    Route::get('account/settings/{customer}/payment-method', 'AccountController@paymentMethod')->name('account.payment-method');
    Route::get('account/settings/{customer}/billing-details', 'AccountController@billingDetails')->name('account.billing-details');
    Route::get('account/customer/{customer}/invoice/{invoice}', 'AccountController@downloadInvoice')->name('account.download-invoice');
    Route::get('account/upgrade/{customer}', 'PaymentController@upgrade')->name('account.upgrade');
    Route::post('account/cancel-subscription/{customer}', 'PaymentController@cancelSubscription')->name('account.cancel-subscription');
    Route::post('payment/store-method/{customer}', 'PaymentController@storePaymentMethod')->name('payment.storeMethod');
    Route::post('payment/billing-details/{customer}', 'PaymentController@updateBillingDetails')->name('payment.updateBillingDetails');

    Route::get('order_channels', 'OrderChannelController@index')->name('order_channels.index');
    Route::get('order_channels/available', 'OrderChannelController@available')->name('order_channels.available');
    Route::get('order_channels/types/{type}', 'OrderChannelController@connectionConfigurations')->name('order_channels.connectionConfigurations');
    Route::post('order_channels/connect', 'OrderChannelController@connect')->name('order_channels.connect');
    Route::get('order_channels/check_name/{customer}/{name}', 'OrderChannelController@checkOrderChannel')->name('order_channels.checkOrderChannel');
    Route::get('order_channels/get_oauth_url', 'OrderChannelController@getOauthUrl')->name('order_channels.getOauthUrl');
    Route::get('order_channels/connect_commerce_with_oauth', 'OrderChannelController@connectEcommerceWithOauth')->name('order_channels.connectEcommerceWithOauth');
    Route::get('order_channels/{orderChannel}', 'OrderChannelController@getOrderChannel')->name('order_channels.getOrderChannel');
    Route::post('order_channels/{orderChannel}/sync_products', 'OrderChannelController@syncProducts')->name('order_channels.syncProducts');
    Route::post('order_channels/{orderChannel}/sync_inventories', 'OrderChannelController@syncInventories')->name('order_channels.syncInventories');
    Route::post('order_channels/{orderChannel}/sync_order_by_number/{number}', 'OrderChannelController@syncOrderByNumber')->name('order_channels.syncOrderByNumber');
    Route::post('order_channels/{orderChannel}/sync_orders_by_date/{order}', 'OrderChannelController@syncOrdersByDate')->name('order_channels.syncOrdersByDate');
    Route::post('order_channels/{orderChannel}/sync_shipments/{syncFrom}', 'OrderChannelController@syncShipments')->name('order_channels.syncShipments');
    Route::post('order_channels/{orderChannel}/sync_product_by_product_id/{productId}', 'OrderChannelController@syncProductByProductId')->name('order_channels.syncProductByProductId');
    Route::post('order_channels/{orderChannel}/sync_product_by_product_sku/{productSku}', 'OrderChannelController@syncProductByProductSku')->name('order_channels.syncProductByProductSku');
    Route::get('order_channels/{orderChannel}/recreate_order_channel_webhooks', 'OrderChannelController@recreateOrderChannelWebhooks')->name('order_channels.recreateOrderChannelWebhooks');
    Route::post('order_channels/{orderChannel}/remove_order_channel_webhook/{id}', 'OrderChannelController@removeOrderChannelWebhook')->name('order_channels.removeOrderChannelWebhook');
    Route::post('order_channels/{orderChannel}/create_packiyo_webhook/{objectType}/{operation}', 'OrderChannelController@createPackiyoWebhook')->name('order_channels.createPackiyoWebhook');
    Route::post('order_channels/{orderChannel}/remove_packiyo_webhook/{webhook}', 'OrderChannelController@removePackiyoWebhook')->name('order_channels.removePackiyoWebhook');
    Route::post('order_channels/{orderChannel}/update_source_configuration', 'OrderChannelController@updateSourceConfiguration')->name('order_channels.updateSourceConfiguration');
    Route::post('order_channels/{orderChannel}/enable_scheduler', 'OrderChannelController@enableScheduler')->name('order_channels.enableScheduler');
    Route::post('order_channels/{orderChannel}/disable_scheduler', 'OrderChannelController@disableScheduler')->name('order_channels.disableScheduler');
    Route::post('order_channels/{orderChannel}/enable_disable_order_channel', 'OrderChannelController@enableDisableOrderChannel')->name('order_channels.enableDisableOrderChannel');
    Route::post('order_channels/{orderChannel}/refresh_packiyo_access_token', 'OrderChannelController@refreshPackiyoAccessToken')->name('order_channels.refreshPackiyoAccessToken');
    Route::post('order_channels/{orderChannel}/update_external_dataflow', 'OrderChannelController@updateExternalDataflow')->name('order_channels.updateExternalDataflow');
    Route::post('order_channels/{orderChannel}/update_user_name', 'OrderChannelController@updateUserName')->name('order_channels.updateUserName');

    Route::get('transfer_orders', 'TransferOrderController@index')->name('transfer_orders.index');
    Route::get('transfer_orders/data-table', 'TransferOrderController@dataTable')->name('transfer_orders.data-table');
    Route::post('transfer_orders/close/{purchaseOrder}', 'TransferOrderController@close')->name('transfer_orders.close');

    Route::get('{page}', ['as' => 'page.index', 'uses' => 'PageController@index']);

    Route::prefix('report')->name('report.')->group( function () {
        Route::get('{reportId}', 'ReportController@view')->name('view');
        Route::get('{reportId}/data_table', 'ReportController@dataTable')->name('dataTable');
        Route::post('{reportId}/export', 'ReportController@export')->name('export');
        Route::get('{reportId}/widgets', 'ReportController@widgets')->name('widgets');
    });

    Route::get('picking_batch/{pickingBatch}/items', 'PickingBatchController@getItems')->withTrashed()->name('picking_batch.getItems');
    Route::get('picking_batch/{pickingBatch}/data_table', 'PickingBatchController@dataTable')->withTrashed()->name('picking_batch.dataTable');
    Route::post('picking_batch/{pickingBatch}/clear_batch', 'PickingBatchController@clearBatch')->name('picking_batch.clearBatch');

    Route::prefix('easypost')->name('easypost.')->group( function () {
        Route::get('{easypostCredential}/carrierTypes', 'EasypostController@getCarrierTypes')->name('carrier_types');
        Route::get('{easypostCredential}/carriers/create', 'EasypostController@create')->name('carrier_account.create');
        Route::post('{easypostCredential}/carriers/store', 'EasypostController@createCarrierAccount')->name('carrier_account.store');
        Route::get('{easypostCredential}/carriers/{carrier}/edit', 'EasypostController@edit')->name('carrier_account.edit');
        Route::post('{easypostCredential}/carriers/{carrier}/update', 'EasypostController@updateCarrierAccount')->name('carrier_account.update');
        Route::delete('{easypostCredential}/carriers/{carrier}/delete', 'EasypostController@deleteCarrierAccount')->name('carrier_account.delete');
    });

    Route::get('audit/{modelName}/data_table/{modelId}', 'AuditController@auditDataTable')->name('audit');
});

Route::get('shipment/{shipment}/label/{shipmentLabel}', 'ShipmentController@label')->name('shipment.label');
Route::get('shipment/{shipment}/package_document/{packageDocument}', 'ShipmentController@packageDocument')->name('shipment.package_document');
Route::get('shipment/{shipment}/asn/{asn}/label/{packingLabel}', 'ShipmentController@ediPackingLabel')->name('shipment.packing-label');
Route::get('return/{return}/label/{returnLabel}', 'ReturnController@label')->name('return.label');
Route::get('shipment/{shipment}/packing_slip', 'ShipmentController@getPackingSlip')->name('shipment.getPackingSlip');
Route::get('order/{order}/order_slip', 'OrderController@getOrderSlip')->name('order.getOrderSlip');
Route::get('product/{product}/barcode', 'ProductController@barcode')->name('product.barcode');
Route::get('tote/{tote}/barcode', 'ToteController@barcode')->name('tote.barcode');
Route::get('picking_carts/{picking_cart}/barcode', 'PickingCartController@barcode')->name('pickingCart.barcode');
Route::get('customers/{customer}/easypost_credentials/{easypost_credential}/batch_shipments', 'EasypostCredentialController@batchShipments')->name('customers.easypost_credentials.batch_shipments');
Route::get('customers/{customer}/easypost_credentials/{easypost_credential}/scanform_batches', 'EasypostCredentialController@scanformBatches')->name('customers.easypost_credentials.scanform_batches');
