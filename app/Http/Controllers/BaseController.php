<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PeteService;
use Illuminate\Contracts\Foundation\Application as FoundationApp;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Option;
use App\Services\OServer;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BaseController
 *
 * App-wide, lightweight endpoints that don't fit a specific resource
 * (welcome, license validation/registration, etc).
 */
final class BaseController extends Controller
{
    /**
     * Pete integration/service layer.
     */
    private PeteService $pete;


    /**
     * Enforce authentication on every action and inject PeteService.
     */
    public function __construct(PeteService $pete)
    {
        $this->middleware('auth');
        $this->pete = $pete;
    }
    public function security(Request $request): View
    {
        $securityStatus     = Option::get_meta_value('security_status');
        $currentUser        = Auth::user();
        $viewsw             = '/security';
        $success            = (string) $request->input('success');

        // Trim the raw JSON log first
        /*
        OServer::truncate_security_log(
            lines: (int) $request->input('trim_lines', 5000)
        );
        */

        $output             = OServer::print_security_report();
        $corerulesetVersion = Option::get_meta_value('coreruleset_version');

        return view(
            'base.security',
            compact('viewsw', 'success', 'currentUser', 'securityStatus', 'corerulesetVersion')
        );
    }

    public function securityReport(): Response
    {
        $filePath = base_path() . '/storage/logs/modsec-audit.html';
        Log::info("FILE PATH {$filePath}");

        return response(file_get_contents($filePath), 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Developer playground landing page.
     *
     * @return View
     */
    public function devPlaygroundWelcome(): View
    {
        // Sidebar highlighting expects this exact key in the layout.
        $viewsw = 'sites';

        // Current authenticated user (required by layout/sidebar).
        $currentUser = Auth::user();

        return view('base.devPlaygroundWelcome', compact('viewsw', 'currentUser'));
    }

    /**
     * Validate a Pete license for the current domain.
     *
     * Expects JSON payload:
     *  - oemail   : string (valid email)
     *  - oapi_key : string (non-empty)
     *  - odomain  : string (non-empty; the domain being validated)
     *
     * Returns a JSON response produced by the service layer, or a
     * standardized error shape if validation fails.
     */
    public function validatePete(Request $request): JsonResponse
    {
        // Lightweight input guardrail; aligns with the Vue form constraints.
        $validated = $request->validate([
            'oemail'   => ['required', 'email'],
            'oapi_key' => ['required', 'string'],
            'odomain'  => ['required', 'string'],
        ]);

        // Delegate to the service as before; keep return shape intact.
        // Service is expected to return an array with relevant data/message.
        $response = $this->pete->validatePete($request->input('oemail'),$request->input('oapi_key'),$request->input('odomain'));

        return response()->json($response);
    }

    /**
     * Show the "Register your License" screen.
     *
     * @return View
     */
    public function registerYourLicense(Request $request): View
    {
        $currentUser = Auth::user();
        $viewsw = 'sites';

        // Service returns "YES"/"NO" (kept for backward compatibility with the Blade).
        $validate = $this->pete->checkPeteLicense();

        return view('base.registerYourLicense', compact('viewsw', 'currentUser', 'validate'));
    }

    private const MIGRATION_PLUGIN_FILENAME = 'site-migration-backup-export-pete-panel.zip';

    /**
     * Canonical source of the plugin: the Pete dashboard always hosts the
     * newest release at a stable URL, independent of this panel's version.
     */
    private function migrationPluginRemoteUrl(): ?string
    {
        $dashboardUrl = rtrim((string) env('PETE_DASHBOARD_URL'), '/');

        return $dashboardUrl !== '' ? $dashboardUrl . '/' . self::MIGRATION_PLUGIN_FILENAME : null;
    }

    /**
     * Bundled snapshot, used only when the dashboard is unreachable.
     */
    private function migrationPluginPath(): string
    {
        return storage_path('app/pete-migration/' . self::MIGRATION_PLUGIN_FILENAME);
    }

    /**
     * Show the "Pete Migration Plugin" screen: how the exporter works and a
     * download link for the WordPress plugin that converts any site into the
     * Pete Panel import format.
     */
    public function peteMigrationPlugin(): View
    {
        $currentUser = Auth::user();
        $viewsw      = '/pete-migration-plugin';

        return view('base.peteMigrationPlugin', compact('viewsw', 'currentUser'));
    }

    /**
     * Stream the migration plugin zip to the browser — latest release from
     * the Pete dashboard first, bundled snapshot as offline fallback.
     */
    public function peteMigrationPluginDownload(): \Symfony\Component\HttpFoundation\Response
    {
        $remote = $this->migrationPluginRemoteUrl();

        if ($remote !== null) {
            // http wrapper without ignore_errors: fopen returns false on 4xx/5xx.
            $context = stream_context_create(['http' => ['timeout' => 20]]);
            $stream  = @fopen($remote, 'rb', false, $context);

            if ($stream !== false) {
                return response()->streamDownload(function () use ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }, self::MIGRATION_PLUGIN_FILENAME, ['Content-Type' => 'application/zip']);
            }

            Log::warning('Pete migration plugin: dashboard download unavailable, serving bundled snapshot', ['url' => $remote]);
        }

        $path = $this->migrationPluginPath();
        abort_unless(is_file($path), 404, 'Migration plugin package not found on this server.');

        return response()->download($path, self::MIGRATION_PLUGIN_FILENAME);
    }
}
