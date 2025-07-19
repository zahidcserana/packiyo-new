<?php

namespace App\Console\Commands;

use App\Exceptions\AutomationException;
use App\Models\Automation;
use App\Models\Automations\AppliesToCustomers;
use App\Models\Customer;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class AssignAutomation extends Command
{
    protected $signature = 'automation:assign';

    protected $description = 'Assign an automation to customers.';

    public function handle(): int
    {
        $chosenCustomer = $this->getCustomer(self::getOwnerCustomerChoices());
        $chosenAutomation = $this->getAutomation($chosenCustomer);

        $automation = Automation::query()
            ->where('customer_id', $chosenCustomer->id)
            ->where('name', $chosenAutomation)
            ->firstOrFail();

        if ($automation->isLocked()) {
            $this->error('This automation is locked and cannot be assigned to customers.');
            return 1;
        }

        $chosenTargetCustomers = $this->getChosenTargetCustomers(self::getTargetCustomerChoices($automation->id, $chosenCustomer->id), AppliesToCustomers::SOME);

        $this->assignAutomation($automation, $chosenTargetCustomers);

        return 0;
    }

    protected function getCustomer(array $customerChoices): Customer
    {
        $customerName = $this->anticipate(
            __('Which customer?'),
            $customerChoices
        );

        return Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
    }

    protected static function getOwnerCustomerChoices(): array
    {
        return Customer::with(['contactInformation'])
            ->whereNull('parent_id')
            ->has('contactInformation')
            ->get()
            ->mapWithKeys(fn (Customer $customer) => [$customer->id => $customer->contactInformation->name])
            ->toArray();
    }

    public function getAutomation(Customer $chosenCustomer): string|array
    {
        return $this->choice(
            __('Which automation do you want to assign?'),
            Automation::query()
                ->where('customer_id', $chosenCustomer->id)
                ->whereNotIn('applies_to', [AppliesToCustomers::OWNER, AppliesToCustomers::ALL])
                ->pluck('name')
                ->toArray()
        );
    }

    protected function getAppliesToChoice(): AppliesToCustomers
    {
        return AppliesToCustomers::from($this->choice(
            __('Which clients should the automation apply to?'),
            collect(AppliesToCustomers::cases())
                ->filter(fn (AppliesToCustomers $appliesTo) => $appliesTo != AppliesToCustomers::OWNER)
                ->pluck('value')
                ->toArray()
        ));
    }

    protected static function getTargetCustomerChoices(int $automationId, int $threePlId): Collection
    {
        $result = Customer::query()
            ->leftJoin('automation_applies_to_customer', function(JoinClause $join) use ($automationId) {
                $join->on('customers.id', '=', 'automation_applies_to_customer.customer_id')
                    ->where('automation_applies_to_customer.automation_id', '=', $automationId);
            })
            ->join('contact_informations', function (JoinClause $join) {
                $join->on('customers.id', '=', 'contact_informations.object_id')
                    ->where('contact_informations.object_type', '=', Customer::class);
            })
            ->where('parent_id', $threePlId)
            ->where('allow_child_customers', false)
            ->whereNull('automation_applies_to_customer.customer_id') // Only include customers who do not have the automation assigned
            ->get()
            ->mapWithKeys(fn (Customer $customer) => [$customer->id => $customer->name]);

        if ($result->isEmpty()) {
            throw new Exception('No customers available to assign the automation to.');
        }

        return $result;
    }

    protected function getChosenTargetCustomers(Collection $customerChoices, AppliesToCustomers $appliesTo): EloquentCollection
    {
        $customerNames = $this->choice(
            $appliesTo == AppliesToCustomers::NOT_SOME
                ? __('Which ones should be excluded? (Case-sensitive, comma-separated, tab to auto-complete.)')
                : __('Which ones should be included? (Case-sensitive, comma-separated, tab to auto-complete.)'),
            $customerChoices->toArray(),
            null, // Default
            null, // Attempts
            true // Multiple
        );

        return Customer::whereHas('contactInformation', function (Builder $query) use (&$customerNames) {
            $query->whereIn('name', $customerNames);
        })->get();
    }

    /**
     * @throws AutomationException
     */
    protected function assignAutomation(Automation $automation, Collection $chosenTargetCustomers): void
    {
        $chosenTargetCustomers->each(fn (Customer $customer) => Automation::addCustomer($automation, $customer, false));
        $this->line('The automation was assigned to the chosen customers.');
    }
}
