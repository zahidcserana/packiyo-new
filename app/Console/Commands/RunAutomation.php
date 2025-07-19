<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Automations\OrderAutomation;
use Illuminate\Console\Command;

class RunAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:run {automation} {operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run an automation.';

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
        $automation = Automation::find($this->argument('automation'));
        $operation = $automation::getOperationClass()::find($this->argument('operation'));

        if (is_null($automation)) {
            $this->error(__('No automation with that ID.'));

            return 1;
        } elseif (
            !$automation->is_enabled
            && !$this->confirm(__('The automation is disabled, do you still want to run it?'), default: false)
        ) {
            $this->line(__('Exiting without running.'));

            return 2;
        } elseif (is_null($operation)) {
            $this->error(__('No :type with that ID.', ['type' => $automation::getOperationClass()]));

            return 3;
        }

        $this->line(__('Running the automation ":name".', ['name' => $automation->name]));
        $event = $this->choice(__('Which event should be used to run the automation?'), static::getEventChoices());
        $automation->run(new $event($operation));
        $this->line(__('The automation was run.'));

        return 0;
    }

    protected static function getEventChoices(): array
    {
        return collect(OrderAutomation::getSupportedEvents()) // TODO: Make dynamic.
            ->map(fn (string $event) => str_replace('\\\\', '\\', $event))
            ->toArray();
    }
}
