<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Domain\Auth\Contracts\TokenIssuerInterface;
use App\Infrastructure\Authentication\Rs256JwtTokenIssuer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TokenIssuerInterface::class,
            Rs256JwtTokenIssuer::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
