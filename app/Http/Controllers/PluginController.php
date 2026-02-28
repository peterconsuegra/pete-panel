<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PeteOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\PeteService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class PluginController extends Controller
{
    private PeteService $pete;

    public function __construct(PeteService $pete)
    {
        $this->middleware('auth');
        $this->pete = $pete;
    }

    /**
     * List available plugins (Pete + WP catalog).
     */
    public function index(Request $request): View
    {
        $currentUser  = Auth::user();
        $viewsw        = '/plugins';
        $pete_options  = new PeteOption(); // kept for layout compatibility
        $dashboard_url = (string) env('PETE_DASHBOARD_URL');

        // Allow cache refresh via ?refresh=1, delegated to the service.
        $data = $this->pete->getPlugins($request->boolean('refresh'));

        $pete_plugins  = $data->pete_plugins ?? [];
        $wp_plugins    = $data->wp_plugins ?? [];
        $error         = !empty($data->error);
        $error_message = $data->message ?? null;

        return view('plugins.index', compact(
            'viewsw',
            'pete_plugins',
            'wp_plugins',
            'dashboard_url',
            'currentUser',
            'pete_options',
            'error',
            'error_message'
        ));
    }

     /**
     * Install a Pete plugin via Composer and register the sidebar entry.
     */
    public function runPluginInstallScript(Request $request): RedirectResponse
    {
        if (! Gate::allows('user.admin')) {

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

        $validated = $request->validate([
            'packageName'   => ['required', 'string'],
            'pluginVersion' => ['required', 'string'],
            'pluginRoute'   => ['required', 'string'],
            'optionName'    => ['required', 'string'],
        ]);

        $this->pete->runPluginInstallScript($validated);

        return redirect($validated['pluginRoute']);
    }

    /**
     * Uninstall a Pete plugin and remove its sidebar entry.
     */
    public function runPluginUninstallScript(Request $request): RedirectResponse
    {
        if (! Gate::allows('user.admin')) {
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

        $validated = $request->validate([
            'packageName' => ['required', 'string'],
            'optionName'  => ['required', 'string'],
        ]);

        $this->pete->runPluginUninstallScript($validated);

        return redirect('/plugins/index');
    }

     /**
     * Update an installed Pete plugin (Composer update + option version).
     */
    public function runPluginUpdateScript(Request $request): RedirectResponse
    {
       if (! Gate::allows('user.admin')) {

            // Flash keys that your Blade already knows how to show
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

        // Match the service method signature: packageName, optionName, optionVersion.
        $validated = $request->validate([
            'packageName'   => ['required', 'string'],
            'pluginVersion' => ['required', 'string'],
            'pluginRoute'   => ['required', 'string'],
            'optionName'    => ['required', 'string'],
            'optionVersion'  => ['required', 'string']
        ]);

        $this->pete->runPluginUninstallScript($validated);
        $this->pete->runPluginInstallScript($validated);

        return redirect('/plugins/index');
    }
}
