<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Site;
use App\Models\Backup;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CreateBackup extends Command
{
    /**
     * Examples:
     *  php artisan create_backup --schedulling=daily
     *  php artisan create_backup --site=42 --label=nightly
     *  php artisan create_backup --dry-run
     */
    protected $signature = 'create_backup
        {--schedulling= : Base label for this run (e.g., daily/weekly)}
        {--site= : Site ID to back up only this site}
        {--label= : Explicit base label (overrides --schedulling)}
        {--dry-run : Don’t enqueue; just print what would happen}';

    protected $description = 'Enqueue one Pete backup per site, pruning oldest backups based on backup_days (skip when backup_days is 0 or null).';

    public function handle(): int
    {
        $baseLabel = (string) ($this->option('label') ?? $this->option('schedulling') ?? 'manual');
        $timestamp = now()->format('Ymd_His');

        // Pick sites
        $os = (string) app(\App\Services\PeteOption::class)->get_meta_value('os');
        $query = Site::query()
            ->orderBy('id', 'asc')
            ->where('app_name', 'WordPress');

        if ($os === 'mac') {
            $query->whereNotNull('barserver_id');
        }
        if ($this->option('site')) {
            $query->where('id', (int) $this->option('site'));
        }

        /** @var \Illuminate\Support\Collection<int,\App\Models\Site> $sites */
        $sites = $query->get();

        if ($sites->isEmpty()) {
            $this->warn('No matching sites found. Nothing to do.');
            return self::SUCCESS;
        }

        $php = trim((string) @shell_exec('command -v php 2>/dev/null')) ?: '/usr/bin/php';
        if (!is_executable($php)) { $php = 'php'; }

        $runner    = base_path('bootstrap/run_backup.php');
        $statusDir = storage_path('app/backup-jobs');
        @mkdir($statusDir, 0775, true);

        $totalJobs = 0;

        foreach ($sites as $site) {
            // ── Only use backup_days; skip when 0 or null ─────────────────────
            $allowed = $this->resolveAllowedBackups($site); // int >= 0

            if ($allowed <= 0) {
                $this->line(sprintf(
                    'Skipping site_id=%d url=%s — backup_days is null or 0',
                    $site->id, $site->url
                ));
                continue;
            }

            // Fetch existing backups (newest first)
            $existing = Backup::where('site_id', $site->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $existingCount = $existing->count();

            // If we’re at/over the cap, prune the OLDEST enough to make room for ONE new backup
            if ($existingCount >= $allowed) {
                $toDeleteCount = $existingCount - $allowed + 1; // +1 to free a slot for this run
                $oldest = $existing->sortBy('created_at')->take($toDeleteCount);

                foreach ($oldest as $b) {
                    if ($this->option('dry-run')) {
                        $this->line(sprintf(
                            '[DRY-RUN] Would delete backup_id=%d file=%s',
                            $b->id,
                            method_exists($b, 'file_path') ? $b->file_path() : ($b->file_name ?? '(unknown)')
                        ));
                        continue;
                    }

                    try {
                        // Prefer model method to delete file if available
                        if (method_exists($b, 'delete_backup')) {
                            $b->delete_backup(); // removes tar.gz on disk
                        }
                        $b->delete(); // removes DB row

                        $this->line(sprintf(
                            'Deleted old backup_id=%d (pruning to honor backup_days=%d)',
                            $b->id, $allowed
                        ));
                    } catch (\Throwable $e) {
                        Log::warning('Backup prune failed', [
                            'backup_id' => $b->id,
                            'site_id'   => $site->id,
                            'error'     => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Build a unique label to avoid any collisions
            // e.g. daily-20251030_101530-a1b2 (short uuid suffix)
            $label = sprintf('%s-%s-%s', $baseLabel, $timestamp, substr((string) Str::uuid(), 0, 4));

            $jobId = (string) Str::uuid();
            $seed = [
                'status'     => 'queued',
                'progress'   => 0,
                'message'    => 'Queued',
                'job'        => $jobId,
                'site_id'    => (int) $site->id,
                'label'      => $label,
                'user_id'    => (int) ($site->user_id ?? 1),
                'created_at' => now()->toISOString(),
            ];

            if ($this->option('dry-run')) {
                $this->line(sprintf(
                    '[DRY-RUN] Would enqueue backup: site_id=%d url=%s label=%s job=%s',
                    $site->id, $site->url, $label, $jobId
                ));
                continue;
            }

            @file_put_contents("$statusDir/{$jobId}.json", json_encode($seed, JSON_PRETTY_PRINT));

            $cmd = sprintf(
                'cd %s && nohup %s %s --site=%d --label=%s --user=%d --job=%s > /dev/null 2>&1 &',
                escapeshellarg(base_path()),
                escapeshellcmd($php),
                escapeshellarg($runner),
                (int) $site->id,
                escapeshellarg($label),
                (int) ($site->user_id ?? 1),
                escapeshellarg($jobId)
            );

            @shell_exec($cmd);
            $totalJobs++;

            $this->info(sprintf(
                'Enqueued backup: site_id=%d url=%s label=%s job=%s',
                $site->id, $site->url, $label, $jobId
            ));
        }

        if (!$this->option('dry-run')) {
            $this->line("Total jobs enqueued: {$totalJobs}");
        }

        return self::SUCCESS;
    }

    /**
     * Only consider backup_days. If null or 0 → return 0 (skip).
     * Otherwise, return the positive integer to retain.
     */
    private function resolveAllowedBackups($site): int
    {
        // Use raw attributes to distinguish NULL vs "0" reliably
        $attrs = $site->getAttributes();

        if (!array_key_exists('backup_days', $attrs) || $attrs['backup_days'] === null) {
            return 0;
        }

        $val = (int) $attrs['backup_days'];
        return $val > 0 ? $val : 0;
    }
}
