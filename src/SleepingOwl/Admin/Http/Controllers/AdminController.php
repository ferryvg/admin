<?php

namespace SleepingOwl\Admin\Http\Controllers;

use AdminTemplate;
use App;
use Eloquent;
use Exception;
use Gate;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Input;
use Redirect;
use SleepingOwl\Admin\Interfaces\FormInterface;
use SleepingOwl\Admin\Model\ModelConfiguration;
use SleepingOwl\Admin\Repository\TreeRepository;
use SplFileInfo;
use stdClass;
use Symfony\Component\Finder\Finder;
use Validator;
use View;
use Response as ResponseFacade;

/**
 * Class AdminController
 *
 * @package SleepingOwl\Admin\Http\Controllers
 */
class AdminController extends Controller
{
	/**
	 * Check acl: permissions and policies
	 *
	 * @param ModelConfiguration $model
	 * @param $action
	 * @param null $id
     */
	public static function check_acl($model, $action, $id=null){
        $model_undercase_name = strtolower(class_basename($model->getClass()));
        if (! self::checkPermissionAccess($model, $action)) {
            View::share('permission', $model_undercase_name.'-' . $action);
            abort(403);
        }
        if ($id != null ) {
            if (! self::checkPolicyAccess($model, $action, $id)) {
                View::share('permission', "You don't have permissions to access object " . $model_undercase_name . ' with id: ' . $id);
                abort(403);
            }
        }
	}

	/**
     * Check permission access
	 *
     * @param ModelConfiguration $model
     * @param $action
     * @return bool
     */
    public static function checkPermissionAccess($model, $action){
        if ( ! $model->aclsAreActive()) return true;
        $model_undercase_name = strtolower(class_basename($model->getClass()));
        if (Gate::denies($model_undercase_name . '-' . $action)) {
            return false;
        }
        return true;
    }

    /**
     * Check policy Access
	 *
     * @param ModelConfiguration $model
     * @param $action
     * @param integer $id
     * @return bool
     */
    public static function checkPolicyAccess($model, $action, $id){
        if ( ! $model->aclsAreActive()) return true;
        $class = $model->getClass();
        if (self::model_uses_soft_deletes($class)) {
            $laravel_model = $class::withTrashed()->findOrFail($id);
        } else {
            $laravel_model = $class::findOrFail($id);
        }
		return self::checkPolicy($action, $laravel_model);
    }

    /**
     * Check policy Access
     *
     * @param ModelConfiguration $model
     * @param $action
     * @param $instance
     * @return bool
     */
    public static function checkPolicyAccessByInstance($model, $action, $instance){
		if ( ! $model->aclsAreActive()) return true;
		return self::checkPolicy($action, $instance);
	}

    /**
     * check Policy
     *
     * @param $action
     * @param $instance
     * @return bool
     */
    protected static function checkPolicy($action, $instance)
    {
        if (Gate::denies($action, $instance)) {
            return false;
        }
        return true;
    }

	/**
	 * Check if model uses trait softDeletes
	 *
	 * @param Eloquent | string $model
	 * @return bool
     */
	public static function model_uses_soft_deletes($model){
		if ( in_array(SoftDeletes::class, class_uses_recursive($model)) ) {
			return true;
		}
	}

	/**
	 * get Display
	 *
	 * @param ModelConfiguration $model
	 * @return View
     */
	public function getDisplay($model)
	{
		$this->check_acl($model,'retrieve');
		return $this->render($model->title(), $model->display());
	}

	/**
	 * get Create
	 *
	 * @param ModelConfiguration $model
	 * @return View
     */
	public function getCreate($model)
	{
		$this->check_acl($model,'create');
		$create = $model->create();
		if (is_null($create))
		{
			abort(404);
		}
		return $this->render($model->title(), $create);
	}

	/**
	 * post Store
	 *
	 * @param ModelConfiguration $model
	 * @return \Illuminate\Http\RedirectResponse
     */
	public function postStore($model)
	{
		$this->check_acl($model,'store');
		$create = $model->create();
		if (is_null($create))
		{
			abort(404);
		}
		if ($create instanceof FormInterface)
		{
			if ($validator = $create->validate($model))
			{
				return Redirect::back()->withErrors($validator)->withInput()->with([
					'_redirectBack' => Input::get('_redirectBack'),
				]);
			}
			$create->save($model);
		}
		return Redirect::to(Input::get('_redirectBack', $model->displayUrl()));
	}

	/**
	 * get Edit
	 *
	 * @param ModelConfiguration $model
	 * @param $id
	 * @return View
     */
	public function getEdit($model, $id)
	{
		$this->check_acl($model,'edit',$id);
		$edit = $model->fullEdit($id);
		if (is_null($edit))
		{
			abort(404);
		}
		return $this->render($model->title(), $edit);
	}

	/**
	 * get show
	 *
	 * @param ModelConfiguration $model
	 * @param $id
	 * @return View
     */
	public function getShow($model, $id)
	{
		$this->check_acl($model,'show',$id);
		$show = $model->fullShow($id);
		if (is_null($show))
		{
			abort(404);
		}
		return $this->render($model->title(), $show);
	}

	/**
	 * post Update
	 *
	 * @param ModelConfiguration $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
     */
	public function postUpdate($model, $id)
	{
		$this->check_acl($model,'update',$id);
		$edit = $model->fullEdit($id);
		if (is_null($edit))
		{
			abort(404);
		}
		if ($edit instanceof FormInterface)
		{
			if ($validator = $edit->validate($model))
			{
				return Redirect::back()->withErrors($validator)->withInput()->with([
					'_redirectBack' => Input::get('_redirectBack'),
				]);
			}
			$edit->save($model);
		}
		return Redirect::to(Input::get('_redirectBack', $model->displayUrl()));
	}

	/**
	 * post destroy
	 *
	 * @param ModelConfiguration $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
     */
	public function postDestroy($model, $id)
	{
		$this->check_acl($model,'destroy',$id);
		$delete = $model->delete($id);
		if (is_null($delete))
		{
			abort(404);
		}
		$model->repository()->delete($id);
		return Redirect::back();
	}

	/**
	 * post Force Destroy
	 *
	 * @param ModelConfiguration $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForceDestroy($model, $id)
	{
		$this->check_acl($model,'destroy',$id);
		$delete = $model->forceDelete($id);
		if (is_null($delete))
		{
			abort(404);
		}
		$model->repository()->forceDelete($id);
		return Redirect::back();
	}

	/**
	 * post Restore
	 *
	 * @param ModelConfiguration $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
     */
	public function postRestore($model, $id)
	{
		$this->check_acl($model,'update', $id);
		$restore = $model->restore($id);
		if (is_null($restore))
		{
			abort(404);
		}
		$model->repository()->restore($id);
		return Redirect::back();
	}

	/**
	 * Render the view
	 *
	 * @param $title
	 * @param $content
	 * @return View
     */
	public function render($title, $content)
	{
		if ($content instanceof Renderable)
		{
			$content = $content->render();
		}
		return view(AdminTemplate::view('_layout.inner'), [
			'title'   => $title,
			'content' => $content,
		]);
	}

	/**
	 * get Language
	 *
	 * @return Response
     */
	public function getLang()
	{
		$lang = trans('admin::lang');
		if ($lang == 'admin::lang')
		{
			$lang = trans('admin::lang', [], 'messages', 'en');
		}

		$data = array(
			'locale' => App::getLocale(),
			'token'  => csrf_token(),
			'prefix' => config('admin.prefix'),
			'lang'   => $lang,
			'ckeditor_cfg' => config('admin.ckeditor')
		);
		
		$content = 'window.admin = '.json_encode($data) . ';';
		
		$response = new Response($content, 200, [
			'Content-Type' => 'text/javascript',
		]);

		return $this->cacheResponse($response);
	}

	/**
	 * Cache response
	 *
	 * @param Response $response
	 * @return Response
     */
	protected function cacheResponse(Response $response)
	{
		$response->setSharedMaxAge(31536000);
		$response->setMaxAge(31536000);
		$response->setExpires(new \DateTime('+1 year'));

		return $response;
	}

	/**
	 * get Wildcard
	 *
     */
	public function getWildcard()
	{
		abort(404);
	}

	/**
	 * reorder
	 *
	 * @param ModelConfiguration $model
     */
	public function reorder($model)
	{
		$data = Input::get('data');
		/** @var TreeRepository $repository */
		$repository = $model->display()->repository();
		$repository->reorder($data);
	}

	/**
	 * up
	 *
	 * @param ModelConfiguration $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
     */
    public function up($model, $id)
	{
		$instance = $model->repository()->find($id);
		$instance->moveUp();
		return back();
	}

	/**
	 * down
	 *
	 * @param ModelConfiguration $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
     */
	public function down($model, $id)
	{
		$instance = $model->repository()->find($id);
		$instance->moveDown();
		return back();
	}

    /**
     * @return array
     */
    function getAllImages()
    {
        return static::getAll();
    }

    /**
     * get All
     *
     * @return array
     */
    protected static function getAll()
    {
        $files = static::getAllFiles();
        $result = [];
        foreach ($files as $file)
        {
            $result[] = static::createImageObject($file);
        }
        return $result;
    }

    /**
     * get All files
     *
     * @return Finder
     */
    protected static function getAllFiles()
    {
        $path = public_path(config('admin.imagesUploadDirectory'));
        return Finder::create()->files()->in($path);
    }

    /**
     * create an image object
     *
     * @param SplFileInfo $file
     * @return stdClass
     */
    protected static function createImageObject(SplFileInfo $file)
    {
        $obj = new StdClass;
        $path = $file->getRelativePathname();
        $url = config('admin.imagesUploadDirectory') . '/' . $path;
        $url = asset($url);
        $obj->url = $url;
        $obj->thumbnail = $url;
        return $obj;
    }

    /**
     * post Upload
     *
     * @return mixed
     */
    public function postUpload()
    {
        return static::_postUpload();
    }

    /**
     * process upload
     *
     * @return string
     */
    protected static function _postUpload()
    {
        $path = config('admin.imagesUploadDirectory') . '/';
        $upload_dir = public_path($path);

        $allowedExtensions = [
            'bmp',
            'gif',
            'jpg',
            'jpeg',
            'png'
        ];

        $maxsize = 2000;
        $maxwidth = 9000;
        $maxheight = 8000;
        $minwidth = 10;
        $minheight = 10;

        $file = Input::file('upload');
        $errors = [];

        $extension = null;
        $width = 0;
        $height = 0;
        try
        {
            if (is_null($file))
            {
                $errors[] = trans('admin::lang.ckeditor.upload.error.common');
                throw new Exception;
            }
            $extension = $file->guessClientExtension();
            if ( ! in_array($extension, $allowedExtensions))
            {
                $errors[] = trans('admin::lang.ckeditor.upload.error.wrong_extension', ['file' => $file->getClientOriginalName()]);
                throw new Exception;
            }
            if ($file->getSize() > $maxsize * 1000)
            {
                $errors[] = trans('admin::lang.ckeditor.upload.error.filesize_limit', ['size' => $maxsize]);
            }
            list($width, $height) = getimagesize($file);
            if ($width > $maxwidth || $height > $maxheight)
            {
                $errors[] = trans('admin::lang.ckeditor.upload.error.imagesize_max_limit', [
                    'width'     => $width,
                    'height'    => $height,
                    'maxwidth'  => $maxwidth,
                    'maxheight' => $maxheight
                ]);
            }
            if ($width < $minwidth || $height < $minheight)
            {
                $errors[] = trans('admin::lang.ckeditor.upload.error.imagesize_min_limit', [
                    'width'     => $width,
                    'height'    => $height,
                    'minwidth'  => $minwidth,
                    'minheight' => $minheight
                ]);
            }
        } catch (Exception $e)
        {
        }

        if ( ! empty($errors))
        {
            return '<script>alert("' . implode('\\n', $errors) . '");</script>';
        }

        $finalFilename = $file->getClientOriginalName();
        $file = $file->move($upload_dir, $finalFilename);
        $CKEditorFuncNum = Input::get('CKEditorFuncNum');
        $url = asset($path . $finalFilename);
        $message = trans('admin::lang.ckeditor.upload.success', [
            'size'   => number_format($file->getSize() / 1024, 3, '.', ''),
            'width'  => $width,
            'height' => $height
        ]);
        $result = "window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '$url', '$message')";
        return '<script>' . $result . ';</script>';
    }

    /**
     * Upload image
     *
     * @return array
     */
    public function uploadImage()
    {
        $validator = Validator::make(Input::all(), static::uploadValidationRules());
        if ($validator->fails())
        {
            return ResponseFacade::make($validator->errors()->get('file'), 400);
        }
        $file = Input::file('file');
        $filename = md5(time() . $file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
        $path = config('admin.imagesUploadDirectory');
        $fullpath = public_path($path);
        $file->move($fullpath, $filename);
        $value = $path . '/' . $filename;
        return [
            'url'   => asset($value),
            'value' => $value,
        ];
    }

    /**
     * uploadValidationRules
     *
     * @return array
     */
    protected static function uploadValidationRules()
    {
        return [
            'file' => 'image',
        ];
    }

} 