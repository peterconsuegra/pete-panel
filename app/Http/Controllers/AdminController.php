<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Option;
use App\Services\OServer;
use App\Services\PeteOption;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;
use App\Services\PeteService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    private PeteService $pete;
    private PeteOption $peteOptions;

    public function __construct(PeteService $pete, PeteOption $peteOptions)
    {
        $this->middleware('auth');
        $this->pete = $pete;
        $this->peteOptions = $peteOptions;

        $this->middleware(function ($request, $next) {
            if (! Gate::allows('user.admin')) {
               
                // If the request expects a JSON response (e.g., AJAX or API), return a 403 JSON error
			if (request()->expectsJson()) {

                Log::info("not allowed AdminController");
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
            
            return $next($request);
        });

    }

    /**
     * Get Pete latest version info.
     */
    public function versionCheck()
    {
        Log::info("enter in admin versionCheck");
        return response()->json($this->pete->getLastVersion());
    }

    /**
     * Show update page with latest version info.
     */
    public function update(Request $request): View
    {
        $environment = (string) $this->peteOptions->get_meta_value('environment');
        $installed   = (string) $this->peteOptions->get_meta_value('version');

        $latest    = (string) $this->pete->getLastVersion();
        $hasUpdate = version_compare($installed, $latest, '<');

        return view('admin.update', [
            'environment'  => $environment,
            'version'      => $installed,
            'latest'       => $latest,
            'hasUpdate'    => $hasUpdate,
            'checkedAt'    => Carbon::now()->toDayDateTimeString(),
            'viewsw'       => '/sites',
            'currentUser' => Auth::user(),
        ]);
    }

    /**
     * Execute the core update flow.
     */
    public function updateCore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'latest' => ['required', 'string'],
        ]);

        $this->pete->runUpdateScript($validated);       
        return Redirect::to('/admin/update');
    }

    public function phpmyadminPanel(): View
    {
        $currentUser      = Auth::user();
        $rootPass         = (string) env('PETE_ROOT_PASS');
        $viewsw           = '/phpmyadminPanel';
        $peteDemo         = (string) env('PETE_DEMO');
        $phpmyadminStatus = (string) Option::get_meta_value('phpmyadmin_status');

        return view(
            'admin.phpmyadminPanel',
            compact('rootPass', 'viewsw', 'currentUser', 'peteDemo', 'phpmyadminStatus')
        );
    }

    public function serverStatus(): View
    {
        $currentUser  = Auth::user();
        $serverStatus = Option::get_meta_value('server_status');
        $viewsw       = '/serverStatus';
        $success      = '';

        return view('admin.serverStatus', compact('viewsw', 'success', 'currentUser', 'serverStatus'));
    }

    public function serverSSH(): View
    {
        // NEW — fetch SSH public key via the shell script
        [$sshPubKey, $sshKeyError] = OServer::readSshPublicKey();

        $currentUser  = Auth::user();
        $viewsw       = '/serverSSH';
        $success      = '';

        return view('admin.serverSSH', compact('viewsw', 'success', 'currentUser', 'sshPubKey','sshKeyError'));
    }

    public function securityStatusToggle(Request $request)
    {
        $status = Option::create_or_update_meta('security_status', (string) $request->input('security_status_sw'));
        OServer::toggle_security($status);
        OServer::reload_server();

        return response()->json(['ok' => 'OK']);
    }

    public function serverStatusToggle(Request $request)
    {
        Log::info("enter in serverStatusToggle");
        $status = Option::create_or_update_meta('server_status', (string) $request->input('server_status_sw'));
        OServer::toggle_server_status($status);
        OServer::reload_server();

        return response()->json(['ok' => 'OK']);
    }

    public function phpmyadminStatusToggle(Request $request)
    {
        $status = Option::create_or_update_meta('phpmyadmin_status', (string) $request->input('phpmyadmin_status_sw'));
        OServer::toggle_phpmyadmin($status);
        OServer::reload_server();

        return response()->json(['ok' => 'OK']);
    }

    public function phpinfoView(): View
    {
		return view('admin.phpinfoView');
	}

    public function phpinfoPanel(): View
    {
        $viewsw   = 'phpinfoPanel';
        $currentUser  = Auth::user();
		return view('admin.phpinfoPanel',compact('viewsw','currentUser'));

	}
}
