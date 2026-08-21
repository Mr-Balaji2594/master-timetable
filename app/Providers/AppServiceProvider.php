<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::serializeUsing(fn (Carbon $date) => $date->format('d/m/Y'));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Trust requests arriving from common reverse proxies so IP-based
        // security features (login lockout, audit log) see the real client IP.
        $this->app['request']->setTrustedProxies(
            ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );
    }
}
