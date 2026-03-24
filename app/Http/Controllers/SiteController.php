<?php 

namespace App\Http\Controllers;

use App\Http\Controllers\PeteController;
use Log;
use App\Models\Site;
use App\Models\User;
use App\Models\Backup;
use App\Models\Option;
use App\Services\PeteOption;
use App\Services\OServer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Services\PeteService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SiteController extends Controller {

	private const LOG_MAX_BYTES = 1048576; // 1 MB
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
		$currentUser = Auth::user();
		$viewsw      = 'sites';

		// Base query
		$query = Site::query()
			->where('app_name', 'WordPress')
			->orderByDesc('id');

		// Only admins can see all sites
		if (! Gate::allows('user.admin')) {
			$query->where('user_id', $currentUser->id);
		}

		// Per-page from request with sane bounds (1..50). Default: 10
		$perPage = (int) $request->integer('per_page', 10);
		$perPage = max(1, min($perPage, 50));

		$sites = $query->paginate($perPage)->withQueryString();

		//Build a small, JS-friendly payload (no models)
		$sitesPayload = $sites->getCollection()->map(function (Site $s) {
			return [
				'id'          => (int) $s->id,
				'name'        => (string) $s->name,
				'url'         => (string) $s->url,
				'ssl'         => (bool) $s->ssl,
				'backup_days' => (int) ($s->backup_days ?? 0),
				'created_at'  => optional($s->created_at)->toIso8601String(), // ← add
			];
		})->values();

		return view('sites.index', [
			'sites'         => $sites,
			'sitesPayload' => $sitesPayload,
			'currentUser'  => $currentUser,
			'viewsw'        => $viewsw,
		]);
	}

	public function updateBackupSettings(Request $request, Site $site): JsonResponse
	{
		if (! \Gate::allows('sites.manage', $site)) {
			return response()->json([
				'error' => true,
				'message' => 'This area is restricted to administrators only. Please contact your admin if you need access..',
			], 403);
		}

		$data = $request->validate([
			'backup_days' => ['required', 'integer', Rule::in([0,1,2,3])],
		]);

		$site->backup_days = (int) $data['backup_days'];
		$site->save();

		return response()->json([
			'ok'          => true,
			'site_id'     => $site->id,
			'backup_days' => $site->backup_days,
			'message'     => 'Backup days updated.',
		]);
	}

	public function getSites(Request $request): JsonResponse
	{
		Log::info("entering SiteController getSites");
		$currentUser = Auth::user();

		// ✅ use request value with default
		$appName = $request->string('app_name', 'WordPress');

		$query = Site::query()
			->where('app_name', 'WordPress')
			->orderByDesc('id');

		if (! Gate::allows('user.admin')) {
			$query->where('user_id', $currentUser->id);
		}

		$sites = $query->get();

		if ($appName === 'WordPress+laravel') {
			$sites = $sites->filter(static function ($site) {
				return substr_count((string) $site->url, '.') <= 2;
			})->values();
		}

		return response()->json($sites);
	}
	

	public function generateSSL(Request $request): JsonResponse
	{
		Log::info('SSL generation requested.');

		// 1) Validate input up front
		$data = $request->validate([
			'site_id' => ['required', 'integer', 'exists:sites,id'],
		]);

		$licenseOk   = $this->pete->checkPeteLicense();
		$envIsProd   = $this->peteOptions->get_meta_value('environment') === 'production';

		// 2) Environment & licensing guards (fail fast, with meaningful status)
		if (! $envIsProd) {
			return response()->json([
				'error'   => true,
				'message' => 'This feature is only available in the production environment.',
			], 403);
		}

		if (! $licenseOk) {
			return response()->json([
				'error'   => true,
				'message' => 'This feature is only available in the PRO version.',
			], 403);
		}

		// 3) Locate resource & authorize
		$site = Site::findOrFail((int) $data['site_id']);

		if (! Gate::allows('sites.manage', $site)) {
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

		// 4) Persist intent + trigger side effect
		try {
			$site->ssl = true;
			$site->ssl_created_at = now();
			$site->save();

			$userEmail = Auth::user()->email ?? 'unknown@example.com';
			$output = $site->generate_ssl($userEmail);

			Log::info('SSL generation initiated.', [
				'user_id' => Auth::id(),
				'site_id' => $site->id,
				'domain'  => $site->url,
			]);

			return response()->json([
				'error'   => false,
				'message' => $output,
				'site_id' => $site->id,
			]);
			
		} catch (\Throwable $e) {
			Log::error('SSL generation failed.', [
				'user_id' => Auth::id(),
				'site_id' => $site->id,
				'error'   => $e->getMessage(),
			]);

			return response()->json([
				'error'   => true,
				'message' => 'SSL generation failed. Please try again.',
			], 500);
		}
	}

	public function cloneSite(Request $request): RedirectResponse|JsonResponse
	{
		// 0) Normalize destination first (strip scheme, append domain template if needed)
		$tpl       = $this->peteOptions->get_meta_value('domain_template');
		$rawInput  = (string) $request->input('site_url', '');
		$fullDest  = $this->pete->normalizeUrlWithTemplate($rawInput, (string) $tpl);

		// Build a validation bag with the normalized value
		$input = array_replace($request->all(), ['site_url' => $fullDest]);

		// 1) Validate inputs
		$v = \Validator::make($input, [
			'site_to_clone_id' => ['required', 'integer', 'exists:sites,id'],
			'site_url' => [
				'required', 'max:255', 'regex:/^(?!\-)(?:[a-z0-9\-]+\.)+[a-z]{2,}$/i',
				Rule::unique('sites', 'url'),
			],
		], [
			 'site_url.regex' => 'Please enter a valid domain, e.g. mysite.com or sub.mysite.org',
		], [
			'site_to_clone_id' => 'site to clone',
			'site_url'         => 'destination URL',
		]);

		if ($v->fails()) {
			return $this->pete->fail($request, 'Validation failed.', $v->errors()->toArray(), 422);
		}

		// 2) Resolve resources & authorize
		$user        = $request->user();
		$siteToClone = \App\Models\Site::findOrFail((int) $input['site_to_clone_id']);

		if (! \Gate::allows('sites.manage', $siteToClone)) {
			if ($request->expectsJson()) {
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

		// 3) Domain/business guards
		if ($this->pete->isTheURLForbidden($fullDest)) {
			return $this->pete->fail($request, 'URL forbidden.', [
				'site_url' => ['This URL is not allowed.'],
			], 422);
		}

		// ==== fire-and-return (lean, no PID/log files) ======================
		$jobId     = (string) \Illuminate\Support\Str::uuid();
		$statusDir = storage_path('app/clone-jobs');
		@mkdir($statusDir, 0775, true);

		// seed status file so UI can show "Queued"
		file_put_contents("$statusDir/{$jobId}.json", json_encode([
			'status'     => 'queued',
			'progress'   => 0,
			'message'    => 'Queued',
			'created_at' => now()->toISOString(),
		], JSON_PRETTY_PRINT));

		// Build detached command (no PID/log file)
		$php = trim((string) @shell_exec('command -v php 2>/dev/null')) ?: '/usr/bin/php';
		if (!is_executable($php)) { $php = 'php'; } 
		                                  // e.g. /usr/local/bin/php
		$runner  = base_path('bootstrap/run_clone.php');
		$srcId   = (int) $siteToClone->id;
		$destUrl = $fullDest;
		$userId  = (int) $user->id;

		$cmd = sprintf(
			'cd %s && nohup %s %s --src=%d --dest=%s --user=%d --job=%s > /dev/null 2>&1 &',
			escapeshellarg(base_path()),
			escapeshellcmd($php),
			escapeshellarg($runner),
			$srcId,
			escapeshellarg($destUrl),
			$userId,
			escapeshellarg($jobId)
		);
		shell_exec($cmd);

		$payload = [
			'error'      => false,
			'message'    => 'Clone enqueued.',
			'job_id'     => $jobId,
			'status_url' => route('sites.clone.status', $jobId),
		];

		return ($request->expectsJson() || $request->ajax())
			? response()->json($payload, 202)
			: redirect()->route('sites.logs', 0)->with('status', $payload['message']);
	}
	
	public function create(){   
		$currentUser = Auth::user(); 
		$viewsw      = 'sites';
		return view('sites.create',compact('currentUser','viewsw'));
	}

	
	public function store(Request $request): RedirectResponse|JsonResponse
{
	// 0) Normalize URL with domain template before validating
	$tpl     = $this->peteOptions->get_meta_value('domain_template');
	$fullUrl = $this->pete->normalizeUrlWithTemplate($request->input('url', ''), (string) $tpl);
	$input   = array_replace($request->all(), ['url' => $fullUrl]);

	// 1) Validate inputs (no auto-redirects)
	$v = Validator::make($input, [
		'url' => [
			'required',
			'max:255',
			'regex:/^(?!\-)(?:[a-z0-9\-]+\.)+[a-z]{2,}$/i',
			Rule::unique('sites', 'url'),
		],
	]);

	if ($v->fails()) {
		return $this->pete->fail($request, 'Validation failed.', $v->errors()->toArray(), 422);
	}

	// Domain/business guards, return field-specific errors
	if ($this->pete->isTheURLForbidden($fullUrl)) {
		return $this->pete->fail($request, 'URL forbidden.', [
			'url' => ['This URL is not allowed.'],
		], 422);
	}

	// Prepare model
	$user = Auth::user();
	$site = new Site();
	$site->user_id = $user->id;
	$site->url     = $fullUrl;
	$site->set_project_name($site->url);

	try {
		// 4) Create site
		$site->create_wordpress();

		// 5) Force uploads hardening after creation
		$appRoot      = rtrim((string) $this->peteOptions->get_meta_value('app_root'), '/');
		$uploadsDir   = $appRoot . '/' . $site->name . '/wp-content/uploads';
		$templatePath = base_path('templates/htaccess_uploads.txt');
		$targetPath   = $uploadsDir . '/.htaccess';

		if ($appRoot !== '') {
			if (! is_dir($uploadsDir)) {
				@mkdir($uploadsDir, 0755, true);
			}

			if (is_file($templatePath) && is_readable($templatePath)) {
				if (! @copy($templatePath, $targetPath)) {
					\Log::warning('Failed to copy uploads .htaccess after site creation', [
						'site_id'       => $site->id,
						'site_name'     => $site->name,
						'template_path' => $templatePath,
						'target_path'   => $targetPath,
					]);
				} else {
					@chmod($targetPath, 0644);

					\Log::info('uploads .htaccess applied after site creation', [
						'site_id'     => $site->id,
						'site_name'   => $site->name,
						'target_path' => $targetPath,
					]);
				}
			} else {
				\Log::warning('uploads .htaccess template missing or unreadable after site creation', [
					'site_id'       => $site->id,
					'site_name'     => $site->name,
					'template_path' => $templatePath,
				]);
			}
		} else {
			\Log::warning('app_root empty; skipped uploads hardening after site creation', [
				'site_id'   => $site->id,
				'site_name' => $site->name,
			]);
		}

		// 6) Reload server
		\App\Services\OServer::reload_server();

		$payload = [
			'error'    => false,
			'message'  => 'Site created successfully.',
			'site_id'  => $site->id,
			'url'      => $site->url,
			'redirect' => route('sites.logs', $site->id),
		];

		return ($request->expectsJson() || $request->ajax())
			? response()->json($payload, 201)
			: redirect()->route('sites.logs', $site->id)->with('status', $payload['message']);

	} catch (\Throwable $e) {
		\Log::error('Site creation failed', [
			'exception' => $e,
			'user_id'   => \Auth::id(),
		]);

		return ($request->expectsJson() || $request->ajax())
			? response()->json([
				'error'   => true,
				'message' => 'Failed to create WordPress site. Please try again.',
			], 500)
			: back()->withInput()->withErrors('Failed to create WordPress site. Please try again.');
	}
}
	
	
	public function destroy(Request $request, Site $site): Response|RedirectResponse
	{
		$user = Auth::user();

		if (! Gate::allows('sites.manage', $site)) {
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
			$site->delete_wordpress();
			// For AJAX / fetch() calls (like your Vue button): HTTP 204
			if ($request->expectsJson() || $request->ajax()) {
				return response()->noContent();
			}

			// For non‑AJAX use (graceful fallback in case you link to it)
			return redirect()
				->route('sites.index')
				->with('status', 'Site deleted successfully.');
		} catch (\Throwable $e) {
			Log::error('Site delete failed', [
				'site_id' => $site->id,
				'error'   => $e->getMessage(),
			]);

			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'message' => 'Delete failed. Please try again.',
				], 500);
			}

			return redirect()
				->route('sites.index')
				->withErrors('Delete failed. Please try again.');
		}
	}

	public function logs(Request $request, Site $site): View
    {
        $currentUser = Auth::user();
		$viewsw      = 'sites';
        $appRoot     = (string) $this->peteOptions->get_meta_value('app_root');

		if (! Gate::allows('sites.manage', $site)) {
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

        $paths = [
            'web_server_error_file'  => "{$appRoot}/wwwlog/{$site->name}/error.log",
            'web_server_access_file' => "{$appRoot}/wwwlog/{$site->name}/access.log",
            'wordpress_debug_file'   => "{$appRoot}/{$site->name}/wp-content/debug.log",
        ];

        // Load tail of each file safely
        $errorLog   = $this->readTail($paths['web_server_error_file']);
        $accessLog  = $this->readTail($paths['web_server_access_file']);
        $wpDebugLog = $this->readTail($paths['wordpress_debug_file']);

        // Strip ANSI from terminal output for neat rendering
        $terminalOutput = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', (string) ($site->output ?? ''));

        return view('sites.logs', [
            'site'                         => $site,
            'currentUser'                 => $currentUser,
            'web_server_error_file'        => $paths['web_server_error_file'],
            'web_server_error_file_content'=> $errorLog['content'],
            'web_server_error_truncated'   => $errorLog['truncated'],
            'web_server_error_size'        => $errorLog['size'],

            'web_server_access_file'       => $paths['web_server_access_file'],
            'web_server_access_file_content'=> $accessLog['content'],
            'web_server_access_truncated'  => $accessLog['truncated'],
            'web_server_access_size'       => $accessLog['size'],

            'wordpress_debug_file'         => $paths['wordpress_debug_file'] ?? null,
            'wordpress_debug_file_content' => $wpDebugLog['content'],
            'wordpress_debug_truncated'    => $wpDebugLog['truncated'],
            'wordpress_debug_size'         => $wpDebugLog['size'],
			'viewsw'					   => $viewsw,

            'terminalOutput'               => $terminalOutput,
        ]);
    }

    /**
     * Read the last N bytes of a file (or whole file if smaller),
     * returning content, size, and whether it was truncated.
     */
    private function readTail(string $path, int $limit = self::LOG_MAX_BYTES): array
    {
        if (!is_readable($path) || !is_file($path)) {
            return [
                'content'   => 'file not found',
                'size'      => 0,
                'truncated' => false,
            ];
        }

        $size = filesize($path);
        if ($size === false) {
            return [
                'content'   => 'unavailable',
                'size'      => 0,
                'truncated' => false,
            ];
        }

        $fp = @fopen($path, 'rb');
        if ($fp === false) {
            return [
                'content'   => 'unavailable',
                'size'      => (int) $size,
                'truncated' => false,
            ];
        }

        // If larger than limit, seek to the last $limit bytes
        $truncated = false;
        if ($size > $limit) {
            fseek($fp, -$limit, SEEK_END);
            $truncated = true;

            // Optionally skip partial first line for cleaner output
            // fgets($fp);
        }

        $content = stream_get_contents($fp) ?: '';
        fclose($fp);

        return [
            'content'   => $content,
            'size'      => (int) $size,
            'truncated' => $truncated,
        ];
    }

	public function cloneStatus(string $id): \Illuminate\Http\JsonResponse
	{
		$path = storage_path("app/clone-jobs/{$id}.json");
		if (!is_file($path)) {
			return response()->json(['error'=>true,'message'=>'Not found'], 404);
		}
		$data = json_decode((string) file_get_contents($path), true) ?: [];
		// Optional: add an ownership check here if you want
		return response()->json($data + ['job_id' => $id]);
	}

}
