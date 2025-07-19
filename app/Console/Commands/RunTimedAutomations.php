<?php

namespace App\Console\Commands;

use App\Components\AutomationComponent;
use Illuminate\Console\Command;

class RunTimedAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:timed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run automations that are based on time.';

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
        // TODO: How can I add a progress bar to this?
        app(AutomationComponent::class)->runTimedAutomations();

        return 0;
    }
}
