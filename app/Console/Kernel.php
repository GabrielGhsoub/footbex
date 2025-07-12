<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        Commands\SettleWeeklyBets::class, // Add this
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('bets:settle-weekly')
                //  every 1minute
                ->everySecond(5) // Adjust as needed, e.g. every 1 minute
                 ->withoutOverlapping()
                 ->runInBackground(); // If it's a long process
        
        // Consider clearing football-data.org cache periodically if stale data is an issue
        // $schedule->command('cache:clear --tags=football-data-api')->daily(); // Example if you use tagged cache
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
