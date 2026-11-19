<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Aucun binding supplémentaire.
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));

        Fortify::requestPasswordResetLinkView(
            fn () => view('auth.forgot-password')
        );

        Fortify::resetPasswordView(
            fn (Request $request) => view('auth.reset-password', [
                'request' => $request,
            ])
        );

        RateLimiter::for('login', function (Request $request): Limit {
            $key = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($key);
        });
    }
}
