<?php

namespace App\JsonApi\PublicV1\Orders;

use App\Http\Requests\Order\StoreRequest;
use App\Http\Requests\Order\UpdateRequest;
use App\Models\Order;
use App\Models\OrderChannel;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class OrderRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        if ($this->isCreating()) {
            $request = new StoreRequest($this->input('data.attributes'));
        } else {
            $request = new UpdateRequest($this->input('data.attributes'));
        }

        $rules = $request->rules();

        unset($rules['customer_id']);
        $rules['customer'] = JsonApiRule::toOne();

        return $rules;
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $customerId = $this->input('data.relationships.customer.data.id');

        if (!$customerId) {
            $order = Order::find($input['data']['id']);
            $customerId = $order->customer_id;
        }

        $input['data']['attributes']['customer_id'] = $customerId;

        if ($shippingContactInformationData = Arr::get($input, 'data.attributes.shipping_contact_information_data')) {
            $input['data']['attributes']['shipping_contact_information'] = $shippingContactInformationData;
            unset($input['data']['attributes']['shipping_contact_information_data']);

            if ($shippingCountryCode = Arr::get($input, 'data.attributes.shipping_contact_information.country')) {
                $input['data']['attributes']['shipping_contact_information']['country_code'] = $shippingCountryCode;
            }
        }

        if ($billingContactInformationData = Arr::get($input, 'data.attributes.billing_contact_information_data')) {
            $input['data']['attributes']['billing_contact_information'] = $billingContactInformationData;
            unset($input['data']['attributes']['billing_contact_information_data']);

            if ($billingCountryCode = Arr::get($input, 'data.attributes.billing_contact_information.country')) {
                $input['data']['attributes']['billing_contact_information']['country_code'] = $billingCountryCode;
            }
        }

        if ($orderItemData = Arr::get($input, 'data.attributes.order_item_data')) {
            $input['data']['attributes']['order_items'] = $orderItemData;
            unset($input['data']['attributes']['order_item_data']);
        }

        $this->setOrderChannel($customerId, $input);

        if ($tagsArray = $this->setTags(Arr::get($input, 'data', []))) {
            $input['data']['attributes']['tags'] = $tagsArray;
        }

        $this->replace($input);
    }

    protected function withExisting(Order $order, array $existing)
    {
        $existing['attributes']['ordered_at'] = Carbon::parse($existing['attributes']['ordered_at'])->toDateTimeString();

        if ($tagsArray = $this->setTags($existing)) {
            $existing['attributes']['tags'] = $tagsArray;
        }

        return $existing;
    }

    private function setOrderChannel($customerId, &$input)
    {
        $orderChannelId = Arr::get($input, 'data.attributes.order_channel_id');

        $accessToken = request()->user()->currentAccessToken();

        if ($accessToken && $accessToken->order_channel_id) {
            $orderChannelId = $accessToken->order_channel_id;
        }

        if (!$orderChannelId && ($orderChannelName = Arr::get($input, 'data.attributes.order_channel_name'))) {
            $orderChannel = OrderChannel::firstOrCreate([
                'customer_id' => $customerId,
                'name' => $orderChannelName
            ]);

            $orderChannelId = $orderChannel->id;
        }

        if ($orderChannelId) {
            $input['data']['attributes']['order_channel_id'] = $orderChannelId;
        }
    }

    /**
     * @param array $input
     * @return array|null
     */
    private function setTags(array $input): array|null
    {
        $tags = Arr::get($input, 'attributes.tags');

        if ($tags != '') {
            $tagsData = array_map('trim', explode(',', $tags));

            return $tagsData;
        }

        return null;
    }
}
