<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\User;
use App\Services\CertbotIssuer;
use App\Services\PeteOption;
use App\Services\PeteService;
use App\Services\SslCertificate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Renews every site's certificate before it expires. The scheduler runs it daily
 * (app/Console/Kernel.php). For each site with SSL on it reads the certificate the site
 * serves, saves its expiry (sites.ssl_expires_at) and, when it ends within --days (30 by
 * default, Let's Encrypt's own renewal window) or none is served, asks Apache's certbot for a
 * new one (CertbotIssuer). Only a confirmed new certificate updates ssl_created_at, so a
 * failed renewal is tried again on the next run.
 */
class RenewSslCommand extends Command
{
    protected $signature = 'sites:renew-ssl
        {--dry-run : Only list what would be renewed (also outside production)}
        {--days=30 : Renew certificates that expire within N days}
        {--site= : Only this site (ID or domain)}
        {--email= : Email to use when issuing (fallbacks to first admin / mail.from.address)}';

    protected $description = 'Renew the SSL certificates of sites that expire within N days (30 by default).';

    public function __construct(
        protected PeteService $pete,
        protected PeteOption  $peteOptions
    ) {
        parent::__construct();
    }

    /**
     * Whether a certificate that expires at $expiresAt (null: none served) needs renewing now.
     */
    public static function isDue(?Carbon $expiresAt, int $days, ?Carbon $now = null): bool
    {
        return $expiresAt === null || $expiresAt->lte(($now ?? now())->copy()->addDays($days));
    }

    public function handle(): int
    {
        $days   = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        // A real renewal needs certbot on a production server with a PRO license (as the
        // controller); a dry run only reads certificates, so it runs anywhere.
        if (! $dryRun) {
            if ($this->peteOptions->get_meta_value('environment') !== 'production') {
                $this->error('This feature is only available in the production environment.');
                return self::FAILURE;
            }
            if (! $this->pete->checkPeteLicense()) {
                $this->error('This feature is only available in the PRO version.');
                return self::FAILURE;
            }
        }

        $emailOpt    = (string) ($this->option('email') ?? '');
        $issuerEmail = $emailOpt !== ''
            ? $emailOpt
            : (
                User::query()->where('admin', true)->value('email')
                ?? config('mail.from.address')
                ?? 'unknown@example.com'
            );

        $this->info("Renewing certificates that expire within {$days} days | Email: {$issuerEmail}" . ($dryRun ? ' | DRY RUN' : ''));

        $query = Site::query()->where('ssl', true)->orderBy('id');
        if ($only = (string) $this->option('site')) {
            ctype_digit($only) ? $query->whereKey((int) $only) : $query->where('url', $only);
        }

        $counts = ['checked' => 0, 'valid' => 0, 'renewed' => 0, 'failed' => 0];

        $query->chunkById(200, function ($sites) use (&$counts, $days, $dryRun, $issuerEmail) {
            foreach ($sites as $site) {
                $domain = trim((string) $site->url);
                if ($domain === '') {
                    continue;
                }
                $counts['checked']++;

                $expiresAt = SslCertificate::expiresAt($domain);
                if ($expiresAt && ! $dryRun) {
                    $site->ssl_expires_at = $expiresAt;
                    $site->save();
                }

                if (! self::isDue($expiresAt, $days)) {
                    $counts['valid']++;
                    $this->line("OK      {$domain}: valid until {$expiresAt->toDateTimeString()} UTC.");
                    continue;
                }

                $why = $expiresAt ? "expires {$expiresAt->toDateTimeString()} UTC" : 'no certificate served';
                if ($dryRun) {
                    $this->line("RENEW   {$domain}: {$why} (dry run, nothing changed).");
                    continue;
                }

                $this->line("RENEW   {$domain}: {$why}, asking certbot …");
                try {
                    $issued   = CertbotIssuer::run($domain, $issuerEmail);
                    $newUntil = $issued ? SslCertificate::expiresAt($domain) : null;
                } catch (\Throwable $e) {
                    $issued   = false;
                    $newUntil = null;
                    Log::error('SSL renewal failed from command.', ['site_id' => $site->id, 'domain' => $domain, 'error' => $e->getMessage()]);
                }

                if ($issued && $newUntil && (! $expiresAt || $newUntil->gt($expiresAt))) {
                    $site->ssl_created_at = now();
                    $site->ssl_expires_at = $newUntil;
                    $site->save();
                    $counts['renewed']++;
                    Log::info('SSL renewed from command.', ['site_id' => $site->id, 'domain' => $domain, 'expires_at' => $newUntil->toIso8601String()]);
                    $this->info("RENEWED {$domain}: valid until {$newUntil->toDateTimeString()} UTC.");
                } else {
                    $counts['failed']++;
                    Log::error('SSL renewal did not produce a new certificate.', ['site_id' => $site->id, 'domain' => $domain, 'certbot_ok' => $issued]);
                    $this->error("FAILED  {$domain}: no new certificate (see storage/logs/laravel.log); tried again on the next run.");
                }
            }
        });

        $this->info(sprintf(
            'Done. Checked %d, valid %d, renewed %d, failed %d.',
            $counts['checked'],
            $counts['valid'],
            $counts['renewed'],
            $counts['failed']
        ));

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
