<?php

namespace App\Console;

use App\Console\Commands\ExecuteCustomCodeCommand;
use App\Console\Commands\ShowMetricsCommand;
use App\Console\Commands\SyncPackiyoSubscriptionPlans;
use App\Features\DataWarehousing;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Laravel\Pennant\Feature;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\RecalculateOrderReadyToShip::class,
        SyncPackiyoSubscriptionPlans::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('recalculate-ready-to-ship')->hourly()->withoutOverlapping();

        $schedule->command('get-carriers')->daily();

        $schedule->command('order-priority-updater')->dailyAt('02:00');

        $schedule->command(ExecuteCustomCodeCommand::class)->everyMinute()->withoutOverlapping();

        $schedule->command('automation:timed')->everyFifteenMinutes()->withoutOverlapping();

        if (Feature::for('instance')->active(DataWarehousing::class)) {
            $schedule->command('process:customer-stats')->dailyAt('02:00');
        }

        // TODO  We should also probably add an email when it fails https://laravel.com/docs/8.x/scheduling#task-output
        $schedule->command('calculate:occupied-locations')->onOneServer()->hourly();

        $schedule->command(ShowMetricsCommand::class)->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
