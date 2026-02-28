<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\PeteService;
use App\Services\PeteOption;

class RenewSslCommand extends Command
{
    /**
     * Options:
     *  --dry-run   : Only list which sites would be processed.
     *  --days=360  : Override age threshold (in days).
     *  --email=... : Force the email used for certificate issuance.
     */
    protected $signature = 'sites:renew-ssl
        {--dry-run : Only log which sites would be renewed}
        {--days=360 : Consider certificates older than N days}
        {--email= : Email to use when issuing (fallbacks to first admin / mail.from.address)}';

    protected $description = 'Renew SSL by invoking Site::generate_ssl() if ssl_created_at is NULL or older than N days.';

    public function __construct(
        protected PeteService $pete,
        protected PeteOption  $peteOptions
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $cutoff = now()->subDays(max(1, $days));

        // ── guard: production only (same spirit as controller) ─────────────
        $envIsProd = $this->peteOptions->get_meta_value('environment') === 'production';
        if (!$envIsProd) {
            $this->error('This feature is only available in the production environment.');
            return self::FAILURE;
        }

        // ── guard: license check (same spirit as controller) ───────────────
        if (! $this->pete->checkPeteLicense()) {
            $this->error('This feature is only available in the PRO version.');
            return self::FAILURE;
        }

        // Email to use for issuance (same value the controller passes)
        $emailOpt = (string) ($this->option('email') ?? '');
        $issuerEmail = $emailOpt !== ''
            ? $emailOpt
            : (
                User::query()->where('admin', true)->value('email')
                ?? config('mail.from.address')
                ?? 'unknown@example.com'
            );

        $this->info("Cutoff: {$cutoff->toDateString()} | Email: {$issuerEmail}");
        if ($this->option('dry-run')) {
            $this->line('Running in DRY RUN mode – no changes will be made.');
        }

        // Pick targets: NULL or older/equal than cutoff
        $query = Site::query()
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('ssl_created_at')
                  ->orWhereDate('ssl_created_at', '<=', $cutoff->toDateString());
            })
            ->orderBy('id');

        $count = 0;

        $query->chunkById(200, function ($sites) use (&$count, $issuerEmail) {
            foreach ($sites as $site) {
                $domain = (string) ($site->url ?? $site->name ?? 'unknown');

                if ($this->option('dry-run')) {
                    $this->line("Would renew SSL for: {$domain} (site_id={$site->id})");
                    $count++;
                    continue;
                }

                try {
                    // Keep behavior aligned with controller:
                    // set flags first, save, then run generate_ssl().
                    $site->ssl = true;
                    $site->ssl_created_at = now();
                    $site->save();

                    $output = $site->generate_ssl($issuerEmail);

                    Log::info('SSL renewal invoked from command.', [
                        'site_id' => $site->id,
                        'domain'  => $domain,
                    ]);

                    $this->info("SSL renewed for {$domain} (site_id={$site->id}).");
                    $count++;
                } catch (\Throwable $e) {
                    Log::error('SSL renewal failed from command.', [
                        'site_id' => $site->id,
                        'domain'  => $domain,
                        'error'   => $e->getMessage(),
                    ]);
                    $this->error("Failed for {$domain} (site_id={$site->id}) – check logs.");
                }
            }
        });

        $this->info("Done. Processed {$count} site(s).");
        return self::SUCCESS;
    }
}
