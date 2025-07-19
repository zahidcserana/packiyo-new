<?php

namespace App\Console\Commands;

use App\Components\AutomationComponent;
use App\Exceptions\AutomationException;
use App\Models\Automation;
use Illuminate\Console\Command;

class DisableAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:disable {automation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable an automation.';

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
     * @param AutomationComponent $automationComponent
     * @return int
     * @throws AutomationException
     */
    public function handle(AutomationComponent $automationComponent): int
    {
        $automation = Automation::find($this->argument('automation'));

        if (is_null($automation)) {
            $this->error(__('No automation with that ID.'));

            return 1;
        } elseif (!$automation->is_enabled) {
            $this->error(__('The automation is already disabled.'));

            return 2;
        } else {
            $this->line(__('Disabling the automation ":name".', ['name' => $automation->name]));
            if ($this->confirm(__('Are you sure you want to disable it?'))) {
                $automationComponent->disable($automation);
                $this->line(__('The automation was disabled.'));
            }
            return 0;
        }
    }
}
