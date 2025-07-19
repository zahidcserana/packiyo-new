<?php

namespace App\Console\Commands;

use App\Models\Automation;
use Illuminate\Console\Command;

class MoveAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:move {automation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move an automation changing the order in which it\'s run.';

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
            $this->error(__('No automation with that ID.'));

            return 1;
        } else {
            $this->line(__('Moving the automation ":name".', ['name' => $automation->name]));

            do {
                if (isset($newPosition)) {
                    $this->line(__('Please enter a positive integer. Not whatever that was.'));
                }

                $newPosition = intval($this->ask(__(
                    "The automation is in position :position.\nWhich position should it be moved to?",
                    ['position' => $automation->position]
                )));
            } while (!is_int($newPosition) || $newPosition < 1);

            $automation->move($newPosition);
            // $automation->save();
            $this->line(__('The automation was moved to position :position.', ['position' => $automation->position]));

            return 0;
        }
    }
}
