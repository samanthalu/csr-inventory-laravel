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
    }
}
