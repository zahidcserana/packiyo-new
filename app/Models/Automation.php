<?php

namespace App\Models;

use App\Components\Automation\AutomationContext;
use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomatableOperation;
use App\Interfaces\AutomationActionInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationActions\SetShippingMethodAction;
use App\Models\Automations\AppliesToCustomers;
use App\Models\Automations\AppliesToSingle;
use App\Models\Automations\AutomationUpdater;
use App\Models\Automations\LogsAutomatedActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use LogicException;
use NumberFormatter;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Parental\HasChildren;

class Automation extends Model implements AuditableInterface
{
    use HasChildren, LogsAutomatedActions, SoftDeletes, AuditableTrait;

    public const AUTOMATION_USER_EMAIL = 'co-pilot@packiyo.com';
    public const AUTOMATION_USER_NAME = 'Co-Pilot';
    public const AUTOMATION_USER_COMPANY = 'Packiyo';

    protected $fillable = [
        'type',
        'name',
        'is_enabled',
        'applies_to',
        'position',
        'target_events',
        'original_revision_automation_id'
    ];

    protected $casts = [
        'target_events' => 'array',
        'is_enabled' => 'bool',
        'applies_to' => AppliesToCustomers::class
    ];

    protected $attributes = [
        'is_enabled' => false
    ];

    public function getOrderAttribute()
    {
        $locale = 'en_US'; // TODO: Make dynamic.
        $formatter = new NumberFormatter($locale, NumberFormatter::ORDINAL);

        return $formatter->format($this->position);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->with('contactInformation')->withTrashed();
    }

    public function originalRevision(): BelongsTo
    {
        return $this->belongsTo(Automation::class, 'original_revision_automation_id')->withTrashed();
    }

    public function previousRevision(): BelongsTo
    {
        return $this->belongsTo(Automation::class, 'previous_revision_automation_id')->withTrashed();
    }

    public function revisions(): BelongsToMany
    {
        return $this->belongsToMany(
            Automation::class,
            'automation_revisions',
            'original_revision_automation_id',
            'automation_id',
            'original_revision_automation_id',
            'id'
        )->withTrashed();
    }

    public function appliesToCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'automation_applies_to_customer');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(AutomationCondition::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class);
    }

    public function actedOnOperations(bool $latestRevision = false): BelongsToMany
    {
        return $this->belongsToMany(
            static::getOperationClass(),
            'automation_acted_on_operation',
            $latestRevision ? 'automation_id' : 'original_revision_automation_id',
            'operation_id',
            $latestRevision ? 'id' : 'original_revision_automation_id',
            'id'
        )
        ->wherePivot('operation_type', static::getOperationClass());
    }

    public function groupAction(): BelongsTo
    {
        return $this->belongsTo(AutomationAction::class);
    }

    public static function buildFromCommand(
        string $type,
        Customer $ownerCustomer,
        Collection $chosenEvents,
        string $name,
        bool $isEnabled,
        Collection $addedConditions,
        Collection $addedActions,
        AppliesToCustomers|null $appliesTo = null,
        Collection|null $chosenTargetCustomers = null
    ): static
    {
        $automation = new Automation([
            'type' => $type,
            'target_events' => $chosenEvents->map(fn (string|array $event) => is_array($event) ? $event[0] : $event),
            'applies_to' => $ownerCustomer->isStandalone() ? AppliesToCustomers::OWNER : $appliesTo,
            'name' => $name,
            'is_enabled' => $isEnabled
        ]);
        $automation->position = Automation::where('customer_id', $ownerCustomer->id)->count() + 1;
        $automation->customer()->associate($ownerCustomer);
        $automation->save();
        $automation->originalRevision()->associate($automation);
        $automation->save();
        $automation->revisions()->attach($automation->id);

        $chosenEvents->map(fn (string|array $event)
            => is_array($event) && $event[1]->automation()->associate($automation)->save());

        $addConditionOrAction = fn (
            AutomationConditionInterface|AutomationActionInterface|array $conditionOrAction,
            int $index
        ) => self::addConditionOrAction($automation, $conditionOrAction, $index);
        $addedConditions->map($addConditionOrAction);
        $addedActions->map($addConditionOrAction);

        if (!is_null($chosenTargetCustomers) && !$chosenTargetCustomers->isEmpty()) {
            $chosenTargetCustomers->map(fn (Customer $customer) => self::addCustomer($automation, $customer));
        }

        return $automation;
    }

    public function revise()
    {
        return new AutomationUpdater($this);
    }

    protected static function addConditionOrAction(
        Automation                                                   $automation,
        AutomationConditionInterface|AutomationActionInterface|array $conditionOrAction,
        int                                                          $index
    ): void {
        if (is_array($conditionOrAction)) {
            [$conditionOrAction, $callback] = $conditionOrAction;
        }

        $conditionOrAction->position = $index + 1;
        $conditionOrAction->automation()->associate($automation)->save();

        if (isset($callback)) {
            if (!is_callable($callback)) {
                throw new LogicException('Closure was expected.');
            }

            $callback($conditionOrAction);
        }
    }

    public function match(AutomatableEvent $event): bool
    {
        if (!$this->is_enabled) {
            throw new AutomationException('Cannot run disabled "'. $this->name . '" automation.');
        }

        return !$this->conditions->count()
            || $this->conditions
                ->sortBy('position')
                ->map(fn (AutomationCondition $condition) => $condition->matchForLink($event))
                ->reduce(fn (AutomationConditionResult|null $carry, AutomationConditionResult $result)
                    => is_null($carry) ? $result : $result->link($carry))
                ->toBool();
    }

    /**
     * @throws AutomationException
     */
    public function run(AutomatableEvent $event): void
    {
        if (!$this->is_enabled) {
            throw new AutomationException('Cannot run disabled "'. $this->name . '" automation.');
        }

        if (!in_array($event::class, $this->target_events)) {
            throw new AutomationException(
                'Automation "' . $this->name . '" does not target the "' . $event::class . '" event.'
            );
        }

        $operation = $event->getOperation();

        if ($this->match($event)) {
            $automationContext = App::make(AutomationContext::class);
            $automationContext->run($this, function () use ($operation, $event) {
                $this->actions()
                    ->orderBy('position')
                    ->get()
                    ->map(fn(AutomationAction $action) => $action->run($event));
                $this->tagOperation($operation);
                $this->logAction($operation, $event);
            });
        }
    }

    public function move(int $newPosition): void
    {
        if ($newPosition < 1) {
            throw new AutomationException(
                'The position of an automation must be a positive integer; ' . $newPosition . ' given.'
            );
        }

        [$oldPositionOperator, $newPositionOperator, $methodName] = [null, null, null];

        if ($this->position < $newPosition) {
            [$oldPositionOperator, $newPositionOperator, $methodName] = ['>', '<=', 'decrement'];
        } elseif ($this->position > $newPosition) {
            [$oldPositionOperator, $newPositionOperator, $methodName] = ['<', '>=', 'increment'];
        }

        if ($this->position != $newPosition) {
            $lastPosition = Automation::where('customer_id', $this->customer_id)->max('position');

            if ($newPosition > $lastPosition) {
                $newPosition = $lastPosition;
            }

            $oldPosition = $this->position;
            $this->position = 0; // Temporarily move.
            $this->save();

            Automation::where('customer_id', $this->customer_id)
                ->where('position', $oldPositionOperator, $oldPosition)
                ->where('position', $newPositionOperator, $newPosition)
                ->$methodName('position');

            $this->position = $newPosition;
            $this->save();
        }
    }

    public function appliesToOne(): bool
    {
        return $this->applies_to == AppliesToCustomers::OWNER
            || (AppliesToCustomers::SOME && $this->appliesToCustomers->count() === 1);
    }

    /**
     * @throws AutomationException
     */
    protected static function addCustomer(Automation $automation, Customer $customer, bool $isNew = true): void
    {
        if (!$isNew && $automation->isLocked()) {
            throw new AutomationException('This automation is locked and cannot be assigned to customers.');
        }
        $automation->validateCustomer($customer);
        $automation->appliesToCustomers()->attach($customer->id);
    }

    protected static function removeCustomer(Automation $automation, Customer $customer): void
    {
        if ($automation->isLocked()) {
            throw new AutomationException('This automation is locked to this customer and cannot be removed.');
        }
        $automation->validateCustomer($customer);
        $automation->appliesToCustomers()->detach($customer->id);
    }

    public function validateCustomer(Customer $customer): bool
    {
        if ($this->customer->isStandalone()) {
            throw new AutomationException('The automation is not owned by a 3PL, cannot add target customer.');
        } elseif ($this->applies_to == AppliesToCustomers::ALL) {
            throw new AutomationException('The automation applies to all clients, cannot add target customer.');
        } elseif ($this->customer_id != $customer->parent_id) {
            throw new AutomationException('The given customer is not a client of the 3PL that owns the automation.');
        }
        return true;
    }

    /**
     * Determines if the automation is locked and cannot be assigned to multiple customers or removed from the original customer.
     * An automation is considered locked if it has any conditions or actions that use the "applies to single" trait.
     * This means that the automation is not intended to be assigned to more than one customer, or removed from him.
     */
    public function isLocked(): bool
    {
        return $this->applies_to === AppliesToCustomers::SOME && ($this->lockedByConditions() || $this->lockedByActions());
    }

    private function lockedByActions(): bool
    {
        return $this->actions->contains(function (AutomationAction $action) {
            $appliesToSingle = $this->usesAppliesToSingle($action);

            if ($appliesToSingle) {
                return true;
            }

            if ($action instanceof SetShippingMethodAction) {
                return $this->shippingMethodBelongsToClient($action);
            }

            return false;
        });
    }

    private function lockedByConditions(): bool
    {
        return $this->conditions->contains(function (AutomationCondition $condition) {
            return $this->usesAppliesToSingle($condition);
        });
    }

    private function usesAppliesToSingle(object $object): bool
    {
        return in_array(AppliesToSingle::class, class_uses_recursive($object));
    }

    private function shippingMethodBelongsToClient(SetShippingMethodAction $shippingMethod): bool
    {
        return $shippingMethod->shippingMethod->shippingCarrier->customer_id !== $this->customer_id;
    }

    public function enable(): bool
    {
        $this->is_enabled = true;
        return $this->save();
    }

    public function disable() : bool
    {
        $this->is_enabled = false;
        return $this->save();
    }
}
