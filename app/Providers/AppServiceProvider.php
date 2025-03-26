<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserRoles;
use App\Policies\UserPolicy;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Define a gate that determines if a user can create a user

        Gate::define('create-user', function(User $user, UserRoles $roles) {
                      
            // return $roles->role_user_type === 'admin' ? Response::allow() : Response::deny('You are not permitted to do this operation');
            return $roles->role_user_type === 'admin' || $roles->role_write;
        });

        Gate::define('update-user', function(User $user) {  
            $roles = UserRoles::firstWhere('role_user_id', $user->id);
            return $roles->role_user_type === 'admin' || $roles->role_edit;
        });

        // Manually register a policy &^*&^*^& #$$%^ Did not work
        // Gate::policy(User::class, UserPolicy::class);
    }
}
