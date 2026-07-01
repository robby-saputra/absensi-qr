<?php

namespace App\Http;

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\WebRole;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Kernel HTTP mengatur middleware global, grup middleware, dan alias middleware aplikasi.
class Kernel extends HttpKernel
{
    public function __construct()
    {
        // dd('KERNEL LOADED');
    }

    // Middleware global berjalan pada semua request yang masuk ke aplikasi.
    protected $middleware = [
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    // Grup middleware memisahkan kebutuhan request web dan API.
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            SubstituteBindings::class,
        ],

        'api' => [
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ],
    ];

    // Alias middleware dipakai di route, misalnya webrole untuk membatasi akses role.
    protected $middlewareAliases = [
        'role' => CheckRole::class,
        'webrole' => WebRole::class,
    ];
}
