<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RefreshOpcache extends Command
{
    protected $signature = 'opcache:refresh';

    protected $description = 'Refresh PHP-FPM OPcache (reload FPM or hit a protected HTTP endpoint).';

    public function handle()
    {
        $base_path    = base_path();
        chdir("$base_path/scripts/");
        $command="sudo ./refresh_opcache.sh";
        $output = shell_exec($command);
    }
}
