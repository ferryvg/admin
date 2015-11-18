<?php

namespace SleepingOwl\Admin\FormItems;

use Exception;
use Input;
use Route;
use SleepingOwl\Admin\AssetManager\AssetManager;
use SleepingOwl\Admin\Interfaces\WithRoutesInterface;
use SplFileInfo;
use stdClass;
use Symfony\Component\Finder\Finder;

/**
 * Class CKEditor
 * @package SleepingOwl\Admin\FormItems
 */
class CKEditor extends NamedFormItem implements WithRoutesInterface
{

    use ShowableFormItem;

    /**
     * view to render
     *
     * @var string
     */
    protected $view = 'ckeditor';

    /**
     * Initilaize
     *
     */
    public function initialize()
	{
		parent::initialize();

		AssetManager::addScript('admin::default/js/formitems/ckeditor/ckeditor.js');
	}

    /**
     * register laravel routes
     *
     */
    public static function registerRoutes()
	{
		Route::get('assets/images/all', '\SleepingOwl\Admin\Http\Controllers\AdminController@getAllImages');
		Route::post('assets/images/upload', '\SleepingOwl\Admin\Http\Controllers\AdminController@postUpload');
	}

}