<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // Middleware globaux
    protected $middleware = [
        // ...
    ];

    // Groupes de middleware (exemple : web, api)
    protected $middlewareGroups = [
        'web' => [
            // ...
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    // Middleware enregistrés pour être utilisés sur les routes
    protected $routeMiddleware = [
        'checkrole' => \App\Http\Middleware\CheckRole::class,
        // autres middlewares...
    ];
}
