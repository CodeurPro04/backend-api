<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    // En mode Bearer token, pas de CSRF cookie
    // $middleware->api(prepend: [
    //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    // ]);

    // Limite par defaut sur TOUTES les routes /api (60 req/min), en plus des
    // limites plus strictes definies specifiquement (login, register, ai/chat...
    // via RateLimiter::for dans AppServiceProvider). Sans ceci, seules les routes
    // explicitement throttlees etaient protegees ; le reste de l'API n'avait
    // aucune limite.
    $middleware->throttleApi();

    $middleware->api(append: [
        \App\Http\Middleware\SecurityHeaders::class,
    ]);

    // Le VPS est deploye derriere un reverse proxy local (Nginx) : sans faire
    // confiance au proxy, $request->ip() (utilise par le rate limiting ci-dessus
    // et par les logs d'activite) refleterait l'IP du proxy plutot que celle du
    // client, ce qui rendrait toute limite par IP inefficace.
    $middleware->trustProxies(at: '*');

    $middleware->alias([
        'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        'checkrole' => \App\Http\Middleware\CheckRole::class,
    ]);
})

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
