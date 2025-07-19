<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Automations\AppliesToCustomers;
use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ListAutomations extends Command
{
    protected const TABLE_HEADERS = ['ID', 'Name', 'Enabled', 'Applies To', 'Clients', 'Position', 'Events', 'Conditions', 'Actions'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:list {customer} {--A|apply-to-any} {--E|only-enabled} {--D|only-disabled} {--C|char-limit=80} {--F|for-client=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List automations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $forClients = $this->option('for-client');
        $ownerCustomer = Customer::findOrFail($this->argument('customer'));

        if (!empty($forClients) && !$ownerCustomer->is3pl()) {
            $this->error(__('Non-3PLs have no clients.'));

            return 1;
        } elseif (in_array($ownerCustomer->id, $forClients)) {
            $this->error(__('Owning customer ID :id was passed as target client as well.', ['id' => $ownerCustomer->id]));

            return 2;
        }

        if (Automation::where('customer_id', $ownerCustomer->id)->exists()) {
            $this->listAutomations($ownerCustomer, $forClients);
        } else {
            $this->line('No automations found.');
        }

        return 0;
    }

    protected function listAutomations(Customer $ownerCustomer, ?array $forClients = null): void
    {
        $onlyEnabled = $this->option('only-enabled');
        $onlyDisabled = $this->option('only-disabled');
        $applyToAny = $this->option('apply-to-any');
        $charLimit = $this->option('char-limit');
        $headers = array_map(fn (string $header) => __($header), static::TABLE_HEADERS);
        $rows = Automation::where('customer_id', $ownerCustomer->id);

        if ($onlyEnabled) {
            $rows = $rows->where('is_enabled', true);
        } elseif ($onlyDisabled) {
            $rows = $rows->where('is_enabled', false);
        }

        if ($forClients) {
            $rows = static::filterByAppliesTo($rows, $forClients);
        }

        $rows = $rows->orderBy('position')->get();

        if ($forClients && !$applyToAny) {
            $rows = self::filterForNotApplyToAny($rows, $forClients);
        }

        $rows = $rows->map(fn (Automation $automation) => static::getRowCells($automation, $charLimit))
            ->toArray();

        $this->table($headers, $rows);
    }

    protected static function filterByAppliesTo(Builder $rows, array $forClients): Builder
    {
        return $rows->where(
            fn (Builder $query) => $query
                ->where('applies_to', AppliesToCustomers::ALL)
                ->orWhere(
                    fn (Builder $query) => $query
                        ->where('applies_to', AppliesToCustomers::SOME)
                        ->whereHas(
                            'appliesToCustomers',
                            fn (Builder $query) => $query->whereIn('customer_id', $forClients)
                        )
                )
                ->orWhere(
                    fn (Builder $query) => $query
                        ->where('applies_to', AppliesToCustomers::NOT_SOME)
                        ->whereDoesntHave(
                            'appliesToCustomers',
                            fn (Builder $query) => $query->whereIn('customer_id', $forClients)
                        )
                )
        );
    }

    protected static function filterForNotApplyToAny(Collection $rows, array $forClients): Collection
    {
        $forClients = collect($forClients);

        return $rows->filter(function (Automation $automation) use ($forClients) {
            return in_array($automation->applies_to, [AppliesToCustomers::ALL, AppliesToCustomers::OWNER])
                || ($automation->applies_to == AppliesToCustomers::SOME
                    && $forClients->diff($automation->appliesToCustomers->pluck('id'))->isEmpty())
                || ($automation->applies_to == AppliesToCustomers::NOT_SOME
                    && !$automation->appliesToCustomers->first(fn (Customer $customer) => $forClients->contains($customer->id)));
        });
    }

    protected static function getRowCells(Automation $automation, int $charLimit): array
    {
        return [
            $automation->id,
            Str::limit($automation->name, $charLimit, '...'),
            $automation->is_enabled ? __('yes') : __('no'),
            $automation->applies_to->value,
            $automation->appliesToCustomers->count(),
            $automation->position,
            Str::limit(implode(', ', $automation->target_events), $charLimit, '...'),
            $automation->conditions->count(),
            $automation->actions->count()
        ];
    }
}
