<?php

namespace SleepingOwl\Admin\Http\Controllers;

use AdminTemplate;
use App;
use Gate;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Input;
use Redirect;
use SleepingOwl\Admin\Interfaces\FormInterface;
use SleepingOwl\Admin\Model\ModelConfiguration;
use View;

/**
 * Class AdminController
 * @package SleepingOwl\Admin\Http\Controllers
 */
class AdminController extends Controller
{
	/**
	 * Check acl
	 * @param ModelConfiguration $model
	 * @param $action
	 * @param null $id
     */
	protected function check_acl($model, $action, $id=null){
		if ( ! $model->aclsAreActive()) return;
		$model_undercase_name = strtolower(class_basename($model->getClass()));
		if (Gate::denies($model_undercase_name . '-' . $action)) {
			View::share('permission', $model_undercase_name.'-' . $action);
			abort(403);
		}

		if ($id != null) {
			$class = $model->getClass();
			if ($this->model_uses_soft_deletes($class)) {
				$laravel_model = $class::withTrashed()->findOrFail($id);
			} else {
				$laravel_model = $class::findOrFail($id);
			}
			if (Gate::denies($action,$laravel_model)) {
				View::share('permission', "You don't have permissions to acces object " . $model_undercase_name . ' with id: ' . $id);
				abort(403);
			}
		}
	}


	/**
	 * Check if model uses trait soft Deleted
	 * @param $model
	 * @return bool
     */
	private function model_uses_soft_deletes($model){
		if (method_exists($model,'forceDelete') && method_exists($model,'restore') && method_exists($model,'trashed')) {
			return true;
		}
		return false;
	}

	/**
	 * @param ModelConfiguration $model
	 * @return View
     */
	public function getDisplay($model)
	{
		$this->check_acl($model,'retrieve');
		return $this->render($model->title(), $model->display());
	}

	/**
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
	 *
     */
	public function getWildcard()
	{
		abort(404);
	}

} 