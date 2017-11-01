<?php 
namespace Asdozzz\Library;

use Illuminate\Support\ServiceProvider;

class LibraryServiceProvider extends ServiceProvider
{

	public function register()
	{
		$this->app->bind('tconfig', function ($app) 
		{
			return new TableConfig;
		});

		$this->app->bind('tlist', function ($app) 
		{
			return new TablesList;
		});
	}

	public function boot()
	{
		require __DIR__ . '/Libraries/TableConfig.php';
		require __DIR__ . '/Libraries/TablesList.php';
	}

}