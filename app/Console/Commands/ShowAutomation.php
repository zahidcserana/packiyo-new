<?php

namespace App\Console\Commands;

use App\Console\Commands\CreateAutomation\OutputsAutomationTable;
use App\Models\Automation;
use Illuminate\Console\Command;

class ShowAutomation extends Command
{
    use OutputsAutomationTable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:show {automation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show an automation.';

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

        if (is_null($automation)) {
            $this->line(__('No automation with that ID.'));

            return 1;
        } else {
            $this->automationTableFromModel($automation);

            return 0;
        }
    }
}
