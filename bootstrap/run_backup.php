<?php
// bootstrap/run_backup.php
use App\Models\Site;

// 1) bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 2) parse argv: --key=value
$args = [];
foreach ($argv as $i => $raw) {
    if ($i === 0) continue;
    if (preg_match('/^--([^=]+)=(.*)$/', $raw, $m)) $args[$m[1]] = $m[2];
}
$job   = (string)($args['job']   ?? '');
$siteId= (int)   ($args['site']  ?? 0);
$label = (string)($args['label'] ?? '');
$user  = (int)   ($args['user']  ?? 0);

// 3) status file
$stFile = storage_path("app/backup-jobs/{$job}.json");
@mkdir(dirname($stFile), 0775, true);
$write = function(array $data) use ($stFile) {
    sleep(1);
    @file_put_contents($stFile, json_encode(
        $data + ['updated_at' => now()->toISOString()],
        JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR
    ));
};

function strip_ansi($s) {
    return preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', (string)$s);
}
function to_utf8($s) {
    $s = (string)$s;
    // coerce to utf-8, drop invalid bytes
    $out = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
    if ($out === false) { $out = mb_convert_encoding($s, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252'); }
    return $out ?? '';
}

// 4) guard
if (!$job || !$siteId || !$label || !$user) {
    $write(['status'=>'failed','progress'=>100,'message'=>'Invalid runner arguments']);
    exit(1);
}

try {
    $write(['status'=>'running','progress'=>5,'message'=>'Preparing…']);

    $site = Site::findOrFail($siteId);

    $write(['status'=>'running','progress'=>25,'message'=>'Creating backup object…']);
    $backup = $site->create_backup($label);      // uses your existing domain logic
    
    $write([
    'status'   => 'running',
    'progress' => 77,
    'message'  => to_utf8(strip_ansi($backup->output)),
    ]);

    $write(['status'=>'running','progress'=>85,'message'=>'Finalizing…']);
   
    $backup->save();                              // persist the row after files are ready

    $write([
        'status'     => 'succeeded',
        'progress'   => 100,
        'message'    => 'Backup completed.',
        'backup_id'  => $backup->id,
        'label'      => $backup->schedulling,
        'download'   => route('backups.download', $backup->id, false),
    ]);
    exit(0);

} catch (\Throwable $e) {
    \Log::error('run_backup failed', ['error' => $e->getMessage()]);
    $write(['status'=>'failed','progress'=>100,'message'=>$e->getMessage()]);
    exit(1);
}
