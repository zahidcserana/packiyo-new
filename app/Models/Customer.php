<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\{Builder,
    Collection,
    Factories\HasFactory,
    Model,
    Relations\BelongsToMany,
    Relations\HasMany,
    Relations\HasOne,
    SoftDeletes};
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;
use Laravel\Pennant\Feature;

/**
 * App\Models\Customer
 *
 * @property int $id
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property bool $allow_child_customers
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $trial_ends_at
 * @property int|null $ship_from_contact_information_id
 * @property int|null $return_to_contact_information_id
 * @property-read \App\Models\Image|null $accountFavicon
 * @property-read \App\Models\Image|null $accountLogo
 * @property-read Collection<int, \App\Models\Automation> $appliesToCustomers
 * @property-read int|null $applies_to_customers_count
 * @property-read \App\Models\BillingDetails|null $billingDetails
 * @property-read Collection<int, \App\Models\BulkInvoiceBatch> $bulkInvoiceBatch
 * @property-read int|null $bulk_invoice_batch_count
 * @property-read Collection<int, Customer> $children
 * @property-read int|null $children_count
 * @property-read \App\Models\ContactInformation|null $contactInformation
 * @property-read Collection<int, \App\Models\CustomerSetting> $customerSettings
 * @property-read int|null $customer_settings_count
 * @property-read Collection<int, \App\Models\EasypostCredential> $easypostCredentials
 * @property-read int|null $easypost_credentials_count
 * @property-read Collection<int, \App\Models\ExternalCarrierCredential> $externalCarrierCredentials
 * @property-read int|null $external_carrier_credentials_count
 * @property-read Collection<int, \App\Models\Image> $images
 * @property-read int|null $images_count
 * @property-read Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Collection<int, \App\Models\LocationType> $locationTypes
 * @property-read int|null $location_types_count
 * @property-read Collection<int, \App\Models\OrderChannel> $orderChannels
 * @property-read int|null $order_channels_count
 * @property-read \App\Models\Image|null $orderSlipLogo
 * @property-read Collection<int, \App\Models\OrderStatus> $orderStatuses
 * @property-read int|null $order_statuses_count
 * @property-read Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read Customer|null $parent
 * @property-read Collection<int, \App\Models\Warehouse> $parentWarehouses
 * @property-read int|null $parent_warehouses_count
 * @property-read Collection<int, \App\Models\Printer> $printers
 * @property-read int|null $printers_count
 * @property-read Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @property-read Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @property-read Collection<int, \App\Models\PurchaseOrderStatus> $purchaseOrdersStatuses
 * @property-read int|null $purchase_orders_statuses_count
 * @property-read Collection<int, \App\Models\RateCard> $rateCards
 * @property-read int|null $rate_cards_count
 * @property-read \App\Models\ContactInformation|null $returnToContactInformation
 * @property-read Collection<int, \App\Models\Return_> $returns
 * @property-read int|null $returns_count
 * @property-read Collection<int, \App\Models\CustomerSetting> $settings
 * @property-read int|null $settings_count
 * @property-read \App\Models\ContactInformation|null $shipFromContactInformation
 * @property-read Collection<int, \App\Models\ShippingBox> $shippingBoxes
 * @property-read int|null $shipping_boxes_count
 * @property-read Collection<int, \App\Models\ShippingCarrier> $shippingCarriers
 * @property-read int|null $shipping_carriers_count
 * @property-read Collection<int, \App\Models\ShippingMethod> $shippingMethods
 * @property-read int|null $shipping_methods_count
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read Collection<int, \App\Models\Supplier> $suppliers
 * @property-read int|null $suppliers_count
 * @property-read Collection<int, \App\Models\TaskType> $taskTypes
 * @property-read int|null $task_types_count
 * @property-read Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \App\Models\Image|null $threeplLogo
 * @property-read \App\Models\Image|null $storeLogo
 * @property-read \App\Models\TribirdCredential|null $tribirdCredential
 * @property-read Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property-read Collection<int, \App\Models\Warehouse> $warehouses
 * @property-read int|null $warehouses_count
 * @property-read Collection<int, \App\Models\Webhook> $webhooks
 * @property-read int|null $webhooks_count
 * @property-read Collection<int, \App\Models\WebshipperCredential> $webshipperCredentials
 * @property-read int|null $webshipper_credentials_count
 * @method static \Database\Factories\CustomerFactory factory(...$parameters)
 * @method static Builder|Customer newModelQuery()
 * @method static Builder|Customer newQuery()
 * @method static Builder|Customer onlyTrashed()
 * @method static Builder|Customer query()
 * @method static Builder|Customer whereAllowChildCustomers($value)
 * @method static Builder|Customer whereCreatedAt($value)
 * @method static Builder|Customer whereDeletedAt($value)
 * @method static Builder|Customer whereId($value)
 * @method static Builder|Customer whereParentId($value)
 * @method static Builder|Customer wherePmLastFour($value)
 * @method static Builder|Customer wherePmType($value)
 * @method static Builder|Customer whereReturnToContactInformationId($value)
 * @method static Builder|Customer whereShipFromContactInformationId($value)
 * @method static Builder|Customer whereStripeId($value)
 * @method static Builder|Customer whereTrialEndsAt($value)
 * @method static Builder|Customer whereUpdatedAt($value)
 * @method static Builder|Customer withClients(array|int $customerIds)
 * @method static Builder|Customer withTrashed()
 * @method static Builder|Customer withoutTrashed()
 * @mixin \Eloquent
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes, Billable;

    public const PRIMARY_WAREHOUSE_NAME = 'Primary';

    protected $cascadeDeletes = [
        'contactInformation',
        'orders',
        'orderStatuses',
        'warehouses',
        'purchaseOrders',
        'purchaseOrdersStatuses',
        'suppliers',
        'taskTypes',
        'products',
        'tasks',
        'slug',
        'store_domain',
        'printers'
    ];

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'parent_id',
        'allow_child_customers',
        'slug',
        'store_domain',
        'ship_from_contact_information_id',
        'return_to_contact_information_id',
    ];

    protected $casts = [
        'allow_child_customers' => 'bool'
    ];

    public const WEIGHT_UNITS = [
        'lb' => 'pounds',
        'oz' => 'ounces',
        'kg' => 'kilograms',
        'g' => 'grams',
        'l' => 'litres'
    ];

    public const WEIGHT_UNIT_DEFAULT = 'g';

    public const DIMENSION_UNITS = [
        'in' => 'inches',
        'cm' => 'centimetres'
    ];

    public const DIMENSION_UNIT_DEFAULT = 'cm';

    private $contactInformation;

    public function parent(): HasOne
    {
        return $this->hasOne(__CLASS__, 'id', 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id', 'id');
    }

    public function contactInformation()
    {
        return $this->morphOne(ContactInformation::class, 'object')->withTrashed();
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CustomerSetting::class);
    }

    public function shipFromContactInformation()
    {
        return $this->belongsTo(ContactInformation::class, 'ship_from_contact_information_id', 'id')->withTrashed();
    }

    public function returnToContactInformation()
    {
        return $this->belongsTo(ContactInformation::class, 'return_to_contact_information_id', 'id')->withTrashed();
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(CustomerUser::class)
            ->withPivot([
                'role_id',
                'warehouse_id'
            ]);
    }

    public function orderChannels()
    {
        return $this->hasMany(OrderChannel::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderStatuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function shippingCarriers()
    {
        return $this->hasMany(ShippingCarrier::class)->where('active', true);
    }

    public function shippingMethods()
    {
        return $this->hasManyThrough(ShippingMethod::class, ShippingCarrier::class)->where('active', true);
    }

    public function shippingMethodsWithCarrier()
    {
        $shippingMethods = $this->hasManyThrough(ShippingMethod::class, ShippingCarrier::class)->where('active', true)->get();

        $result = [];

        foreach ($shippingMethods as $shippingMethod) {
            $shippingMethodAndCarrierName = $shippingMethod->name . ' (' . $shippingMethod->shippingCarrier->name . ')';
            $result[$shippingMethod->id] = $shippingMethodAndCarrierName;
        }

        return $result;
    }

    /**
     * Returns owned shipping boxes
     *
     * @return HasMany
     */
    public function shippingBoxes()
    {
        return $this->hasMany(ShippingBox::class);
    }

    /**
     * Returns all available shipping boxes (also from 3PL customer)
     *
     * @return Collection
     */
    public function availableShippingBoxes(): Collection
    {
        $customerIds = [$this->id];

        if ($this->is3plChild()) {
            $customerIds[] = $this->parent_id;
        }

        return ShippingBox::whereIn('customer_id', $customerIds)->get();
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseOrdersStatuses()
    {
        return $this->hasMany(PurchaseOrderStatus::class);
    }

    public function returns()
    {
        return $this->hasMany(Return_::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function taskTypes()
    {
        return $this->hasMany(TaskType::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Checks if a user belongs to the customer or customer's parent
     *
     * @param int $userId
     * @param bool $strict if false - will also check parent customer
     * @return bool
     */
    public function hasUser(int $userId, bool $strict = false): bool
    {
        $users = $this->users;

        if (!$strict && $this->parent) {
            $users = $users->merge($this->parent->users)->unique('id');
        }

        return $users->contains('id', $userId);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function pathaoCredentials()
    {
        return $this->hasMany(PathaoCredential::class);
    }

    public function webshipperCredentials()
    {
        return $this->hasMany(WebshipperCredential::class);
    }

    public function easypostCredentials()
    {
        return $this->hasMany(EasypostCredential::class);
    }

    public function externalCarrierCredentials(): HasMany
    {
        return $this->hasMany(ExternalCarrierCredential::class);
    }

    public function tribirdCredential(): HasOne
    {
        return $this->hasOne(TribirdCredential::class);
    }

    public function orderSlipLogo()
    {
        return $this->morphOne(Image::class, 'object')->where('image_type', 'order_slip_logo')->latest();
    }

    public function threeplLogo()
    {
        return $this->morphOne(Image::class, 'object')->where('image_type', 'threepl_logo')->latest();
    }

    public function storeLogo()
    {
        return $this->morphOne(Image::class, 'object')->where('image_type', 'store_logo')->latest();
    }

    public function bannerImages()
    {
        return $this->morphOne(Image::class, 'object')->where('image_type', 'store_logo');
    }

    public function accountLogo()
    {
        return $this->morphOne(Image::class, 'object')->where('image_type', 'account_logo')->latest();
    }

    public function accountFavicon()
    {
        return $this->morphOne(Image::class, 'object')->where('image_type', 'account_favicon')->latest();
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'object');
    }

    public function printers()
    {
        return $this->hasMany(Printer::class);
    }

    public function customerSettings()
    {
        return $this->hasMany(CustomerSetting::class);
    }

    public function labelPrinter()
    {
        return $this->getPrinter(CustomerSetting::CUSTOMER_SETTING_LABEL_PRINTER_ID);
    }

    public function barcodePrinter()
    {
        return $this->getPrinter(CustomerSetting::CUSTOMER_SETTING_BARCODE_PRINTER_ID);
    }

    public function slipPrinter()
    {
        return $this->getPrinter(CustomerSetting::CUSTOMER_SETTING_SLIP_PRINTER_ID);
    }

    private function getPrinter($printerType)
    {
        $printer = Printer::find(customer_settings($this->id, $printerType));

        if (!$printer && $this->parent) {
            return $this->parent->getPrinter($printerType);
        }

        return $printer;
    }

    public function locationTypes(): HasMany
    {
        return $this->hasMany(LocationType::class);
    }

    public function parentWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'customer_id', 'parent_id');
    }

    public function billingDetails(): HasOne
    {
        return $this->hasOne(BillingDetails::class);
    }

    public function rateCards(): BelongsToMany
    {
        return $this->belongsToMany(RateCard::class);
    }

    public function primaryRateCard(): ?RateCard
    {
        return $this->rateCards()->wherePivot('priority', RateCard::PRIMARY_RATE_CARD_PRIORITY)->first();
    }

    public function secondaryRateCard(): ?RateCard
    {
        return $this->rateCards()->wherePivot('priority', RateCard::SECONDARY_RATE_CARD_PRIORITY)->first();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getWarehouses(): Collection
    {
        return $this->warehouses->merge($this->parentWarehouses);
    }

    public function isNotChild(): bool
    {
        return is_null($this->parent_id) ?? false;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function isStandalone(): bool
    {
        return $this->isNotChild() && !$this->allow_child_customers;
    }

    public function is3pl(): bool
    {
        return $this->isNotChild() && $this->allow_child_customers;
    }

    public function is3plChild(): bool
    {
        return !is_null($this->parent_id);
    }

    public function lastBill()
    {
        return $this->invoices()->orderBy('period_end', 'desc')->first();
    }

    public function hasFeature(string $featureName): bool
    {
        return Feature::for($this)->active($featureName);
    }

    public function appliesToCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Automation::class, 'automation_applies_to_customer');
    }

    public function bulkInvoiceBatch(): HasMany
    {
        return $this->hasMany(BulkInvoiceBatch::class);
    }

    public function scopeWithClients(Builder $query, array|int $customerIds): Builder
    {
        if (!is_array($customerIds)) {
            $customerIds = [$customerIds];
        }

        return $query->whereIn('id', $customerIds)
            ->orWhereIn('parent_id', $customerIds);
    }
}
