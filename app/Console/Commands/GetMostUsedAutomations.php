<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetMostUsedAutomations extends Command
{
    protected const CONDITIONS_HEADERS = ['Condition Type', 'Qtd Created', 'Qtd. Ran'];
    protected const ACTIONS_HEADERS = ['Action Type', 'Qtd Created', 'Qtd. Ran'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:most-used';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the most used automation conditions / actions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->listMostUsedAutomations();
        return 0;
    }

    private function getMostUsedConditions(): Collection
    {
        return DB::table('automation_triggers')
            ->select(
                'automation_triggers.type',
                DB::raw('COUNT(DISTINCT automation_triggers.id) AS qtd_created'),
                DB::raw('COUNT(automation_acted_on_operation.id) AS qtde_ran')
            )
            ->join('automations', 'automations.id', '=', 'automation_triggers.automation_id')
            ->leftJoin('automation_acted_on_operation', 'automation_acted_on_operation.automation_id',
                '=', 'automation_triggers.automation_id')
            ->where('automations.is_enabled', 1)
            ->groupBy('automation_triggers.type')
            ->orderBy(DB::raw('COUNT(DISTINCT automation_triggers.id)'), 'desc')
            ->get();
    }

    private function getMostUsedActions(): Collection
    {
        return DB::table('automation_actions')
            ->select(
                'automation_actions.type',
                DB::raw('COUNT(DISTINCT automation_actions.id) AS qtd_created'),
                DB::raw('COUNT(automation_acted_on_operation.id) AS qtde_ran')
            )
            ->join('automations', 'automations.id', '=', 'automation_actions.automation_id')
            ->leftJoin('automation_acted_on_operation', 'automation_acted_on_operation.automation_id',
                '=', 'automation_actions.automation_id')
            ->where('automations.is_enabled', 1)
            ->groupBy('automation_actions.type')
            ->orderBy(DB::raw('COUNT(DISTINCT automation_actions.id)'), 'desc')
            ->get();
    }

    protected function listMostUsedAutomations(): void
    {
        $conditions = $this->getMostUsedConditions();
        $actions = $this->getMostUsedActions();

        $rows = $conditions->map(fn ($condition) => static::getRowCells($condition->type, $condition->qtd_created, $condition->qtde_ran))
            ->toArray();

        $this->table(static::CONDITIONS_HEADERS, $rows);

        $rows = $actions->map(fn ($actions) => static::getRowCells($actions->type, $actions->qtd_created, $actions->qtde_ran))
            ->toArray();

        $this->table(static::ACTIONS_HEADERS, $rows);
    }

    protected static function getRowCells(string $type, int $qty_created, int $qty_ran): array
    {
        return [
            $type,
            $qty_created,
            $qty_ran
        ];
    }
}
