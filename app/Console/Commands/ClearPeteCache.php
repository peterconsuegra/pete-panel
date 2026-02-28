<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PeteService;

class ClearPeteCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run it with:
     *   php artisan pete:clear-cache
     */
    protected $signature = 'pete:clear-cache';

    /**
     * The console command description.
     */
    protected $description = 'Clear all PeteService caches (options, sidebar, plugins, last version)';

    private PeteService $pete;

    /**
     * Inject PeteService.
     */
    public function __construct(PeteService $pete)
    {
        parent::__construct();
        $this->pete = $pete;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->pete->forgetPluginsCache();
        $this->info('✅ Plugins cache cleared.');

        $this->pete->forgetOptionsCache();
        $this->info('✅ Options cache cleared.');

        $this->pete->forgetSidebarOptionsCache();
        $this->info('✅ Sidebar cache cleared.');

        $this->pete->forgetLastVersionCache();
        $this->info('✅ Last version cache cleared.');

        $this->info('✨ All PeteService caches cleared successfully.');
        return Command::SUCCESS;
    }
}
