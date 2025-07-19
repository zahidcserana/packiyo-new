<?php

namespace App\Http\Controllers;

use App\Http\Requests\TribirdOrderChannel\ConnectRequest;
use App\Http\Requests\TribirdOrderChannel\ConfigurationUpdateRequest;
use App\Http\Requests\TribirdOrderChannel\EnableSchedulerRequest;
use App\Http\Requests\TribirdOrderChannel\DisableSchedulerRequest;
use App\Http\Requests\TribirdOrderChannel\UpdateExternalDataflowRequest;
use App\Http\Requests\TribirdOrderChannel\UpdateUserNameRequest;
use App\Models\TribirdCredential;
use App\Models\OrderChannel;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Webhook;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\JsonResponse;

class OrderChannelController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OrderChannel::class);
    }

    public function index(Request $request)
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $credential = TribirdCredential::whereIn('customer_id', $customerIds)->first();
        $orderChannels = OrderChannel::whereIn('customer_id', $customerIds)->get();

        return view('order_channels.index', [
            'credential' => $credential,
            'orderChannels' => $orderChannels,
            'skipoauth' => $request->skipoauth == 'true' ? 'true' : 'false'
        ]);
    }

    public function available()
    {
        return app('tribirdOrderChannel')->getOrderChannels() ?? [];
    }

    public function connectionConfigurations($type)
    {
        $orderChannel = app('tribirdOrderChannel')->getOrderChannel($type);

        return response()->json([
            'data' => $orderChannel['configuration']
        ]);
    }

    public function connect(ConnectRequest $request)
    {
        $this->authorize('create', OrderChannel::class);

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

    public function checkOrderChannel(Customer $customer, $name)
    {
        $orderChannel = OrderChannel::where('customer_id', $customer->id)->where('name', $name)->first();

        return response()->json([
            'success' => $orderChannel ? true : false
        ]);
    }

    public function getOauthUrl(Request $request)
    {
        return app('tribirdOrderChannel')->getEcommerceOauthUrl($request);
    }

    public function connectEcommerceWithOauth(Request $request)
    {
        $response = app('tribirdOrderChannel')->getEcommerceOauthUrl($request);

        return Redirect::to($response['url']);
    }

    public function getOrderChannel(OrderChannel $orderChannel)
    {
        $this->authorize('getOrderChannel', $orderChannel);

        if (!$orderChannel->credential) {
            return redirect()
                ->back(fallback: route('order_channels.index'))
                ->withErrors(__('Cannot manage custom order channels'));
        }

        $orderChannelDetails = app('tribirdOrderChannel')->getOrderChannelDetails($orderChannel);
        $orderChannelInfo = (array) app('tribirdOrderChannel')->getOrderChannelInfo($orderChannel);

        return view('order_channels.details', [
            'orderChannel' => $orderChannel,
            'orderChannelDetails' => $orderChannelDetails,
            'orderChannelInfo' => $orderChannelInfo,
            'user' => User::find($orderChannel->settings['user_id'])
        ]);
    }

    public function updateSourceConfiguration(ConfigurationUpdateRequest $request, OrderChannel $orderChannel)
    {
        $this->authorize('update', $orderChannel);

        app('tribirdOrderChannel')->updateSourceConfigurations($orderChannel, $request);

        return redirect()->back()->withStatus(__('Configurations successfully updated'));
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

    /**
     * @param OrderChannel $orderChannel
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncOrderByNumber(OrderChannel $orderChannel, $number): JsonResponse
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

    public function syncProductByProductId(OrderChannel $orderChannel, $productId): JsonResponse
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

    public function syncProductByProductSku(OrderChannel $orderChannel, $productSku): JsonResponse
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

    /**
     * @param OrderChannel $orderChannel
     * @param $syncFrom
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncOrdersByDate(OrderChannel $orderChannel, $syncFrom): JsonResponse
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

    /**
     * @param OrderChannel $orderChannel
     * @param $syncFrom
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncShipments(OrderChannel $orderChannel, $syncFrom): JsonResponse
    {
        app('orderChannel')->syncShipments($orderChannel, $syncFrom);

        return response()->json([
            'success' => true,
            'message' => __('Shipments synced successfully!')
        ]);
    }

    public function recreateOrderChannelWebhooks(OrderChannel $orderChannel)
    {
        app('tribirdOrderChannel')->recreateOrderChannelWebhooks($orderChannel);

        return redirect()->back()->withStatus(__('Webhooks successfully created'));
    }

    public function removeOrderChannelWebhook(OrderChannel $orderChannel, $id)
    {
        app('tribirdOrderChannel')->removeOrderChannelWebhook($orderChannel, $id);

        return redirect()->back()->withStatus(__('Webhook successfully removed'));
    }

    public function createPackiyoWebhook(OrderChannel $orderChannel, $objectType, $operation)
    {
        app('tribirdOrderChannel')->createPackiyoWebhook($orderChannel, $objectType, $operation);

        return redirect()->back()->withStatus(__('Webhook successfully created'));
    }

    public function removePackiyoWebhook(OrderChannel $orderChannel, Webhook $webhook)
    {
        $webhook->delete();

        return redirect()->back()->withStatus(__('Webhook successfully removed'));
    }

    public function enableScheduler(OrderChannel $orderChannel, EnableSchedulerRequest $request)
    {
        $response = app('tribirdOrderChannel')->enableScheduler($orderChannel, $request);

        if (($response && $response['success'])) {
            return redirect()->back()->withStatus(__('Cron successfully added'));
        }

        return redirect()->back()->withErrors(__('There is an error adding the cron'));
    }

    public function disableScheduler(OrderChannel $orderChannel, DisableSchedulerRequest $request)
    {
        $response = app('tribirdOrderChannel')->disableScheduler($orderChannel, $request);

        if (($response && $response['success'])) {
            return redirect()->back()->withStatus(__('Cron successfully removed'));
        }

        return redirect()->back()->withErrors(__('There is an error removing the cron'));
    }

    public function enableDisableOrderChannel(OrderChannel $orderChannel)
    {
        
        if ($orderChannel->is_disabled == true ) {
            $response = app('tribirdOrderChannel')->enableOrderChannel($orderChannel);

            if ($response['success'] == 'true') {
                return response()->json([
                    'success' => true,
                    'message' => __('Order Channel Successfully Enabled')
                ]);
            }
        } else if ($orderChannel->is_disabled == false ) {
            $response = app('tribirdOrderChannel')->disableOrderChannel($orderChannel);

            if ($response['success'] == 'true') {
                return response()->json([
                    'success' => true,
                    'message' => __('Order Channel Successfully Disabled')
                ]);
            } 
        }
        
        return response()->json([
            'success' => false,
            'message' => __('Error while ')
        ]);
    }
    
    public function refreshPackiyoAccessToken(OrderChannel $orderChannel)
    {
        $response = app('tribirdOrderChannel')->refreshPackiyoAccessToken($orderChannel);
        
        if ($response && $response['success']) {
            return redirect()->back()->withStatus(__('Packiyo access token successfully refreshed'));
        }
        
        return redirect()->back()->withErrors(__('There is an error refreshing the access token'));
    }

    public function updateExternalDataflow(OrderChannel $orderChannel, UpdateExternalDataflowRequest $request)
    {
        $response = app('tribirdOrderChannel')->updateDataflow($orderChannel, $request);

        if ($response && $response['success']) {
            return redirect()->back()->withStatus(__('Integration name successfully updated'));
        }
        
        return redirect()->back()->withErrors(__('There is an error updating integration name'));
    }

    public function updateUserName(OrderChannel $orderChannel, UpdateUserNameRequest $request)
    {
        app('tribirdOrderChannel')->updateUserName($orderChannel, $request);

        return redirect()->back()->withStatus(__('Integration user name successfully updated'));
    }
}
