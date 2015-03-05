<?php namespace Sako\Passport;

use Illuminate\Support\ServiceProvider;
use Sako\Passport\Commands\PermissionGeneratorCommand;

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
		// package info
		$this->package('sako/passport');

		// filters
		include __DIR__ . '/../../filters.php';
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

		$this->commands('passport::commands.permission_generator');
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
