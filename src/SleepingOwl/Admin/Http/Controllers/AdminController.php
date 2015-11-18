<?php

namespace SleepingOwl\Admin\Http\Controllers;

use AdminTemplate;
use App;
use Eloquent;
use Gate;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Input;
use Redirect;
use SleepingOwl\Admin\Interfaces\FormInterface;
use SleepingOwl\Admin\Model\ModelConfiguration;
use View;

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
        if (Gate::denies($action,$laravel_model)) {
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
     */
	public function getWildcard()
	{
		abort(404);
	}

} 