<?php

namespace App\Models;

use App\Components\Invoice\DataTransferObjects\BillableOperationDto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Webpatser\Countries\Countries;

/**
 * Class BillingRate
 * @package App\Models
 */
class BillingRate extends Model
{
    use SoftDeletes, HasFactory;

    public const STORAGE_BY_LOCATION = 'storage_by_location';
    public const SHIPMENTS_BY_SHIPPING_LABEL = 'shipments_by_shipping_label';
    public const SHIPMENTS_BY_PICKING_RATE_V2 = 'shipments_by_picking_rate_v2';
    public const SHIPMENT_BY_BOX = 'shipments_by_box'; // Copilot-based billing rate.
    public const AD_HOC = 'ad_hoc';
    public const PURCHASE_ORDER = 'purchase_order';
    public const PACKAGING_RATE = 'packaging_rate';

    public const BILLING_RATE_TYPES = [
        self::AD_HOC => ['title' => 'Ad hoc', 'folder' => 'ad_hoc'],
        self::SHIPMENTS_BY_SHIPPING_LABEL => ['title' => 'Shipping', 'folder' => 'shipments_by_shipping_label'],
        self::SHIPMENTS_BY_PICKING_RATE_V2 => ['title' => 'Picking', 'folder' => 'shipments_by_picking_rate_v2'],
        self::STORAGE_BY_LOCATION => ['title' => 'Storage', 'folder' => 'storage_by_location'],
        self::PACKAGING_RATE => ['title' => 'Packaging', 'folder' => 'packaging_rate'],
        // self::PURCHASE_ORDER => ['title' => 'Purchase Order', 'folder' => 'purchase_order'],
    ];
    public const DOC_DB_ONLY_RATES = [
        self::PURCHASE_ORDER
    ];

    public const SHIPMENT_OPERATION_RATES = [
        self::SHIPMENTS_BY_SHIPPING_LABEL,
        self::SHIPMENTS_BY_PICKING_RATE_V2,
        self::PACKAGING_RATE
    ];

    public const STORAGE_OPERATION_RATES = [
        self::STORAGE_BY_LOCATION,
    ];

    public const RECEIVING_OPERATION_RATES = [
        self::PURCHASE_ORDER,
    ];

    public const PERIODS = [ 'day', 'week', 'month' ];
    public const WEIGHT_UNITS = [ 'g', 'kg', 'oz', 'lb' ];
    public const VOLUME_UNITS = [ 'cubic inch', 'cubic feet', 'cubic cm', 'cubic m' ];
    public const AD_HOC_UNITS = [ 'hours', 'orders', 'purchase orders', 'units' ];

    protected $fillable = [
        'is_enabled',
        'name',
        'rate_card_id',
        'type',
        'settings',
        'code'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }

    public function getShippingCarrierFromSettings(): mixed
    {
        $shippingCarrierId = Arr::get($this->settings, 'shipping_carrier_id');

        if (empty($shippingCarrierId)) {
            return [];
        }

        return ShippingCarrier::find($shippingCarrierId) ?? [];
    }

    public function getShippingCarrierMethodsFromSettings()
    {
        $shippingCarrier = $this->getShippingCarrierFromSettings();

        if (empty($shippingCarrier)) {
            return [];
        }

        return $shippingCarrier->shippingMethods ?? [];
    }

    public function getCountries($countryIds)
    {
        if (empty($countryIds)) {
            return [];
        }

        return Countries::whereIn('id', $countryIds)->get()->keyBy('id');
    }

    public function getAllSelectedCountries()
    {
        $countries = new Collection();

        $shippingZones = Arr::get($this->settings, 'shipping_zones', []);

        foreach ($shippingZones as $shippingZone) {
            $zoneCountryIds = $shippingZone['countries'] ?? [];
            $zoneCountries = Countries::whereIn('id', $zoneCountryIds)->get();
            $countries = $countries->merge($zoneCountries);
        }

        return $countries->keyBy('id');
    }

    public function getAllSelectedShippingMethods()
    {
        $shippingMethodIds = Arr::get($this->settings, 'shipping_method');

        if (empty($shippingMethodIds)) {
            return [];
        }

        return ShippingMethod::whereIn('id', $shippingMethodIds)->get()->keyBy('id');
    }

    public function getBillableOperationType(): string
    {
        switch (true){
            case in_array($this->type, self::SHIPMENT_OPERATION_RATES):
                return BillableOperationDto::FULFILLMENT_TYPE;
            case in_array($this->type, self::STORAGE_OPERATION_RATES):
                return BillableOperationDto::STORAGE_TYPE;
            case in_array($this->type, self::RECEIVING_OPERATION_RATES):
                return BillableOperationDto::RECEIVING_TYPE;
            default:
                return '';
        }
    }
}
