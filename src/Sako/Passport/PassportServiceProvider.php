<?php

namespace Sako\Passport;

use Illuminate\Support\ServiceProvider;
use Sako\Passport\Commands\PermissionGeneratorCommand;
use Sako\Passport\Commands\PermissionListCommand;

class PassportServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Publish your migrations
        $this->publishes([
            __DIR__.'/../../migrations/' => database_path('migrations')
        ], 'migrations');

        // Publish a config file
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('passport.php'),
        ], 'config');

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // classes
        $this->registerClasses();

        // commands
        $this->registerCommands();
    }

    /**
     * Register classes
     *
     * @return void
     */
    public function registerClasses()
    {
        $this->app['passport'] = $this->app->share(function($app)
        {
            return new Passport;
        });
    }

    /**
     * Register commands
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->app['passport::commands.permission_generator'] = $this->app->share(function($app)
        {
            return new PermissionGeneratorCommand;
        });

        $this->app['passport::commands.list_permissions'] = $this->app->share(function($app)
        {
            return new PermissionListCommand;
        });

        $this->commands('passport::commands.permission_generator');
        $this->commands('passport::commands.list_permissions');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('passport');
    }

}
