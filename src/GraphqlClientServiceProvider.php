<?php

namespace BendeckDavid\GraphqlClient;

use Illuminate\Support\ServiceProvider;
use BendeckDavid\GraphqlClient\Classes\Client;

class GraphqlClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('graphqlClient', function($app) {
            return new Client(config('graphqlclient.graphql_endpoint'), config('graphqlclient.token'), config('graphqlclient.auth_scheme'));
        });
        
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'graphqlclient');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {  
        if ($this->app->runningInConsole()){
            $this->publishes([
            __DIR__.'/../config/config.php' => config_path('graphqlclient.php'),
            ], 'config');
        }
    }
}
