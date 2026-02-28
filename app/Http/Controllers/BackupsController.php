<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Site;
use App\Services\OServer;
use Illuminate\Http\Request;
use App\Services\PeteOption;
use App\Models\Backup;
use Validator;
use Illuminate\Support\Facades\Redirect;
use Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller;
use App\Services\PeteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Response;    
use Illuminate\Contracts\View\View; 
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;


class BackupsController extends Controller
{
	private PeteService $pete;
    private PeteOption $peteOptions;
	
	public function __construct(PeteService $pete, PeteOption $peteOptions)
    {
        $this->middleware('auth');
        $this->pete = $pete;
		$this->peteOptions = $peteOptions;
    }
	
	public function index(Request $request): View
	{
		$user    = Auth::user();
		$perPage = (int) $request->integer('per_page', 10);
		$viewsw  = 'backups';

		if (! in_array($perPage, [5, 10, 20, 50], true)) {
			$perPage = 5;
		}

		$query = Backup::query()
			->select('backups.*')
			->latest('backups.id');

		if (Gate::allows('user.non-admin')) {
			$query->where('user_id', $user->id);
		}

		$backups = $query->paginate($perPage)->withQueryString();

		//  	Build a minimal, front-end–friendly payload for Vue
		//    - No models/relations, just primitives
		//    - Avoids short closures in Blade (the source of previous syntax errors)
		//    - Keeps URLs relative so they work behind proxies
		$backupsPayload = $backups->getCollection()->map(function (Backup $b) {
			return [
				'id'         => $b->id,
				'label'      => $b->schedulling,
				'url'        => $b->url,
				'file_path'  => $b->file_path(), // computed string; no fs hit
				'download'   => route('backups.download', $b->id, false),
				'created_at' => optional($b->created_at)->toIso8601String(), // ← add this
			];
		})->values();

		return view('backups.index', [
			'backups'         => $backups,
			'backupsPayload'  => $backupsPayload, 
			'currentUser'    => $user,
			'viewsw'          => $viewsw,
		]);
	}

	
	public function store(Request $request): JsonResponse
	{
		// Validate up-front (same rules you had)
		$validated = $request->validate([
			'site_id' => ['required', 'integer', 'exists:sites,id'],
			'backup_label' => [
				'required','string','max:64',
				'regex:/^[A-Za-z0-9._-]+$/',
				Rule::unique('backups', 'schedulling')->where(
					fn ($q) => $q->where('site_id', $request->integer('site_id'))
				),
			],
		]);

		$siteId = (int) $validated['site_id'];
		$label  = (string) $validated['backup_label'];

		// AuthZ: user must manage backups for this site (or use your existing policy)
		$site = \App\Models\Site::findOrFail($siteId);
		if (! \Gate::allows('sites.manage', $site)) {
			return response()->json([
				'error'   => true,
				'message' => 'This area is restricted to administrators only. Please contact your admin if you need access..',
			], 403);
		}

		// ==== enqueue async job (mirror cloneSite()) =========================
		$jobId     = (string) \Illuminate\Support\Str::uuid();
		$statusDir = storage_path('app/backup-jobs');
		@mkdir($statusDir, 0775, true);

		// seed status file → UI can show "Queued"
		file_put_contents("$statusDir/{$jobId}.json", json_encode([
			'status'     => 'queued',
			'progress'   => 0,
			'message'    => 'Queued',
			'site_id'    => $siteId,
			'label'      => $label,
			'created_at' => now()->toISOString(),
		], JSON_PRETTY_PRINT));

		// detached PHP command
		$php = trim((string) @shell_exec('command -v php 2>/dev/null')) ?: '/usr/bin/php';
		if (!is_executable($php)) { $php = 'php'; }

		$runner = base_path('bootstrap/run_backup.php');
		$userId = (int) $request->user()->id;

		$cmd = sprintf(
			'cd %s && nohup %s %s --site=%d --label=%s --user=%d --job=%s > /dev/null 2>&1 &',
			escapeshellarg(base_path()),
			escapeshellcmd($php),
			escapeshellarg($runner),
			$siteId,
			escapeshellarg($label),
			$userId,
			escapeshellarg($jobId)
		);
		shell_exec($cmd);

		// 202 Accepted with polling URL (same shape as clone)
		return response()->json([
			'error'      => false,
			'message'    => 'Backup enqueued.',
			'job_id'     => $jobId,
			'status_url' => route('backups.status', $jobId, false),
		], 202);
	}

	
	public function restore(Request $request): RedirectResponse|JsonResponse
	{
		// Normalize destination first (append domain template if needed)
		$tpl         = $this->peteOptions->get_meta_value('domain_template');
		$fullDomain  = $this->pete->normalizeUrlWithTemplate($request->input('backup_domain', ''), (string) $tpl);
		$input       = array_replace($request->all(), ['backup_domain' => $fullDomain]);

		// Validate inputs
		$v = \Validator::make($input, [
			'backup_id' => ['required', 'integer', 'exists:backups,id'],
			'backup_domain' => [
				'required',
				'max:255',
				// Must be a domain like example.com or sub.example.co.uk (no scheme, no trailing dot)
				'regex:/^(?!\-)(?:[a-z0-9\-]+\.)+[a-z]{2,}$/i',
				\Illuminate\Validation\Rule::unique('sites', 'url'),
			],
		], [
			'backup_domain.regex' => 'Please enter a valid domain, e.g. mysite.com or sub.mysite.org',
		], [
			'backup_domain' => 'destination URL',
		]);
		
		if ($v->fails()) {
			return $this->pete->fail($request, 'Validation failed.', $v->errors()->toArray(), 422);
		}

		// Resolve actors/resources & authorize
		$user   = $request->user();
		$backup = \App\Models\Backup::findOrFail((int) $input['backup_id']);

		if (! \Gate::allows('backups.manage', $backup)) {
			return $request->expectsJson()
				? response()->json([
					'error'   => true,
					'message' => 'This area is restricted to administrators only. Please contact your admin if you need access..',
				], 403)
				: redirect()->route('sites.index')
					->with('error', true)
					->with('error_message', 'This area is restricted to administrators only. Please contact your admin if you need access..');
		}

		// Business guards
		if ($this->pete->isTheURLForbidden($fullDomain)) {
			return $this->pete->fail($request, 'URL forbidden.', [
				'backup_domain' => ['This URL is not allowed.'],
			], 422);
		}

		// === Async restore via run_import.php ==================================
		$jobId    = (string) \Illuminate\Support\Str::uuid();
		$statusDir = storage_path('app/import-jobs'); // IMPORTANT: run_import.php writes here
		@mkdir($statusDir, 0775, true);

		// Seed "Queued" so the UI has an immediate status to show
		@file_put_contents("$statusDir/{$jobId}.json", json_encode([
			'status'     => 'queued',
			'progress'   => 0,
			'message'    => 'Queued',
			'job'        => $jobId,
			'dest'       => $fullDomain,
			'user_id'    => (int) $user->id,
			'backup_id'  => (int) $backup->id,
			'created_at' => now()->toISOString(),
		], JSON_PRETTY_PRINT));

		// Build detached command that reuses the existing runner
		$php = trim((string) @shell_exec('command -v php 2>/dev/null')) ?: '/usr/bin/php';
		if (!is_executable($php)) { $php = 'php'; }

		$runner   = base_path('bootstrap/run_import.php');
		$template = $backup->get_backup_file();  // <- This is what run_import.php expects as --template

		$cmd = sprintf(
			'cd %s && nohup %s %s --job=%s --dest=%s --user=%d --template=%s > /dev/null 2>&1 &',
			escapeshellarg(base_path()),
			escapeshellcmd($php),
			escapeshellarg($runner),
			escapeshellarg($jobId),
			escapeshellarg($fullDomain),
			(int) $user->id,
			escapeshellarg($template)
		);
		shell_exec($cmd);

		// Return 202 + a polling URL (see status() change below)
		$payload = [
			'error'      => false,
			'message'    => 'Restore enqueued.',
			'job_id'     => $jobId,
			'status_url' => route('backups.status', $jobId, false), // we’ll make status() look in import-jobs too
		];

		return ($request->expectsJson() || $request->ajax())
			? response()->json($payload, 202)
			: redirect()->route('sites.index')->with('status', $payload['message']);
	}

	
     public function destroy(Request $request, Backup $backup): Response|RedirectResponse|JsonResponse
    {
        
        if (! Gate::allows('backups.manage', $backup)) {
			// If the request expects a JSON response (e.g., AJAX or API), return a 403 JSON error
			if (request()->expectsJson()) {
				return response()->json([
					'error'   => true,
					'message' => 'This area is restricted to administrators only. Please contact your admin if you need access..',
				], 403);
			}

			// If the request is a standard HTTP request, redirect back with a flash error message
			return redirect()
				->route('sites.index')
				->with('error', true)
				->with('error_message', 'This area is restricted to administrators only. Please contact your admin if you need access..');
		}

        try {
            // Removes file + DB row
            $backup->delete_backup();

            // For XHR / fetch() calls
            if ($request->expectsJson() || $request->ajax()) {
                return response()->noContent(); // Illuminate\Http\Response
            }

            // Non-AJAX fallback
            return redirect()
                ->route('backups.index')
                ->with('status', 'Backup deleted');
        } catch (\Throwable $e) {
            Log::error('Backup delete failed', [
                'backup_id' => $backup->id,
                'user_id'   => $request->user()?->id,
                'message'   => $e->getMessage(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Delete failed. Please try again.',
                ], 500); // Symfony JsonResponse
            }

            return redirect()
                ->route('backups.index')
                ->withErrors('Delete failed. Please try again.');
        }
    }

   public function download(Backup $backup)
	{
		$file = $backup->file_path();

		if (! Gate::allows('backups.manage', $backup)) {
			if (request()->expectsJson()) {
				return response()->json([
					'error'   => true,
					'message' => 'This area is restricted to administrators only. Please contact your admin if you need access..',
				], 403);
			}
			return redirect()
				->route('sites.index')
				->with('error', true)
				->with('error_message', 'This area is restricted to administrators only. Please contact your admin if you need access..');
		}

		if (!is_file($file) || !is_readable($file)) {
			abort(404, 'Backup file not found.');
		}

		$filename = basename($file);

		// Prefer X-Sendfile in Docker / Pete environment
		if (env('PETE_USE_XSENDFILE', true)) {
			// Close session so the long download doesn't block anything else
			if (session()->isStarted()) {
				session()->save();
			}

			// Let Apache handle the heavy lifting
			return response('', 200, [
				'Content-Type'        => 'application/octet-stream',
				'Content-Disposition' => 'attachment; filename="'.$filename.'"',
				'X-Sendfile'          => $file,
				// let Apache set Content-Length; omitting it is fine for large files
				'Cache-Control'       => 'private, no-transform, must-revalidate',
				'X-Accel-Buffering'   => 'no',
			]);
		}

		// --- Fallback: manual streaming if X-Sendfile is disabled via env ---
		$size = @filesize($file) ?: null;

		return response()->streamDownload(function () use ($file) {
			if (function_exists('apache_setenv')) {
				@apache_setenv('no-gzip', '1');
			}

			@ini_set('zlib.output_compression', '0');
			@ini_set('output_buffering', 'off');
			@set_time_limit(0);
			@ignore_user_abort(false);

			while (ob_get_level() > 0) {
				@ob_end_clean();
			}

			$fh = fopen($file, 'rb');
			if ($fh === false) {
				return;
			}

			$chunk = 8 * 1024 * 1024; // 8MB
			while (!feof($fh)) {
				$buf = fread($fh, $chunk);
				if ($buf === false) {
					break;
				}
				echo $buf;
				flush();
			}
			fclose($fh);
		}, $filename, array_filter([
			'Content-Type'              => 'application/octet-stream',
			'Content-Transfer-Encoding' => 'binary',
			'Content-Length'            => $size,
			'Cache-Control'             => 'private, no-transform, must-revalidate',
			'Pragma'                    => 'public',
			'X-Accel-Buffering'         => 'no',
		]));
	}

	public function status(string $id): \Illuminate\Http\JsonResponse
	{
		// 1) First try a restore/import job (run_import.php)
		$path = storage_path("app/import-jobs/{$id}.json");
		if (!is_file($path)) {
			// 2) Then try a backup job (run_backup.php)
			$path = storage_path("app/backup-jobs/{$id}.json");
		}

		if (!is_file($path)) {
			return response()->json(['error' => true, 'message' => 'Not found'], 404);
		}

		$data = json_decode((string) file_get_contents($path), true) ?: [];
		return response()->json($data + ['job_id' => $id]);
	}

	
}
