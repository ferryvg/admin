<?php namespace SleepingOwl\AdminAuth;

use Config;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Manager;

class AdminAuthManager extends AuthManager {

	/**
	 * Create an instance of the Eloquent driver.
	 *
	 * @return \Illuminate\Auth\Guard
	 */
	public function createEloquentDriver()
	{
		$provider = $this->createEloquentProvider('');

		return new Guard('eloquent', $provider, $this->app['session.store'], $this->app['request']);
	}

	/**
	 * Create an instance of the Eloquent user provider.
	 *
	 * @return \Illuminate\Auth\EloquentUserProvider
	 */
	protected function createEloquentProvider($name)
	{
		$model = Config::get('admin.auth.model');

		return new EloquentUserProvider($this->app['hash'], $model);
	}

	protected function getConfig($name)
	{
		return [
			'driver' => $this->getDefaultDriver(),
            'provider' => $this->getDefaultDriver(),
		];
	}

	/**
	 * Get the default authentication driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return 'eloquent';
	}

}
