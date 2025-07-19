<?php

namespace App\Models\AutomationActions;

use App\Events\PurchaseOrderClosedEvent;
use App\Events\PurchaseOrderReceivedEvent;
use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomatableOperation;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Interfaces\ItemQuantities;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\DebitsBillingCharges;
use App\Models\BillingCharges\AdHocCharge;
use App\Models\BillingRate;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ChargeAdHocRateAction extends AutomationAction implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany, DebitsBillingCharges;

    private const HOUR_IN_MINUTES = 60;
    private const HALF_AN_HOUR_IN_MINUTES = 30;

    protected $fillable = [
        'minimum',
        'tolerance',
        'threshold'
    ];

    public function billingRate(): BelongsTo
    {
        // TODO: Ad hoc rates need to be properly typed.
        return $this->belongsTo(BillingRate::class, 'billing_rate_id');
    }

    public static function getSupportedEvents(): array
    {
        return [PurchaseOrderReceivedEvent::class, PurchaseOrderClosedEvent::class];
    }

    public static function loadForCommand(): array
    {
        return ['billingRate'];
    }

    public function run(AutomatableEvent $event): void
    {
        $operation = $event->getOperation();
        $quantity = 0;

        if (in_array($this->billingRate->settings['unit'], ['orders', 'purchase orders'])) {
            $quantity = 1;
        } elseif ($this->billingRate->settings['unit'] == 'units') {
            $quantity = $this->quantityByUnits($operation);
        } elseif ($this->billingRate->settings['unit'] == 'hours') {
            $quantity = $this->quantityByHours($operation);
        } else {
            throw new AutomationException('The ad hoc unit "' . $this->billingRate->settings['unit'] . '" is unkown.');
        }

        $amount = $this->billingRate->settings['fee'] * $quantity;
        $charge = new AdHocCharge([
            // TODO: 'description' => 'The temporary description of the ad hoc rate.',
            'description' => '',
            'quantity' => $quantity,
            'amount' => $amount
        ]);
        $charge->billingRate()->associate($this->billingRate);

        // TODO: We should be getting the specific warehouse from the operation.
        $this->debitCharge($operation->customer->parent->warehouses->first(), $operation->customer, $charge);
    }

    protected function quantityByUnits(AutomatableOperation $operation): float
    {
        $quantity = 0;

        if (!$operation instanceof ItemQuantities) {
            throw new AutomationException('The operation must implement "' . ItemQuantities::class . '".');
        }

        foreach ($operation->items() as $item) {
            $quantity += $item->quantity; // TODO: Make common.
        }

        return $quantity;
    }

    protected function quantityByHours(AutomatableOperation $operation): float
    {
        $hours = 0;

        if ($operation instanceof PurchaseOrder) {
            $hours = $this->quantityByTimeToReceive($operation);
        } else {
            throw new AutomationException('The operation type "' . $operation::class . '" is not yet supported.');
        }

        return $hours;
    }

    protected function quantityByTimeToReceive(PurchaseOrder $operation): float
    {
        $hours = 0.0;
        $audits = $operation->audits()->where('event', 'po-item-received')->orderBy('created_at')->get();

        if ($audits->count() == 1) {
            $hours = $this->minimum;
        } elseif ($audits->count() > 1) {
            $hours = $this->hoursFromAudits($audits);
        }

        return $this->applyThreshold($hours);
    }

    protected function hoursFromAudits(Collection $audits): float
    {
        // TODO: Consider dead-times between the first and last reception.
        $first = $audits->first();
        $last = $audits->last();
        $interval = $last->created_at->diffInMinutes($first->created_at);
        $hours = (int) floor($interval / static::HOUR_IN_MINUTES);
        $minutes = (int) $interval % static::HOUR_IN_MINUTES;

        // TODO: Make rounding configurable. Here we round up to the next half an hour.
        if ($minutes > (static::HALF_AN_HOUR_IN_MINUTES + $this->tolerance)) {
            $hours += 1.0;
        } elseif ($minutes > $this->tolerance) {
            $hours += 0.5;
        }

        return $hours;
    }

    protected function applyThreshold(float $hours): float
    {
        if ($this->threshold >= $hours) {
            $hours = 0.0;
        } else {
            $hours -= $this->threshold;
        }

        return $hours;
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Charge Ad Hoc rate';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
