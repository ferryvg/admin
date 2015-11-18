<?php namespace SleepingOwl\Admin\FormItems;

use Route;
use SleepingOwl\Admin\AssetManager\AssetManager;
use SleepingOwl\Admin\Interfaces\WithRoutesInterface;

class Image extends NamedFormItem implements WithRoutesInterface
{

	protected $view = 'image';
	protected static $route = 'uploadImage';

	public function initialize()
	{
		parent::initialize();

		AssetManager::addScript('admin::default/js/formitems/image/init.js');
		AssetManager::addScript('admin::default/js/formitems/image/flow.min.js');
	}

	public static function registerRoutes()
	{
		Route::post('formitems/image/' . static::$route, [
			'as' => 'admin.formitems.image.' . static::$route,'AdminController@uploadImage'
		]);
	}

}