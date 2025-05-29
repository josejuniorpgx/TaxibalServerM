<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\FindDriverForRegularRide;
use App\Console\Commands\FindNearbyDriver;
use App\Console\Commands\AssignDriverToRide;
use App\Console\Commands\DatabaseBackup;
use App\Console\Commands\FindScheduledRides;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected $command = [
        // FindDriverForRegularRide::class,
        // FindNearbyDriver::class,
        AssignDriverToRide::class,
        DatabaseBackup::class,
        FindScheduledRides::class,
    ];
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('find_driver:for_regular_ride')->everyMinute();
        // $schedule->command('ride:find-nearby-driver')->everyMinute();
        $schedule->command('ride:assign-drivers-for-regular-rides')->everyMinute();

        $backup_type = appSettingData('get')->backup_type;
        switch ($backup_type) {
            case 'daily':
                $schedule->command('backup:database')->daily()->at('02:00');
                break;
            case 'weekly':
                $schedule->command('backup:database')->weekly()->sundays()->at('03:00');
                break;
            case 'monthly':
                $schedule->command('backup:database')->monthly()->at('04:00');
                break;
            default:
                // $schedule->command('backup:database')->everyMinute();
                break;
        }
        $schedule->command('rides:find-scheduled')->everyMinute();
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
