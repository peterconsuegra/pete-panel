<?php

namespace App\Console;

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
        \App\Console\Commands\Addoption::class,
        \App\Console\Commands\CreateBackup::class,
        \App\Console\Commands\Removeoption::class,
        \App\Console\Commands\UpdateOptionVersion::class,
        \App\Console\Commands\ClearPeteCache::class,
        \App\Console\Commands\ActivateSecurity::class,
         \App\Console\Commands\RenewSslCommand::class
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Use app timezone if set; default to America/Bogota
        $tz = config('app.timezone', 'America/Bogota');

        // Daily Pete backup — one backup per site with retention (CreateBackup)
        $schedule->command('create_backup --schedulling=daily')
            ->dailyAt('02:15')                 
            ->timezone($tz)
            ->onOneServer()                    
            ->withoutOverlapping()             
            ->runInBackground()            
            ->appendOutputTo(storage_path('logs/schedule-create_backup.log'))
            ->description('Pete: daily WordPress backups per site (retention-aware)');

        // Daily SSL renewal — each site's certificate is renewed 30 days before it expires
        // (sites.ssl_expires_at, recorded when a certificate is issued; RenewSslCommand)
        $schedule->command('sites:renew-ssl')
            ->dailyAt('03:00')
            ->timezone('America/Bogota')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/schedule-renew-ssl.log'))
            ->description('Pete: renew SSL certificates that expire within 30 days');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
