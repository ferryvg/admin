<?php namespace SleepingOwl\Admin\Http\Controllers;

use AdminTemplate;
use App;
use Gate;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Input;
use Redirect;
use SleepingOwl\Admin\Interfaces\FormInterface;
use View;

/**
 * Class AdminController
 * @package SleepingOwl\Admin\Http\Controllers
 */
class AdminController extends Controller
{

	/**
	 * @param $model
	 * @param $action
     */
	protected function check_acl($model,$action){
		$model_undercase_name = strtolower(class_basename($model->getClass()));
		if ($model->aclsAreActive() && Gate::denies($model_undercase_name . '-' . $action)) {
			View::share('permission', $model_undercase_name.'-' . $action);
			abort(403);
		}
	}

	/**
	 * @param $model
	 * @return View
     */
	public function getDisplay($model)
	{
		$this->check_acl($model,'retrieve');
		return $this->render($model->title(), $model->display());
	}

	/**
	 * @param $model
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
	 * @param $model
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
	 * @param $model
	 * @param $id
	 * @return View
     */
	public function getEdit($model, $id)
	{
		$this->check_acl($model,'edit');
		$edit = $model->fullEdit($id);
		if (is_null($edit))
		{
			abort(404);
		}
		return $this->render($model->title(), $edit);
	}

	/**
	 * @param $model
	 * @param $id
	 * @return View
     */
	public function getShow($model, $id)
	{
		$this->check_acl($model,'show');
		$show = $model->fullShow($id);
		if (is_null($show))
		{
			abort(404);
		}
		return $this->render($model->title(), $show);
	}

	/**
	 * @param $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
     */
	public function postUpdate($model, $id)
	{
		$this->check_acl($model,'update');
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
	 * @param $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
     */
	public function postDestroy($model, $id)
	{
		$this->check_acl($model,'destroy');
		$delete = $model->delete($id);
		if (is_null($delete))
		{
			abort(404);
		}
		$model->repository()->delete($id);
		return Redirect::back();
	}

	/**
	 * @param $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForceDestroy($model, $id)
	{
		$this->check_acl($model,'destroy');
		$delete = $model->forceDelete($id);
		if (is_null($delete))
		{
			abort(404);
		}
		$model->repository()->forceDelete($id);
		return Redirect::back();
	}

	/**
	 * @param $model
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
     */
	public function postRestore($model, $id)
	{
		$this->check_acl($model,'update');
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