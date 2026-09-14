<?php

namespace App\Providers;

use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Lote;
use App\Models\RegistroOrdenio;
use App\Models\Vacuna;
use App\Models\Vacunacion;
use App\Models\Veterinario;
use App\Policies\CatalogoPolicy;
use App\Policies\GanaderiaPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        foreach ([Finca::class, Lote::class, Ganado::class, Vacunacion::class, RegistroOrdenio::class] as $model) {
            Gate::policy($model, GanaderiaPolicy::class);
        }
        foreach ([Vacuna::class, Veterinario::class] as $model) {
            Gate::policy($model, CatalogoPolicy::class);
        }

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(30)->by($request->ip()),
        ]);
    }
}
