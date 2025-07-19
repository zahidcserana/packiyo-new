<?php

namespace App\Models\AutomationActions;

use App\Enums\IsoWeekday;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationActionInterface;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\OrderDateField;
use App\Models\Automations\SetDateFieldActionConfigurationCast;
use App\Models\Automations\SetDateFieldActionConfiguration;
use App\Models\Automations\SetDateFieldActionDayConfiguration;
use App\Models\Automations\SetDateFieldActionMonthConfiguration;
use App\Models\Automations\SetDateFieldActionWeekConfiguration;
use App\Models\Automations\TimeUnit;
use App\Models\Order;
use App\Models\Warehouse;
use App\Traits\inheritanceHasParent;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetDateFieldAction extends AutomationAction implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    protected $fillable = [
        'field_name',
        'unit_of_measure',
        'number_field_value',
        'text_field_values',
    ];

    protected $casts = [
        'field_name' => OrderDateField::class,
        'unit_of_measure' => TimeUnit::class,
        'number_field_value' => 'int',
        'text_field_values' => SetDateFieldActionConfigurationCast::class
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getConfigurationAttribute(): SetDateFieldActionConfiguration|null
    {
        if (!$this->text_field_values) {
            return null;
        }

        $values = json_decode($this->text_field_values, true);

        return match ($this->unit_of_measure) {
            TimeUnit::DAYS => new SetDateFieldActionDayConfiguration($values[SetDateFieldActionConfiguration::TIME_OF_DAY]),
            TimeUnit::WEEKS => new SetDateFieldActionWeekConfiguration(IsoWeekday::from($values[SetDateFieldActionConfiguration::ISO_DAY_OF_WEEK]), $values[SetDateFieldActionConfiguration::TIME_OF_DAY]),
            TimeUnit::MONTHS => new SetDateFieldActionMonthConfiguration($values[SetDateFieldActionConfiguration::DAY_OF_MONTH], $values[SetDateFieldActionConfiguration::TIME_OF_DAY]),
            default => null,
        };
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public static function loadForCommand(): array
    {
        return ['warehouse.contactInformation'];
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();
        $fieldName = $this->field_name->value;
        $order->$fieldName = $this->getValue($order);
        $order->save();
    }

    /**
     * @throws Exception
     */
    private function getValue(Order $order): CarbonImmutable
    {

        // Since we're converting to the warehouse timezone here, when we change to UTC later, to store in the database,
        // the time will already be correct, also taking DST into account.
        $createdAt = $order->created_at->toImmutable()->setTimezone($this->warehouse->timezone());
        $configuration = $this->configuration;

        if ($configuration instanceof SetDateFieldActionConfiguration) {
            return $configuration->calculate($createdAt, $this->number_field_value);
        }

        return match ($this->unit_of_measure) {
            TimeUnit::MINUTES => $createdAt->addMinutes($this->number_field_value)->utc(),
            TimeUnit::HOURS => $createdAt->addHours($this->number_field_value)->utc(),
            TimeUnit::YEARS, TimeUnit::BUSINESS_DAYS => throw new Exception('Not implemented yet'),
        };
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set Date Field';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
