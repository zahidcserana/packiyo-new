<?php

namespace App\Components\OrderChannel\Providers;

use App\Http\Requests\TribirdOrderChannel\ConnectRequest;
use App\Http\Requests\TribirdOrderChannel\ConfigurationUpdateRequest;
use App\Http\Requests\TribirdOrderChannel\EnableSchedulerRequest;
use App\Http\Requests\TribirdOrderChannel\DisableSchedulerRequest;
use App\Http\Requests\TribirdOrderChannel\UpdateExternalDataflowRequest;
use App\Http\Requests\TribirdOrderChannel\UpdateUserNameRequest;
use App\Components\BaseComponent;
use App\Interfaces\OrderChannelProviderCredential;
use App\Models\OrderChannel;
use App\Models\TribirdCredential;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Customer;
use App\Models\CustomerUserRole;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TribirdOrderChannelProvider extends BaseComponent implements OrderChannelProviderCredential
{
    const ENDPOINT_PACKIYO = 'PACKIYO';

    public function getOrderChannels(OrderChannelProviderCredential $credential = null)
    {
        try {
            $orderChannels = $this->send($credential, 'GET', '/ecommerces/list/source_ecommerces');

            foreach ($orderChannels as $key => $orderChannel) {
                $orderChannels[$key]['image_url'] = config('tribird.base_url') . $orderChannel['image_url'];
            }

            return $orderChannels;
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getOrderChannel($type)
    {
        return $this->send(null, 'GET', '/ecommerces/' . $type);
    }

    public function setupOrderChannelConnection(ConnectRequest $request)
    {
        $data = $request->validated();

        $source['endpoint_type'] = $data['order_channel_type'];
        $source['configuration'] = $this->reformConfigurations($data['configurations']);

        $requestBody['source'] = $source;
        $requestBody['name'] = $data['name'];
        $requestBody['customer_id'] = $data['customer_id'];

        $credential = $this->getCredential($data['customer_id']);

        $setupUrlAndRequestBody = $this->getSetupUrlAndRequestBody($credential, $requestBody, $data['oauth_connection']);

        $connection = $this->send(null, 'POST', $setupUrlAndRequestBody['url'], $setupUrlAndRequestBody['requestBody'], false);

        if (!$connection || $connection['errors']) {
            return $connection ?? null;
        }

        $dataflowInfo = $connection['data'];

        $credential = $this->getCredential($data['customer_id']);

        if (!$credential) {
            $data['settings']['external_integration_id'] = $dataflowInfo['integration_info']['id'];

            $credential = TribirdCredential::create($data);
        }

        $integrationUser = $this->getIntegrationUser($data);

        $migrateToOrderChannelId = Arr::get($data, 'migrate_to_order_channel_id');

        if ($migrateToOrderChannelId) {
            $orderChannel = OrderChannel::find($migrateToOrderChannelId);
        } else {
            $orderChannel = new OrderChannel();
        }

        $orderChannel->fill([
            'customer_id' => $data['customer_id'],
            'name' => $data['name'],
            'image_url' => $dataflowInfo['source_connections'][0]['image_url'],
            'settings' => [
                'user_id' => $integrationUser->id,
                'external_endpoint_type' => $dataflowInfo['source_connections'][0]['type'] ?? $dataflowInfo['source_connections'][0]['name'],
                'external_dataflow_id' => $dataflowInfo['dataflow_id']
            ]
        ]);

        $orderChannel->credential()->associate($credential);
        $orderChannel->save();

        $destinationInfo = $this->getDestinationDataWithEndpoint($data['customer_id'], $integrationUser, $orderChannel, $dataflowInfo['dataflow_id']);

        $connectionWithDest = $this->send($orderChannel->credential, 'POST', $destinationInfo['endpoint'], $destinationInfo['data']);

        $orderChannel->forceFill(['settings->external_destination_connection_id' => $connectionWithDest['data']['destination_connections'][0]['id']]);
        $orderChannel->save();

        return $orderChannel;
    }

    private function getCredential($customerId) {
        return TribirdCredential::where('customer_id', $customerId)->first();
    }

    private function reformConfigurations($configurations) {
        $configs = [];

        foreach ($configurations as $configuration) {
            $configs[] = $configuration;
        }

        return $configs;
    }

    private function getSetupUrlAndRequestBody(TribirdCredential $credential = null, $data, $oauthConnection)
    {
        $endpointType = $data['source']['endpoint_type'];

        if ($oauthConnection == 'true') {
            $url = is_null($credential)  ? '/' . strtolower($endpointType) . '/create_connection?customer_id=' . $data['customer_id'] : '/' . strtolower($endpointType) . '/create_connection/integrations/' . $credential->settings['external_integration_id'] . '?customer_id=' . $data['customer_id'];
            $requestBody = $data['source'];
        } else {
            $url = is_null($credential) ? '/ecommerces/connection_setup' : '/ecommerces/dataflows/add_dataflow_using_source_config/integrations/' . $credential->settings['external_integration_id'] . '?customer_id=' . $data['customer_id'];
            $requestBody = is_null($credential) ? $data : $data['source'];
        }

        return compact('url', 'requestBody');
    }

    public function getDestinationDataWithEndpoint($customerId, $integrationUser, $orderChannel, $dataflowId)
    {
        $data = [];

        $connectedChannel = OrderChannel::where('customer_id', $customerId)
            ->whereNotIn('id', [$orderChannel->id])
            ->where('credential_id', '!=', 0)
            ->first();

        if ($connectedChannel == null) {
            $data['endpoint_type'] = self::ENDPOINT_PACKIYO;

            $configuration[0]['field'] = 'url';
            $configuration[0]['value'] = URL::to('/') . '/api';
            $configuration[1]['field'] = 'access_token';
            $configuration[1]['value'] = $integrationUser->createToken('Integration')->plainTextToken;
            $configuration[2]['field'] = 'customer_id';
            $configuration[2]['value'] = $customerId;
            $configuration[3]['field'] = 'order_channel_id';
            $configuration[3]['value'] = $orderChannel->id;
            $configuration[4]['field'] = 'supplier_id';
            $configuration[4]['value'] = null;
            $configuration[5]['field'] = 'warehouse_id';
            $configuration[5]['value'] = null;

            $data['configuration'] = $configuration;

            $endpoint = '/ecommerces/dataflows/' . $dataflowId . '/add_destination_using_config';
        } else {
            if (!isset($connectedChannel->settings['external_destination_connection_id'])) {
                $data['connection_id'] = $this->getExternalDestinationConnectionId($connectedChannel);
            } else {
                $data['connection_id'] = $connectedChannel->settings['external_destination_connection_id'];
            }

            $configuration[0]['field'] = 'order_channel_id';
            $configuration[0]['value'] = $orderChannel->id;
            $configuration[1]['field'] = 'access_token';
            $configuration[1]['value'] = $integrationUser->createToken('Integration')->plainTextToken;

            $data['dataflow_configuration'] = $configuration;

            $endpoint = '/ecommerces/dataflows/' . $dataflowId . '/add_destination_using_connection';
        }

        return compact('data', 'endpoint');
    }

    private function getExternalDestinationConnectionId(OrderChannel $connectedChannel) {
        $externalDestinationConnectionId = null;

        do {
            try {
                $connectedChannel->refresh();
                $externalDestinationConnectionId = $connectedChannel->settings['external_destination_connection_id'];
            } catch (\Exception $exception) {
                sleep(1);
            }

        } while ($externalDestinationConnectionId == null);

        return $externalDestinationConnectionId;
    }

    public function getIntegrationUser($data)
    {
        $customer = Customer::find($data['customer_id']);
        $email = strtolower(preg_replace('/\s+/', '', $data['name'])) . '_' . $customer->id . '@packiyo.com';

        $integrationUser = User::where('email', $email)->first();

        if ($integrationUser) {
            return $integrationUser;
        }

        $userInput['email'] = $email;
        $userInput['password'] = Hash::make(Str::random());
        $userInput['user_role_id'] = UserRole::ROLE_MEMBER;
        $userInput['system_user'] = true;

        $integrationUser = User::create($userInput);

        $integrationUser->customers()->attach($customer->id, [
            'role_id' => CustomerUserRole::ROLE_CUSTOMER_ADMINISTRATOR
        ]);

        $contactInformationData['name'] = $data['name'];
        $contactInformationData['email'] = $email;
        $contactInformationData['company_name'] = $customer->contactInformation->company_name ?? null;
        $contactInformationData['country_id'] = $customer->contactInformation->country_id ?? null;

        $this->createContactInformation($contactInformationData, $integrationUser);

        return $integrationUser;
    }

    public function getEcommerceOauthUrl(Request $request) {
        $urlTail = '';

        foreach ($request->configurations as $configuration) {
            $urlTail .= $configuration['field'] . '=' . $configuration['value'] . '&';
        }

        $url = '/' . strtolower($request->order_channel_type) . '/get_oauth_url?' . $urlTail .'integration_id=' . $request->external_integration_id .'&customer_id=' . $request->customer_id . '&customer_name=' . Customer::find($request->customer_id)->contactInformation->name;

        return $this->send(null, 'GET', $url);
    }

    public function syncProducts(OrderChannel $orderChannel)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/product_sync/' . $orderChannel->settings['external_dataflow_id']);
    }

    public function syncOrders(OrderChannel $orderChannel)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/order_sync/' . $orderChannel->settings['external_dataflow_id']);
    }

    public function syncInventories(OrderChannel $orderChannel)
    {
        return $this->send($orderChannel->credential, 'POST', '/dataflows/inventory_sync/' . $orderChannel->settings['external_dataflow_id']. '/connection/' . $orderChannel->settings['external_destination_connection_id']);
    }

    public function syncOrderByNumber(OrderChannel $orderChannel, $number)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/order_sync_by_number/' . $orderChannel->settings['external_dataflow_id'] . '/' . str_replace('#', '%23', ucfirst($number)));
    }

    public function syncProductByProductId(OrderChannel $orderChannel, $id)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/product_sync_by_product_id/' . $orderChannel->settings['external_dataflow_id'] . '/' . str_replace('#', '%23', ucfirst($id)));
    }

    public function syncProductByProductSku(OrderChannel $orderChannel, $sku)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/product_sync_by_sku/' . $orderChannel->settings['external_dataflow_id'] . '/' . $sku);
    }

    public function syncOrdersByDate(OrderChannel $orderChannel, $syncFrom)
    {
        $timezone = auth()->user()->settings->where('key', 'timezone')->first()->value ?? config('app.timezone');

        $utcDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $syncFrom, $timezone)->tz('UTC');

        return $this->send($orderChannel->credential, 'GET', '/dataflows/order_sync/' . $orderChannel->settings['external_dataflow_id'] . '?date_from=' . $utcDateTime);
    }

    public function getOrderChannelInfo(OrderChannel $orderChannel)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/info/' . $orderChannel->settings['external_dataflow_id'] . '/source');
    }

    public function recreateOrderChannelWebhooks(OrderChannel $orderChannel)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/check_and_set_webhooks/' . $orderChannel->settings['external_dataflow_id'] . '/source');
    }

    public function removeOrderChannelWebhook(OrderChannel $orderChannel, $id)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/remove_webhook/' . $orderChannel->settings['external_dataflow_id'] . '/source/' . $id);;
    }

    public function createPackiyoWebhook(OrderChannel $orderChannel, $objectType, $operation)
    {
        return $this->send($orderChannel->credential, 'GET', '/dataflows/check_and_set_webhooks/' . $orderChannel->settings['external_dataflow_id'] . '/destination/' . $orderChannel->settings['external_destination_connection_id'] . '?object_type=' . $objectType . '&operation=' . $operation);
    }

    public function getOrderChannelDetails(OrderChannel $orderChannel)
    {
        return $this->send($orderChannel->credential, 'GET', '/ecommerces/dataflows/' . $orderChannel->settings['external_dataflow_id'] . '/details');
    }

    public function updateSourceConfigurations(OrderChannel $orderChannel, ConfigurationUpdateRequest $request)
    {
        $input = $request->validated();

        $body['configuration'] = $this->reformConfigurations($input['configurations']);

        return $this->send($orderChannel->credential, 'POST', '/ecommerces/dataflows/' . $orderChannel->settings['external_dataflow_id'] . '/source/update', $body);
    }

    public function enableScheduler(OrderChannel $orderChannel, EnableSchedulerRequest $request)
    {
        $input = $request->validated();

        $body['cron_expression'] = $input['cron_expression'];
        $body['destination_connection_id'] = $orderChannel->settings['external_destination_connection_id'];
        $body['order_channel_id'] = $orderChannel->id;

        return $this->send($orderChannel->credential, 'POST', '/scheduler/' . $orderChannel->settings['external_dataflow_id']. '/enable_cron/' . $input['type'], $body);
    }

    public function disableScheduler(OrderChannel $orderChannel, DisableSchedulerRequest $request)
    {
        $input = $request->validated();

        return $this->send($orderChannel->credential, 'POST', '/scheduler/' . $orderChannel->settings['external_dataflow_id']. '/disable_cron/' . $input['type'] . '?connection=' . $orderChannel->settings['external_destination_connection_id']);
    }

    public function enableOrderChannel(OrderChannel $orderChannel)
    {
        $response = $this->send($orderChannel->credential, 'POST', '/dataflows/enable_order_channel/' . $orderChannel->settings['external_dataflow_id'] . '?connection=' . $orderChannel->settings['external_destination_connection_id']);

        if ($response['success'] == 'true') {
            $orderChannel->is_disabled = false;
            $orderChannel->save();
        }

        return $response;
    }

    public function disableOrderChannel(OrderChannel $orderChannel)
    {
        $response = $this->send($orderChannel->credential, 'POST', '/dataflows/disable_order_channel/' . $orderChannel->settings['external_dataflow_id'] . '?connection=' . $orderChannel->settings['external_destination_connection_id']);

        if ($response['success'] == 'true') {
            $inventoryPackiyoWebhook =$orderChannel->webhooks->where('object_type', \App\Models\InventoryLog::class)->where('operation', \App\Models\Webhook::OPERATION_TYPE_STORE)->first();
            $shipmentPackiyoWebhook = $orderChannel->webhooks->where('object_type', \App\Models\Shipment::class)->where('operation', \App\Models\Webhook::OPERATION_TYPE_STORE)->first();

            if ($inventoryPackiyoWebhook != null) {
                $inventoryPackiyoWebhook->delete();
            }

            if ($shipmentPackiyoWebhook != null) {
                $shipmentPackiyoWebhook->delete();
            }

            $orderChannel->is_disabled = true;
            $orderChannel->save();
        }

        return $response;
    }

    public function refreshPackiyoAccessToken(OrderChannel $orderChannel)
    {
        $user = User::withTrashed()->where('id', $orderChannel->settings['user_id'])->first();
        $user->restore();

        $config['configuration'][0]['field'] = 'access_token';
        $config['configuration'][0]['value'] = $user->createToken('Integration')->plainTextToken;;

        return $this->send($orderChannel->credential, 'POST', '/ecommerces/dataflows/' . $orderChannel->settings['external_dataflow_id'] . '/destination_connection/' . $orderChannel->settings['external_destination_connection_id'] . '/update', $config);
    }

    public function updateDataflow(OrderChannel $orderChannel, UpdateExternalDataflowRequest $request)
    {
        $input = $request->validated();

        $data['name'] = $input['name'];

        $response = $this->send($orderChannel->credential, 'POST', '/ecommerces/dataflows/' . $orderChannel->settings['external_dataflow_id'] . '/update_dataflow', $data);

        if (!($response && $response['success'])) {
            return $response;
        }

        $orderChannel->name = $data['name'];
        $orderChannel->save();

        return $response;
    }

    public function updateUserName(OrderChannel $orderChannel, UpdateUserNameRequest $request)
    {
        $input = $request->validated();

        $contactInformation = User::find($orderChannel->settings['user_id'])->contactInformation;
        $contactInformation->name = $input['name'];
        $contactInformation->save();
    }

    private function send(TribirdCredential $tribirdCredential = null, $method, $endpoint, $data = null, $returnException = false)
    {
        Log::info('[Tribird] send', [
            'tribird_credential_id' => $tribirdCredential->id ?? null,
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data
        ]);

        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        $url = config('tribird.base_url') . $endpoint;

        try {
            $response = $client->request($method, $url, $method == 'GET' ? [] : ['body' => !is_null($data) ? json_encode($data) : $data]);
            $body = json_decode($response->getBody()->getContents() ?? null, true);

            Log::info('[Tribird] response', is_null($body) || is_bool($body) ? [$body] : $body);


            return $body;

        } catch (\Exception $exception) {
            Log::error('[Tribird] exception thrown', [$exception]);

            if ($returnException) {
                $response = $exception->getResponse();

                return $response->getBody()->getContents();
            }
        }

        return null;
    }
}
