<?php

namespace App\Http\Middleware;

use Closure;
use DateTime;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Services\PeteOption;
use App\Models\Option;
use Log;
use App\Services\PeteService;

class AdIndex
{
    /**
     * Handle an incoming request.
     *
     * - Safely handles Closure and invokable routes (no 'controller' key).
     * - Runs license checks only for the specific controllers/method you care about.
     */

    private PeteService $pete;

    public function __construct(PeteService $pete)
    {
        $this->pete = $pete;
    }

    public function handle($request, Closure $next)
    {
        // Resolve the matched route and its action safely
        $route  = Route::getRoutes()->match($request);
        $action = $route->getAction();

        // When the route is a Closure there is no 'controller' key.
        $controllerFull = $action['controller'] ?? null;

        // Default validation flag
        $validate = 'NO';

        // If it's a Closure route, skip the special checks and continue
        if (!$controllerFull) {
            $request->attributes->set('validate', $validate);
            return $next($request);
        }

        // Normalize controller and method (supports Foo@bar and invokable controllers)
        if (strpos($controllerFull, '@') !== false) {
            [$controllerClass, $controllerMethod] = explode('@', $controllerFull, 2);
        } else {
            $controllerClass  = $controllerFull; // invokable controller
            $controllerMethod = '__invoke';
        }

        // Load Pete options once
        $pete_options    = app(\App\Services\PeteOption::class);
        $domain_template = $pete_options->get_meta_value('domain_template');

        // Decide if we should run license checks for this request
        $isWpPlusLaravel  = $controllerClass === 'Pete\WordPressPlusLaravel\Http\WordPressPlusLaravelController';
        $isWpImporter     = $controllerClass === 'Pete\WordPressImporter\Http\WordPressImporterController';
        $isPeteBackups    = $controllerClass === 'Pete\PeteBackups\Http\PeteBackupsController';
        $isPluginsInstall = $controllerMethod === 'runPluginInstallScript';

        if ($isPluginsInstall || $isWpPlusLaravel || $isWpImporter || $isPeteBackups) {

            // Local/dev playground is always valid
            if ($domain_template === 'petelocal.net') {
                $validate = 'YES';
            } else {
                // Check current validation status
                $validate = $this->pete->checkPeteLicense() ?: 'NO';
            }

            if ($validate === 'NO') {
                return redirect('/register-your-license');
            }

            // If validation event has passed, re‑validate (only for non-petelocal.net)
            $validationEventStr = $pete_options->get_meta_date('validation_event');
            $validation_event   = $validationEventStr ? new DateTime($validationEventStr) : null;
            $now                = new DateTime();

            if ($validation_event && $now > $validation_event && $domain_template !== 'petelocal.net') {
                $apiKey   = $pete_options->get_meta_value('validation_api_key');
                $email    = $pete_options->get_meta_value('validation_user_email');
                $domain   = $pete_options->get_meta_value('validation_domain');

                $this->pete->validatePete($email, $apiKey, $domain);
                $validate = $this->pete->checkPeteLicense() ?: 'NO';

                if ($validate === 'NO') {
                    // Clean up and force re-registration
                    Option::delete_meta_value('validation_event');
                    Option::delete_meta_value('validation_api_key');
                    Option::delete_meta_value('token');

                    return redirect('/register-your-license');
                }
            }
        }

        // Expose validate flag to downstream (views/controllers expect it)
        $request->attributes->set('validate', $validate);

        return $next($request);
    }
}
