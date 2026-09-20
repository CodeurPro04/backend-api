<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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

        Schema::defaultStringLength(191);

        $this->configureRateLimiting();
    }

    /**
     * Limites dediees pour les points d'entree les plus sensibles/couteux :
     * la seule limite globale (throttle:api, 60/min, voir bootstrap/app.php)
     * ne suffit pas a empecher le bruteforce d'un login ou l'abus d'un
     * endpoint IA/public.
     */
    private function configureRateLimiting(): void
    {
        // Limite par defaut pour "throttle:api" (appliquee a toute l'API via
        // $middleware->throttleApi() dans bootstrap/app.php). Sans cette
        // definition, Laravel leve "Rate limiter [api] is not defined." des
        // la premiere requete, meme sur des routes publiques comme /login.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Bruteforce login : 5 tentatives/minute, combinees IP + email cible,
        // pour ne pas bloquer tout un cybercafe/NAT partage sur un seul compte.
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')) . '|' . $request->ip();
            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $key = strtolower((string) $request->input('email')) . '|' . $request->ip();
            return Limit::perMinute(3)->by($key);
        });

        // Endpoint public sans authentification qui proxy un LLM local : le plus
        // couteux en ressources serveur de toute l'API, et le plus attractif a
        // spammer puisqu'il ne demande aucun compte.
        RateLimiter::for('ai-chat', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Formulaires publics sans authentification qui creent des enregistrements
        // (et, pour client-requests, un compte utilisateur) : a proteger contre le
        // spam/l'epuisement de stockage.
        RateLimiter::for('public-write', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
