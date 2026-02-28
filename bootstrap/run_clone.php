<?php
// Minimal bootstrap so we can use Eloquent/your models outside HTTP/Artisan.
use App\Models\Site;
use App\Services\OServer;

// 1) bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 2) tiny argv parser: --key=value
$args = [];
foreach ($argv as $i => $raw) {
    if ($i === 0) continue;
    if (preg_match('/^--([^=]+)=(.*)$/', $raw, $m)) $args[$m[1]] = $m[2];
}
$job   = (string)($args['job']   ?? '');
$srcId = (int)   ($args['src']   ?? 0);
$dest  = (string)($args['dest']  ?? '');
$user  = (int)   ($args['user']  ?? 0);

// 3) status writer (storage/app/clone-jobs/{job}.json)
$stFile = storage_path("app/clone-jobs/{$job}.json");
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

// 4) guard inputs
if (!$job || !$srcId || !$dest || !$user) {
    $write(['status'=>'failed','progress'=>100,'message'=>'Invalid runner arguments']);
    exit(1);
}

try {
    $write(['status'=>'running','progress'=>5,'message'=>'Preparing…']);

    $source = Site::findOrFail($srcId);
    $destSite          = new Site();
    $destSite->user_id = $user;
    $destSite->url     = $dest;
    $destSite->set_project_name($destSite->url);

    
    $write(['status'=>'running','progress'=>10,'message'=>'Exporting site…']);
    $backupLabel = 'oclone_' . $job;
    $backup = $source->create_backup($backupLabel);
    
    $write([
    'status'   => 'running',
    'progress' => 40,
    'message'  => to_utf8(strip_ansi($backup->output)),
    ]);

    $params = [
        'template'       => $backup->get_backup_file(),
        'action_name'    => 'Cloned',
        'wp_user'        => $backup->wp_user,
        'theme'          => $backup->theme,
        'user_id'        => $destSite->user_id,
        'first_password' => $backup->first_password,
        'site_url'       => $destSite->url,
    ];
    
    $write(['status'=>'running','progress'=>55,'message'=>'Importing into destination…']);
    $destSite->import_wordpress($params);

    $write(['status'=>'running','progress'=>80,'message'=>'Site import completed']);

    $write([
    'status'   => 'running',
    'progress' => 90,
    'message'  => to_utf8(strip_ansi($destSite->output)),
    ]);

    $backup->delete_backup();
    OServer::reload_server();

    $write(['status'=>'running','progress'=>95,'message'=>'Finalizing…']);

    $write([
    'status'       => 'succeeded',
    'progress'     => 100,
    'message'      => 'Clone completed.',
    'dest_site_id' => $destSite->id,
    'dest_url'     => $destSite->url,
    'dest_name'    => $destSite->name,
    ]);
    exit(0);

} catch (\Throwable $e) {
    $write(['status'=>'failed','progress'=>100,'message'=>$e->getMessage()]);
    // Also mirror to laravel.log
    \Log::error('run_clone failed', ['error'=>$e->getMessage()]);
    exit(1);
}
