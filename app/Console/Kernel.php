<?php

namespace App\Console;

use App\Console\Commands\BulkZoneTest;
use App\Console\Commands\GeoCodeCompanies;
use App\Console\Commands\ScrapeMT;
use App\Console\Commands\ScrapeMTbyIMO;
use App\Console\Commands\PollAISMT;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    //  ScrapeMT::class,
    //  ScrapeMTbyIMO::class,
        GeoCodeCompanies::class,
        BulkZoneTest::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //  $schedule->command('cdt:scrape-mt-imo')->days([1, 3, 5]);
        // $filePath = storage_path('logs/mt_poll.log');
        // $schedule->command('cdt:poll-mt')
        //  ->everyMinute()
        //  ->runInBackground()
        //  ->appendOutputTo($filePath);
         // ->emailOutputOnFailure('foo@example.com') ???
        $filePath = storage_path('logs/ais_mt_poll.log');
        $schedule->command('cdt:poll-ais-mt')
         ->everyMinute()
         ->runInBackground()
         ->appendOutputTo($filePath);
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
