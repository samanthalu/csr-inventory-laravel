<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Product;
use App\Policies\UserPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Gate;
// use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

    protected $policies = [
        User::class => UserPolicy::class,
        Product::class => ProductPolicy::class
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
        $this->registerPolicies();

        // Define Gates for each permission
        Gate::define('read', function (User $user) {
            return ($user->legacy_permissions & User::PERMISSION_READ) === User::PERMISSION_READ;
        });

        Gate::define('create', function (User $user) {
            return ($user->legacy_permissions & User::PERMISSION_CREATE) === User::PERMISSION_CREATE;
        });

        Gate::define('edit', function (User $user) {
            return ($user->legacy_permissions & User::PERMISSION_EDIT) === User::PERMISSION_EDIT;
        });

        Gate::define('delete', function (User $user) {
            return ($user->legacy_permissions & User::PERMISSION_DELETE) === User::PERMISSION_DELETE;
        });

        // Admin-only — super-admin role OR legacy user_type check
        Gate::define('admin-only', fn(User $u) =>
            $u->hasRole('super-admin') || $u->user_type === User::TYPE_ADMIN);

        // Hire management
        Gate::define('manage-hire', fn(User $u) =>
            $u->hasAnyPermission(['create_hires', 'update_hires', 'delete_hires'])
            || in_array($u->user_type, [User::TYPE_ADMIN, User::TYPE_ICT]));

        // Maintenance management
        Gate::define('manage-maintenance', fn(User $u) =>
            $u->hasAnyPermission(['create_maintenance', 'update_maintenance', 'delete_maintenance'])
            || in_array($u->user_type, [User::TYPE_ADMIN, User::TYPE_ICT, User::TYPE_ADMINISTRATION]));

        // Disposal management
        Gate::define('manage-disposal', fn(User $u) =>
            $u->hasAnyPermission(['create_disposal', 'update_disposal', 'delete_disposal'])
            || in_array($u->user_type, [User::TYPE_ADMIN, User::TYPE_ICT, User::TYPE_ADMINISTRATION]));

        // Invoice management
        Gate::define('manage-invoices', fn(User $u) =>
            $u->hasAnyPermission(['create_hires', 'update_hires'])
            || in_array($u->user_type, [User::TYPE_ADMIN, User::TYPE_ICT]));
    }
}
