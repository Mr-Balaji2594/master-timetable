<?php

namespace App\Providers;

use App\Auth\EmployeeUserProvider;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['auth']->provider('employee', function ($app, array $config) {
            return new EmployeeUserProvider;
        });
    }
}
