<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Le scaffolding Breeze (routes/auth.php + app/Http/Controllers/Auth/*) a ete
// retire : il exposait un second systeme d'authentification base sur les
// sessions web (register/login/logout/reset), en doublon non securise de
// l'API reelle (Api\AuthController, jetons Sanctum). Sa route /register
// notamment permettait a n'importe qui de s'auto-inscrire avec le role
// "admin" et de recevoir un jeton valide immediatement. Toute l'authentification
// passe desormais exclusivement par /api/v1/auth/*.
