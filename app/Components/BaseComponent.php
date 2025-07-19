<?php

namespace App\Components;

use App\Models\{ContactInformation, Currency, Shipment, Tag, Webhook};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookServer\WebhookCall;
use Venturecraft\Revisionable\Revision;
use Webpatser\Countries\Countries;

class BaseComponent
{
    protected function batchWebhook($collections, $objectType, $resourceCollection, $operation)
    {
        $customerWiseItems = [];

        foreach ($collections as $key => $item) {
            $customerId = $this->getCustomerId($item, $objectType);

            $customerWiseItems[$customerId][] = $item;
        }

        foreach ($customerWiseItems as $customerId => $items) {
            $collections = new Collection($items);

            $this->webhook(new $resourceCollection($collections), $objectType, $operation, $customerId);
        }
    }

    protected function webhook($response, $objectType, $operation, $customerId, $orderChannelId = null)
    {
        $webhooks = Webhook::where('object_type', $objectType)
            ->where('operation', $operation)
            ->where('customer_id', $customerId);

        if ($objectType == Shipment::class) {
            $webhooks = $webhooks->where('order_channel_id', $orderChannelId);
        }

        $webhooks = $webhooks->get();

        foreach ($webhooks as $webhook) {
            $url = $webhook->url;

            WebhookCall::create()
                ->url($url)
                ->payload(['payload' => $response])
                ->useSecret($webhook->secret_key)
                ->dispatch();
        }
    }

    protected function getCustomerId($item, $objectType)
    {
        if ($objectType == Shipment::class) {
            $customerId = $item->order->customer_id;
        } else {
            $customerId = $item->customer_id;
        }

        return $customerId;
    }

    public function history($object)
    {
        $revisionable_type = get_class($object);
        $revisionable_id = $object->id;

        $revisions = Revision::where('revisionable_type', $revisionable_type)->where('revisionable_id', $revisionable_id)->get();

        return $revisions;
    }

    public function createContactInformation($data, $object)
    {
        $countryId = Arr::get($data, 'country_id');

        if ($countryId == null && Arr::get($data, 'country_code') != null) {
            $country = Countries::where('iso_3166_2', Arr::get($data, 'country_code'))->first();

            if ($country) {
                $countryId = $country->id;
            }
        }

        $contact = new ContactInformation();

        $contact->name = Arr::get($data, 'name');
        $contact->company_name = Arr::get($data, 'company_name');
        $contact->company_number = Arr::get($data, 'company_number');
        $contact->address = Arr::get($data, 'address');
        $contact->address2 = Arr::get($data, 'address2');
        $contact->zip = Arr::get($data, 'zip');
        $contact->city = Arr::get($data, 'city');
        $contact->state = Arr::get($data, 'state');
        $contact->country_id = $countryId;
        $contact->email = Arr::get($data, 'email');
        $contact->phone = Arr::get($data, 'phone');
        $contact->object()->associate($object);
        $contact->save();

        return $contact;
    }

    /**
     * @param $tags
     * @param Model $model
     * @param bool $replace
     * @return void
     */
    public function updateTags($tags, Model $model, bool $replace = false): void
    {
        if (request()->is('api/*')) {
            $replace = false;
        }

        $selectedTags = [];

        if (!empty($tags)) {
            foreach($tags as $tag) {
                $tag = trim($tag);

                if (empty($tag)) {
                    continue;
                }

                // TODO talk to Javi about this. Should we do a like binary search?
                $findTag = Tag::query()
                    ->where('name', 'like binary',$tag)
                    ->where('customer_id', $model->customer_id ?? $model->warehouse->customer_id ?? $model->shippingCarrier->customer_id)
                    ->firstOrCreate([
                        'name' => $tag,
                        'customer_id' => $model->customer_id ?? $model->warehouse->customer_id ?? $model->shippingCarrier->customer_id
                    ]);

                $selectedTags[] = $findTag->id;
            }
        }

        $this->syncTags($model, $selectedTags, $replace);
    }

    public function bulkUpdateTags(array $tags, $ids, $modelClass, $replace = false): void
    {
        if (request()->is('api/*')) {
            $replace = false;
        }

        try {
            foreach ($ids as $id) {
                $selectedTags = [];
                $model = $modelClass::find($id);

                foreach ($tags as $tag) {
                    $tag = trim($tag);

                    if (empty($tag)) {
                        continue;
                    }

                    $tagModel = Tag::firstOrCreate([
                        'name' => $tag,
                        'customer_id' => $model->customer_id ?? $model->warehouse->customer_id
                    ]);

                    $selectedTags[] = $tagModel->id;
                }

                $this->syncTags($model, $selectedTags, $replace);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    protected function updateCurrencyInput($input)
    {
        $currencyId = Arr::get($input, 'currency_id');
        $currencyCode = Arr::get($input, 'currency_code');

        if (!$currencyId && $currencyCode) {
            $currency = Currency::where('code', $currencyCode)->first();

            if ($currency) {
                $input['currency_id'] = $currency->id;
            }
        }

        return $input;
    }

    /**
     * Check if model have auditing activated
     */
    protected function syncTags($model, array $selectedTags, $detaching)
    {
        if (is_auditable($model)) {
            $model->auditSync('tags', $selectedTags, $detaching);
        } else {
            $model->tags()->sync($selectedTags, $detaching);
        }
    }
}
