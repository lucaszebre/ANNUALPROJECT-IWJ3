<?php

namespace App\Providers;

use App\Enums\MemberRole;
use App\Models\User;
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
        // Role checks used by the navigation (@can). A user has a role
        // through any of their organisation memberships.
        Gate::define('teach', fn (User $user) => $user->memberships()
            ->whereIn('role', [MemberRole::Teacher, MemberRole::Admin])
            ->exists());

        Gate::define('administer', fn (User $user) => $user->memberships()
            ->where('role', MemberRole::Admin)
            ->exists());
    }
}
