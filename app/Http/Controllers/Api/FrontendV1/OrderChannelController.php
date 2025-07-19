<?php

namespace App\Http\Controllers\Api\FrontendV1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TribirdOrderChannel\ConfigurationUpdateRequest;
use App\Http\Requests\TribirdOrderChannel\ConnectRequest;
use App\Http\Requests\TribirdOrderChannel\DisableSchedulerRequest;
use App\Http\Requests\TribirdOrderChannel\EnableSchedulerRequest;
use App\Models\Customer;
use App\Models\OrderChannel;
use App\Models\TribirdCredential;
use Illuminate\Http\Request;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class OrderChannelController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    public function availableConnections(Request $request)
    {
        $orderChannels = app('tribirdOrderChannel')->getOrderChannels() ?? [];
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();
        $credential = TribirdCredential::whereIn('customer_id', $customerIds)->first();

        return response()->json(['data' => [
            'orderChannels' => $orderChannels,
            'credential' => $credential,
        ]]);
    }

    public function connectionFields($type)
    {
        $orderChannel = app('tribirdOrderChannel')->getOrderChannel($type);

        return response()->json(['data' => $orderChannel]);
    }

    public function orderChannel(OrderChannel $orderChannel)
    {
        $orderChannelDetails = app('tribirdOrderChannel')->getOrderChannelDetails($orderChannel);
        $orderChannelInfo = (array) app('tribirdOrderChannel')->getOrderChannelInfo($orderChannel);

        return response()->json(['data' => [
            'orderChannel' => $orderChannel,
            'orderChannelDetails' => $orderChannelDetails,
            'orderChannelInfo' => $orderChannelInfo,
        ]]);
    }

    public function updateSourceConfiguration(ConfigurationUpdateRequest $request, OrderChannel $orderChannel)
    {
        app('tribirdOrderChannel')->updateSourceConfigurations($orderChannel, $request);

        return response()->json(['success' => true, 'message' => 'Configurations successfully updated.']);
    }

    public function recreateOrderChannelWebhooks(OrderChannel $orderChannel)
    {
        app('tribirdOrderChannel')->recreateOrderChannelWebhooks($orderChannel);

        return response()->json(['success' => true, 'message' => 'Webhooks successfully created.']);
    }

    public function enableScheduler(EnableSchedulerRequest $request, OrderChannel $orderChannel)
    {
        $response = app('tribirdOrderChannel')->enableScheduler($orderChannel, $request);

        if (($response && $response['success'])) {
            return response()->json(['success' => true, 'message' => 'Cron successfully added.']);
        }

        return response()->json(['success' => false, 'message' => 'There is an error adding the cron.']);
    }

    public function disableScheduler(DisableSchedulerRequest $request, OrderChannel $orderChannel)
    {
        $response = app('tribirdOrderChannel')->disableScheduler($orderChannel, $request);

        if (($response && $response['success'])) {
            return response()->json(['success' => true, 'message' => 'Cron successfully removed.']);
        }

        return response()->json(['success' => false, 'message' => 'There is an error adding the cron.']);
    }

    public function createPackiyoWebhook(OrderChannel $orderChannel, $objectType, $operation)
    {
        app('tribirdOrderChannel')->createPackiyoWebhook($orderChannel, $objectType, $operation);

        return response()->json(['success' => true, 'message' => 'Webhook successfully created.']);
    }

    public function syncProducts(OrderChannel $orderChannel)
    {
        if (is_null(app('tribirdOrderChannel')->syncProducts($orderChannel))) {
            return response()->json([
                    'success' => false,
                    'message' => __('There is an error connecting the order channel')
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('Products synced successfully!')
        ]);
    }

    public function syncInventories(OrderChannel $orderChannel)
    {
        if (is_null(app('tribirdOrderChannel')->syncInventories($orderChannel))) {
            return response()->json([
                    'success' => false,
                    'message' => __('There is an error connecting the order channel')
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('Inventories synced successfully!')
        ]);
    }

    public function syncOrderByNumber(OrderChannel $orderChannel, $number)
    {
        if (is_null(app('tribirdOrderChannel')->syncOrderByNumber($orderChannel, $number))) {
            return response()->json([
                    'success' => false,
                    'message' => __('There is an error connecting the order channel')
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('Order synced successfully!')
        ]);
    }

    public function syncProductByProductId(OrderChannel $orderChannel, $productId)
    {
        $syncResult =  app('tribirdOrderChannel')->syncProductByProductId($orderChannel, $productId);

        if (is_null($syncResult)) {
            return response()->json([
                    'success' => false,
                    'message' => __('There is an error connecting the order channel')
                ]
            );
        }

        if (empty($syncResult)){
            return response()->json([
                'success' => false,
                'message' => __('No products found for the ID : :id', ['id' => $productId])
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Product synced successfully!')
        ]);
    }

    public function syncProductByProductSku(OrderChannel $orderChannel, $productSku)
    {
        $syncResult =  app('tribirdOrderChannel')->syncProductByProductSku($orderChannel, $productSku);

        if (is_null($syncResult)) {
            return response()->json([
                    'success' => false,
                    'message' => __('There is an error connecting the order channel')
                ]
            );
        }

        if (empty($syncResult)){
            return response()->json([
                'success' => false,
                'message' => __('No products found for the SKU : :sku', ['sku' => $productSku])
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Product synced successfully!')
        ]);
    }

    public function syncOrdersByDate(OrderChannel $orderChannel, $syncFrom)
    {
        if (is_null(app('tribirdOrderChannel')->syncOrdersByDate($orderChannel, $syncFrom))) {
            return response()->json([
                    'success' => false,
                    'message' => __('There is an error connecting the order channel')
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('Orders synced successfully!')
        ]);
    }

    public function syncShipments(OrderChannel $orderChannel, $syncFrom)
    {
        app('orderChannel')->syncShipments($orderChannel, $syncFrom);

        return response()->json([
            'success' => true,
            'message' => __('Shipments synced successfully!')
        ]);
    }

    public function checkOrderChannel(Customer $customer, $name)
    {
        $orderChannel = OrderChannel::where('customer_id', $customer->id)->where('name', $name)->first();

        return response()->json([
            'success' => (bool)$orderChannel
        ]);
    }

    public function removeOrderChannelWebhook(OrderChannel $orderChannel, $id)
    {
        app('tribirdOrderChannel')->removeOrderChannelWebhook($orderChannel, $id);

        return response()->json([
            'success' => true,
            'message' => __('Webhook successfully removed!')
        ]);
    }

    public function getOauthUrl(Request $request)
    {
        $response = app('tribirdOrderChannel')->getEcommerceOauthUrl($request);

        return response()->json([
            'success' => (bool)$response,
            'url' => $response['url']
        ]);
    }

    public function connect(ConnectRequest $request)
    {
        $orderChannel = app('tribirdOrderChannel')->setupOrderChannelConnection($request);

        if (!$orderChannel || $orderChannel['errors']) {
            return response()->json([
                'success' => false,
                'message' => $orderChannel['errors'][0] ?? "There is an error connecting the order channels!"
            ]);
        }

        if (is_null(app('tribirdOrderChannel')->syncProducts($orderChannel))) {
            return response()->json([
                    'success' => false,
                    'message' => __('Order channel connected but there is an error in syncing products')
                ]
            );
        }

        if (is_null(app('tribirdOrderChannel')->syncOrders($orderChannel))) {
            return response()->json([
                    'success' => false,
                    'message' => __('Order channel connected but there is an error in syncing orders')
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('Order channel successfully connected and data synced!')
        ]);
    }
}
