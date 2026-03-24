<?php
// bootstrap/run_import.php

use App\Models\Site;
use App\Services\OServer;
use App\Services\PeteOption;

require __DIR__ . '/../vendor/autoload.php';
$app    = require __DIR__ . '/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ---------- argv parsing: --key=value ----------
$args = [];
foreach ($argv as $i => $raw) {
    if ($i === 0) continue;
    if (preg_match('/^--([^=]+)=(.*)$/', $raw, $m)) {
        $args[$m[1]] = $m[2];
    }
}

// ---------- status + log paths ----------
$job      = (string) ($args['job'] ?? '');
$dest     = (string) ($args['dest'] ?? '');
$user     = (int)    ($args['user'] ?? 0);
$template = (string) ($args['template'] ?? '');

$statusDir = storage_path('app/import-jobs');
@mkdir($statusDir, 0775, true);

$stFile  = "{$statusDir}/{$job}.json";
$logFile = "{$statusDir}/{$job}.log";

// ---------- helpers ----------
function strip_ansi($s)
{
    return preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', (string) $s);
}

function to_utf8($s)
{
    $s   = (string) $s;
    $out = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
    if ($out === false) {
        $out = mb_convert_encoding($s, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
    }
    return $out ?? '';
}

$appendLog = function (string $line) use ($logFile) {
    $ts = now()->format('Y-m-d H:i:s');
    @file_put_contents($logFile, "[{$ts}] {$line}\n", FILE_APPEND);
};

$write = function (array $data) use ($stFile) {
    // throttle a bit to reduce fs churn
    usleep(120_000);
    @file_put_contents($stFile, json_encode(
        $data + ['updated_at' => now()->toISOString()],
        JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR
    ));
};

/**
 * Run a command with stderr, capture exit code, and stream output into the job log.
 * Returns: ['code'=>int, 'out'=>string]
 */
$runCmd = function (string $cmd, ?string $cwd = null) use ($appendLog): array {
    $appendLog("CMD: {$cmd}");

    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd ?: null);
    if (! is_resource($process)) {
        $appendLog('ERROR: proc_open failed');
        return ['code' => 127, 'out' => 'proc_open failed'];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $out = '';
    $err = '';

    while (true) {
        $status = proc_get_status($process);

        $chunk1 = stream_get_contents($pipes[1]);
        if ($chunk1 !== false && $chunk1 !== '') {
            $out .= $chunk1;
        }

        $chunk2 = stream_get_contents($pipes[2]);
        if ($chunk2 !== false && $chunk2 !== '') {
            $err .= $chunk2;
        }

        if (! $status['running']) {
            break;
        }

        usleep(80_000);
    }

    // collect any remaining
    $chunk1 = stream_get_contents($pipes[1]);
    if ($chunk1 !== false && $chunk1 !== '') {
        $out .= $chunk1;
    }

    $chunk2 = stream_get_contents($pipes[2]);
    if ($chunk2 !== false && $chunk2 !== '') {
        $err .= $chunk2;
    }

    fclose($pipes[1]);
    fclose($pipes[2]);

    $code = proc_close($process);

    $combined = trim(to_utf8(strip_ansi($out . "\n" . $err)));
    if ($combined !== '') {
        foreach (explode("\n", $combined) as $line) {
            $appendLog($line);
        }
    }

    $appendLog("EXIT: {$code}");

    return [
        'code' => (int) $code,
        'out'  => $combined,
    ];
};

$fail = function (string $message, array $extra = []) use ($write, $appendLog) {
    $appendLog("FAILED: {$message}");
    $write(array_merge([
        'status'   => 'failed',
        'progress' => 100,
        'message'  => $message,
    ], $extra));
    exit(1);
};

try {
    // ---------- guard ----------
    if (! $job || ! $dest || ! $user || ! $template) {
        $fail('Invalid runner arguments', ['phase' => 'validate']);
    }

    $write([
        'status'   => 'running',
        'progress' => 3,
        'phase'    => 'validate',
        'message'  => 'Validating inputs…',
    ]);

    $appendLog("Starting import job={$job} user={$user} dest={$dest}");

    if (! is_string($template) || trim($template) === '') {
        $fail('Template/archive path is empty', ['phase' => 'validate']);
    }

    // If template is a local path, validate readability early.
    if (strpos($template, 'http') === false) {
        if (! is_file($template) || ! is_readable($template)) {
            $fail("Archive not readable: {$template}", ['phase' => 'validate']);
        }
    }

    $write([
        'status'   => 'running',
        'progress' => 8,
        'phase'    => 'site_create',
        'message'  => 'Creating site record…',
    ]);

    $site          = new Site();
    $site->user_id = $user;
    $site->url     = $dest;
    $site->set_project_name($site->url);
    $site->save();

    $appendLog("Site created id={$site->id} name={$site->name}");

    $write([
        'status'   => 'running',
        'progress' => 12,
        'phase'    => 'import',
        'message'  => 'Importing WordPress archive…',
        'site_id'  => $site->id,
    ]);

    $site->import_wordpress([
        'template'    => $template,
        'theme'       => 'custom',
        'user_id'     => $user,
        'site_url'    => $site->url,
        'action_name' => 'Import',
        'job_id'      => $job,
    ]);

    // ---------- post-import uploads hardening ----------
    $write([
        'status'   => 'running',
        'progress' => 80,
        'phase'    => 'hardening',
        'message'  => 'Applying uploads hardening…',
        'site_id'  => $site->id,
    ]);

    $appendLog('Applying uploads hardening…');

    /** @var PeteOption $peteOptions */
    $peteOptions = app(PeteOption::class);
    $appRoot     = rtrim((string) $peteOptions->get_meta_value('app_root'), '/');
    $uploadsDir  = $appRoot . '/' . $site->name . '/wp-content/uploads';
    $templatePath = base_path('templates/htaccess_uploads.txt');
    $targetPath   = $uploadsDir . '/.htaccess';

    $appendLog("Uploads template path: {$templatePath}");
    $appendLog("Uploads target path: {$targetPath}");

    if ($appRoot === '') {
        $appendLog('WARNING: app_root is empty; skipping uploads hardening.');
    } else {
        if (! is_dir($uploadsDir)) {
            if (@mkdir($uploadsDir, 0755, true)) {
                $appendLog("Created uploads directory: {$uploadsDir}");
            } else {
                $appendLog("WARNING: Failed to create uploads directory: {$uploadsDir}");
            }
        }

        if (is_file($templatePath) && is_readable($templatePath)) {
            if (@copy($templatePath, $targetPath)) {
                $appendLog("uploads .htaccess applied: {$targetPath}");
            } else {
                $appendLog("WARNING: Failed to copy uploads .htaccess to: {$targetPath}");
            }
        } else {
            $appendLog("WARNING: uploads template missing or unreadable: {$templatePath}");
        }

        if (is_file($targetPath)) {
            @chmod($targetPath, 0644);
            $appendLog("Verified uploads .htaccess exists: {$targetPath}");
        } else {
            $appendLog("WARNING: uploads .htaccess does not exist after hardening step");
        }
    }

    $write([
        'status'   => 'running',
        'progress' => 86,
        'phase'    => 'reload',
        'message'  => 'Reloading server…',
        'site_id'  => $site->id,
    ]);

    // Prefer OServer reload (already abstracts docker vs systemd/init)
    OServer::reload_server();

    $write([
        'status'   => 'succeeded',
        'progress' => 100,
        'phase'    => 'done',
        'message'  => 'Import completed.',
        'site_id'  => $site->id,
        'url'      => $site->url,
        'name'     => $site->name,
    ]);

    $appendLog("DONE site_id={$site->id}");
    exit(0);
} catch (\Throwable $e) {
    \Log::error('run_import failed', [
        'job'   => $job,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    $fail($e->getMessage(), [
        'phase' => 'exception',
    ]);
}