<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnassignAutomation extends Command
{
    protected $signature = 'automation:unassign';

    protected $description = 'Unassign automations from a customer.';

    public function handle(): int
    {
        $chosen3Pl = $this->get3Pls(self::getOwnerCustomerChoices());
        $chosenCustomer = $this->getCustomer($chosen3Pl);

        $customer = $chosen3Pl
            ->children()
            ->whereHas('contactInformation', function (Builder $query) use (&$chosenCustomer) {
                $query->where('name', $chosenCustomer);
            })
            ->firstOrFail(['id', 'parent_id']);

        $chosenAutomations = $this->getAutomations($customer);

        $automations = Automation::query()
            ->whereIn('name', $chosenAutomations)
            ->get();

        $lockedAutomations = $this->getLockedAutomations($automations);

        if ($lockedAutomations->isNotEmpty()) {
            return $this->failByLocked($lockedAutomations);
        }

        $this->unassignAutomations($customer, $automations);

        return 0;
    }

    protected function get3Pls(array $customerChoices): Customer
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

    public function getCustomer(Customer $chosen3Pl): string|array
    {
        return $this->choice(
            __('Which customer do you want to unassign automations from?'),
            $chosen3Pl->children()->with('contactInformation')->get()->mapWithKeys(
                fn(Customer $customer) => [$customer->id => $customer->contactInformation->name]
            )->toArray()
        );
    }

    public function getAutomations(
        Customer $customer
    ): string|array {
        return $this->choice(
            __('Which automations do you want to unassign? (Separate multiple with commas.)'),
            DB::table('automation_applies_to_customer')
                ->join('automations', 'automation_applies_to_customer.automation_id', '=', 'automations.id')
                ->where('automation_applies_to_customer.customer_id', $customer->id)
                ->get(['automations.id', 'automations.name'])
                ->map(fn($automation) => $automation->name)
                ->toArray(),
            multiple: true
        );
    }

    private function getLockedAutomations(Collection $automations): Collection
    {
        return $automations->filter(fn (Automation $automation) => $automation->isLocked())->pluck('name');
    }

    protected function unassignAutomations(Customer $customer, Collection $automations): void
    {
        $automations->each(fn (Automation $automation) => Automation::removeCustomer($automation, $customer));
        $this->info(__('The automations were unassigned from the chosen customer.'));
    }

    /**
     * @param  Collection  $lockedAutomations
     * @return int
     */
    public function failByLocked(Collection $lockedAutomations): int
    {
        $this->error(__('The following automations are locked and cannot be unassigned: :automations', [
            'automations' => $lockedAutomations->join(', ')
        ]));
        return 1;
    }

}
