<?php

namespace App\Console\Commands;

use App\Models\Automation;
use Illuminate\Console\Command;

class RenameAutomation extends Command
{
    use NamesAutomations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:rename {automation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename an automation.';

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
            $this->line(__('The automation is named ":name".', ['name' => $automation->name]));
            $automation->name = $this->getGivenName($automation->customer, true);
            $automation->save();
            $this->line(__('The automation was renamed to ":name".', ['name' => $automation->name]));

            return 0;
        }
    }
}
