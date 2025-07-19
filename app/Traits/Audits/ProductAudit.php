<?php

namespace App\Traits\Audits;

use App\Enums\LotPriority;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Webpatser\Countries\Countries;

trait ProductAudit
{
    use AuditTrait;

    public static $columnTitle = [
        'priority_counting_requested_at' => 'Priority counting',
        'has_serial_number' => 'Needs serial number',
        'lot_tracking' => 'Needs Lot tracking',
    ];

    public static $productTypes = [
        Product::PRODUCT_TYPE_REGULAR =>  'Regular',
        Product::PRODUCT_TYPE_STATIC_KIT =>  'Static Kit',
        Product::PRODUCT_TYPE_DYNAMIC_KIT =>  'Dynamic Kit',
        Product::PRODUCT_TYPE_VIRTUAL => 'Virtual'
    ];

    public static function auditKitItems($product, $oldKitItems, $newKitItems)
    {
        $oldData = [];
        $oldIds = [];

        foreach ($oldKitItems as $kitItem) {
            $oldData[] = ['product_id' => $kitItem->pivot->child_product_id, 'quantity' => $kitItem->pivot->quantity, 'sku' => $kitItem->sku];
            $oldIds[] = $kitItem->id;
        }

        $newData = [];
        $newIds = [];

        foreach ($newKitItems as $kitItem) {
            $newData[] = ['product_id' => $kitItem->pivot->child_product_id, 'quantity' => $kitItem->pivot->quantity, 'sku' => $kitItem->sku];
            $newIds[] = $kitItem->id;
        }

        $newAddedProductIds = array_values(array_diff($newIds, $oldIds));

        if (!empty($newAddedProductIds)) {
            $newAddedProducts = collect($newData)->whereIn('product_id', $newAddedProductIds);

            foreach ($newAddedProducts as $newAddedProduct) {
                $message = __(':quantity x :sku added to KIT', ['quantity' => $newAddedProduct['quantity'], 'sku' => $newAddedProduct['sku']]);

                static::auditCustomEvent($product, 'kit added', $message);
            }
        }

        $removedProductIds = array_values(array_diff($oldIds, $newIds));

        if (!empty($removedProductIds)) {
            $removedProducts = collect($oldData)->whereIn('product_id', $removedProductIds);

            foreach ($removedProducts as $removedProduct) {
                $message = __(':quantity x :sku removed from KIT', ['quantity' => $removedProduct['quantity'], 'sku' => $removedProduct['sku']]);

                static::auditCustomEvent($product, 'kit removed', $message);
            }
        }

        foreach ($newKitItems as $newKitItem) {
            foreach ($oldKitItems as $oldKitItem) {
                if ($newKitItem->id == $oldKitItem->id && $newKitItem->pivot->quantity != $oldKitItem->pivot->quantity) {
                    $message = __('Quantity changed from <em>":old"</em> to <em>":new"</em> for :sku', ['old' => $oldKitItem->pivot->quantity, 'new' => $newKitItem->pivot->quantity, 'sku' => $newKitItem->sku]);

                    static::auditCustomEvent($product, 'kit updated', $message);
                }
            }
        }
    }

    /**
     * @param $model
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        $data = static::setAuditMessageForOption($this, $data);

        if ($this->auditEvent == 'created') {
            $data['custom_message'] = __('Product created');
            $data['old_values'] = null;
            $data['new_values'] = ['message' => $data['custom_message']];
        } elseif ($this->auditEvent == 'updated') {
            foreach (Arr::get($data, 'new_values') as $attribute => $value) {
                if ($attribute == 'message') {
                    $data['custom_message'] = Arr::get($data, 'new_values.message', '');
                } elseif (in_array($attribute, Product::$columnBoolean)) {
                    if ($attribute == 'priority_counting_requested_at' && !empty($this->getAttribute($attribute)) && !empty($this->getOriginal($attribute))) {
                        $data['old_values']['priority_counting_requested_at'] = null;
                        $data['new_values']['priority_counting_requested_at'] = null;
                    } else {
                        $new = $this->getAttribute($attribute);
                        $new = ($new == true || $new == 1) ? true : false;
                        $to = $new ? __('YES') : __('NO');
                        $from = $new ? __('NO') : __('YES');

                        $data['custom_message'] .= __(':attribute changed from :from to :to <br/>', [
                            'attribute' => Arr::get(static::$columnTitle, $attribute, str_replace('_', ' ', ucfirst($attribute))),
                            'from' => $from,
                            'to' => $to,
                        ]);
                    }
                } elseif ($attribute == 'tags') {
                    $oldTag = Arr::pluck(Arr::get($data, 'old_values.tags'), 'name');
                    $newTag = Arr::pluck(Arr::get($data, 'new_values.tags'), 'name');

                    $addedTag = array_values(array_diff($newTag, $oldTag));
                    $removedTag = array_values(array_diff($oldTag, $newTag));

                    if (!empty($removedTag)) {
                        $data['custom_message'] = __('Removed <em>":tag"</em> :attribute', ['tag' => implode(', ', $removedTag), 'attribute' => count($removedTag) > 1 ? 'tags' : 'tag']);
                    }

                    if (!empty($addedTag)) {
                        $data['custom_message'] = __('Added <em>":tag"</em> :attribute', ['tag' => implode(', ', $addedTag), 'attribute' => count($addedTag) > 1 ? 'tags' : 'tag']);
                    }
                } else {
                    $field = Arr::get(static::$columnTitle, $attribute, str_replace('_', ' ', ucfirst($attribute)));

                    $data['custom_message'] .=  static::setAuditMessage($field, $data, $attribute) . ' <br/>';
                }
            }
        } elseif (in_array($this->auditEvent, ['kit added', 'kit removed', 'kit updated'])) {
            $data['custom_message'] = Arr::get($data, 'new_values.message', '');
        }

        return $data;
    }

    public static function setAuditMessageForOption($model, $data) {
        if (Arr::has($data, 'new_values.type')) {
            $data['old_values']['type'] = Arr::get(static::$productTypes, $model->getOriginal('type'), '');
            $data['new_values']['type'] = Arr::get(static::$productTypes, $model->getAttribute('type'), '');
        }

        if (Arr::has($data, 'new_values.lot_priority')) {
            $data['old_values']['lot_priority'] = $model->getOriginal('lot_priority') == null ? null : Arr::get(LotPriority::translatedValues(), $model->getOriginal('lot_priority'));
            $data['new_values']['lot_priority'] = Arr::get(LotPriority::translatedValues(), $model->getAttribute('lot_priority'), '');
        }

        if (Arr::has($data, 'new_values.country_of_origin')) {
            $data['old_values']['country_of_origin'] = Countries::find($model->getOriginal('country_of_origin'))->name ?? '';
            $data['new_values']['country_of_origin'] = Countries::find($model->getAttribute('country_of_origin'))->name ?? '';
        }

        return $data;
    }

    public static function getAudits(Request $request, Product $product)
    {
        $product = $product->load('audits.user.contactInformation', 'images.audits.user.contactInformation');
        $audits = $product->audits;

        $product->images->map(function($image, $key) use($audits) {
            $image->audits->map(function($audit, $key) use($audits) {
                $audits->push($audit);
            });
        });

        return app('audit')->prepareEachAudits($request, $audits);
    }
}

