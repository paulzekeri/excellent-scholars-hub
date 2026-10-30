<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // One login field accepts either an email address or a username, so a
        // child without a mailbox can sign in with the username the school gave
        // them (note 12). Login, throttling, remember-me and the password broker
        // all keep their standard behaviour.
        Auth::provider('esh-eloquent', function ($app, array $config) {
            return new \App\Auth\EmailOrUsernameUserProvider($app['hash'], $config['model']);
        });
    }
}
