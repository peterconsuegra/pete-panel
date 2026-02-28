<?php

namespace App\Providers;

use App\Models\Site;
use App\Models\Backup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        $this->registerPolicies();

        // ------------------------------------------------------------
        // Gate: user.admin
        // Purpose: Grants access if the authenticated user has the
        //          `admin` flag set to true. Used to restrict admin-only
        //          actions such as system configuration or managing users.
        // ------------------------------------------------------------
        Gate::define('user.admin', static fn (Authenticatable $user): bool =>
            (bool) data_get($user, 'admin', false)
        );

        // ------------------------------------------------------------
        // Gate: user.non-admin
        // Purpose: Grants access if the authenticated user is NOT an
        //          administrator. Useful for flows or views intended
        //          only for standard users (e.g., onboarding, limited dashboards).
        // ------------------------------------------------------------
        Gate::define('user.non-admin', static fn (Authenticatable $user): bool =>
            ! (bool) data_get($user, 'admin', false)
        );

        // ------------------------------------------------------------
        // Gate: sites.manage
        // Purpose: Grants access if the authenticated user is either:
        //          - An administrator, or
        //          - The owner of the given Site model instance.
        //          Covers CRUD operations and site management features.
        // ------------------------------------------------------------
        Gate::define('sites.manage', static function (Authenticatable $user, Site $site): bool {
            $isAdmin = (bool) data_get($user, 'admin', false);
            $isOwner = (int) $site->user_id === (int) data_get($user, 'id');

            return $isAdmin || $isOwner;
        });

        // ------------------------------------------------------------
        // Gate: backups.manage
        // Purpose: Grants access if the authenticated user is either:
        //          - An administrator, or
        //          - The owner of the given Backup model instance.
        //          Covers CRUD operations and backup management features.
        // ------------------------------------------------------------
        Gate::define('backups.manage', static function (Authenticatable $user, Backup $backup): bool {
            $isAdmin = (bool) data_get($user, 'admin', false);
            $isOwner = (int) $backup->user_id === (int) data_get($user, 'id');

            return $isAdmin || $isOwner;
        });

        // ------------------------------------------------------------
        // Log unauthorized access attempts
        // This listener will capture any denied authorization checks,
        // logging details such as the user, route, IP, and gate name.
        // ------------------------------------------------------------
        $this->app['events']->listen(AuthorizationException::class, function ($e) {
            $user = auth()->user();

            Log::warning('Unauthorized access attempt', [
                'user_id' => $user?->id,
                'email'   => $user?->email,
                'route'   => request()->path(),
                'ip'      => request()->ip(),
                'gate'    => $e->getMessage(),
            ]);
        });
    }
}
