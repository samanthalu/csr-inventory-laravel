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
            return ($user->permissions & User::PERMISSION_READ) === User::PERMISSION_READ;
        });

        Gate::define('create', function (User $user) {
            return ($user->permissions & User::PERMISSION_CREATE) === User::PERMISSION_CREATE;
        });

        Gate::define('edit', function (User $user) {
            return ($user->permissions & User::PERMISSION_EDIT) === User::PERMISSION_EDIT;
        });

        Gate::define('delete', function (User $user) {
            return ($user->permissions & User::PERMISSION_DELETE) === User::PERMISSION_DELETE;
        });

        // Admin-only (system admin)
        Gate::define('admin-only', fn(User $u) =>
            $u->user_type === User::TYPE_ADMIN);

        // Hire management: admin + ict
        Gate::define('manage-hire', fn(User $u) =>
            in_array($u->user_type, [User::TYPE_ADMIN, User::TYPE_ICT]));

        // Maintenance management: admin + ict + administration
        Gate::define('manage-maintenance', fn(User $u) =>
            in_array($u->user_type, [User::TYPE_ADMIN, User::TYPE_ICT, User::TYPE_ADMINISTRATION]));

        // Disposal management: admin + ict + administration
        Gate::define('manage-disposal', fn(User $u) =>
            in_array($u->user_type, [User::TYPE_ADMIN, User::TYPE_ICT, User::TYPE_ADMINISTRATION]));

        // Invoice management: admin + ict
        Gate::define('manage-invoices', fn(User $u) =>
            in_array($u->user_type, [User::TYPE_ADMIN, User::TYPE_ICT]));
    }
}
