<?php

namespace SleepingOwl\Admin\Form;

use AdminTemplate;
use Config;
use Eloquent;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\View\View;
use Input;
use SleepingOwl\Admin\Admin;
use SleepingOwl\Admin\Interfaces\DisplayInterface;
use SleepingOwl\Admin\Interfaces\FormInterface;
use SleepingOwl\Admin\Interfaces\FormItemInterface;
use SleepingOwl\Admin\Model\ModelConfiguration;
use SleepingOwl\Admin\Repository\BaseRepository;
use URL;
use Validator;

/**
 * Class FormDefault
 * @package SleepingOwl\Admin\Form
 */
class FormDefault implements Renderable, DisplayInterface, FormInterface
{

	/**
	 * View to render
     *
	 * @var string
	 */
	protected $view = 'default';

	/**
	 * Form related class
     *
	 * @var string
	 */
	protected $class;

	/**
	 * Form related repository
     *
	 * @var BaseRepository
	 */
	protected $repository;

	/**
	 * Form items
     *
	 * @var FormItemInterface[]
	 */
	protected $items = [];

	/**
	 * Form action url
     *
	 * @var string
	 */

	protected $action;
	/**
	 * Form related model instance
     *
	 * @var mixed
	 */
	protected $instance;

	/**
	 * Currently loaded model id
     *
	 * @var int
	 */
	protected $id;

	/**
	 * Is form already initialized?
     *
	 * @var bool
	 */
	protected $initialized = false;

	/**
	 * Initialize form
	 */
	public function initialize()
	{
		if ($this->initialized) return;

		$this->initialized = true;
		$this->repository = new BaseRepository($this->class);
		$this->instance(app($this->class));
		$items = $this->items();
		array_walk_recursive($items, function ($item)
		{
			if ($item instanceof FormItemInterface)
			{
				$item->initialize();
			}
		});
	}

	/**
	 * Set form action
	 * @param string $action
	 */
	public function setAction($action)
	{
		if (is_null($this->action))
		{
			$this->action = $action;
		}
	}

	/**
	 * Set form class
     *
	 * @param string $class
	 */
	public function setClass($class)
	{
		if (is_null($this->class))
		{
			$this->class = $class;
		}
	}

	/**
	 * Get or set form items
     *
	 * @param FormInterface[]|null $items
	 * @return $this|FormInterface[]
	 */
	public function items($items = null)
	{
		if (is_null($items))
		{
			return $this->items;
		}
		$this->items = $items;
		return $this;
	}

	/**
	 * Get or set form related model instance
     *
	 * @param mixed|null $instance
	 * @return $this|mixed|Eloquent
	 */
	public function instance($instance = null)
	{
		if (is_null($instance))
		{
			return $this->instance;
		}
		$this->instance = $instance;
		$items = $this->items();
		array_walk_recursive($items, function ($item) use ($instance)
		{
			if ($item instanceof FormItemInterface)
			{
				$item->setInstance($instance);
			}
		});
		return $this;
	}

	/**
	 * Set currently loaded model id
     *
	 * @param int $id
	 */
	public function setId($id)
	{
		if (is_null($this->id))
		{
			$this->id = $id;
			$this->instance($this->repository->findOrFail($id));
		}
	}

	/**
	 * Get related form model configuration
     *
	 * @return ModelConfiguration
	 */
	public function model()
	{
		return Admin::model($this->class);
	}


    /**
     * Save instance
     *
     * @param mixed $model
     * @return null
     */
    public function save($model)
	{
		if ($this->model() != $model)
		{
			return null;
		}
		$items = $this->items();

		array_walk_recursive($items, function ($item)
		{
			if ($item instanceof FormItemInterface)
			{
				$item->save();
			}
		});

		$this->saveBelongsToRelations();

		$this->instance()->save();

		$this->saveHasOneRelations();
	}


	/**
	 * Validate data, returns null on success
     *
	 * @param mixed $model
	 * @return Validator|null
	 */
	public function validate($model)
	{
		if ($this->model() != $model)
		{
			return null;
		}

		$rules = [];
		$items = $this->items();
		array_walk_recursive($items, function ($item) use (&$rules)
		{
			if ($item instanceof FormItemInterface)
			{
				$rules += $item->getValidationRules();
			}
		});
		$data = Input::all();
		$verifier = app('validation.presence');
		$verifier->setConnection($this->instance()->getConnectionName());
		$validator = Validator::make($data, $rules);
		$validator->setPresenceVerifier($verifier);
		if ($validator->fails())
		{
			return $validator;
		}
		return null;
	}

	/**
	 * Get redirect back URL
     *
	 * @return array|string
	 * @throws ModelNotFoundException
     */
	protected function obtainRedirectBack(){
		$redirect_back = Input::input('_redirectBack',null);
		if ($redirect_back != null) {
			return $this->beSureIsAbsoluteURL($redirect_back);
		} else {
			return $this->display_url($this->class);
		}

	}

	protected function saveBelongsToRelations()
	{
		$relations = $this->instance()->getRelations();
		$model = $this->instance();

		foreach ($relations as $name => $relation) {
			if ($model->{$name}() instanceof BelongsTo) {
				$relation->save();
				$model->{$name}()->associate($relation);
			}
		}
	}

	protected function saveHasOneRelations()
	{
		$relations = $this->instance()->getRelations();
		$model = $this->instance();

		foreach ($relations as $name => $relation) {
			if ($model->{$name}() instanceof HasOneOrMany) {
				if (is_array($relation)) {
					$model->{$name}()->saveMany($relation);
				} else {
					$model->{$name}()->save($relation);
				}
			}
		}
	}

    /**
     * Be sure is absolute URL
     *
     * @param $url
     * @return string
     */
    protected function beSureIsAbsoluteURL($url) {
		if (starts_with($url,'http://') || starts_with($url,'https://')) {
			return $url;
		} else {
			if (starts_with($url,'/')) {
				return URL::to('/') . $url;
			} else {
				return URL::to('/') . '/'. $url;
			}
		}
	}

	/**
	 * Get display URL (list of item models)
     *
	 * @param $model
	 * @return string
	 * @throws ModelNotFoundException
     */
	protected function display_url($model) {
		if (array_key_exists($model,Admin::modelAliases())) {
			$alias = Admin::modelAliases()[$model];
			return URL::to('/') . '/' .  Config::get('admin.prefix') . '/' . $alias ;
		} else {
			throw new ModelNotFoundException;
		}
	}

	/**
     * Render the view
     *
	 * @return View
	 */
	public function render()
	{
		$params = $this->getParams();
		return view(AdminTemplate::view('form.' . $this->view), $params);
	}

	/**
     * Convert to string
     *
	 * @return string
	 */
	function __toString()
	{
		return (gettype($this->render()) == 'string')? $this->render(): (string)$this->render();
	}

	/**
	 * Params for view to render
	 *
	 * @return array
	 */
	protected function getParams()
	{
		$params = [
			'items' => $this->items(),
			'instance' => $this->instance(),
			'action' => $this->action,
			'backUrl' => $this->obtainRedirectBack(),
		];
		return $params;
	}

}