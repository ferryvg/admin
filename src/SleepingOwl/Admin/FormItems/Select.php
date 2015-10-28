<?php

namespace SleepingOwl\Admin\FormItems;

use Illuminate\Support\Collection;
use SleepingOwl\Admin\AssetManager\AssetManager;
use SleepingOwl\Admin\Repository\BaseRepository;

/**
 * Class Select
 * @package SleepingOwl\Admin\FormItems
 */
class Select extends NamedFormItem
{

	use ShowableFormItem { getParams as getParamsShowable; };

	/**
	 * view to render
	 *
	 * @var string
     */
	protected $view = 'select';

	/**
	 * Model
	 *
	 * @var
     */
	protected $model;

	/**
	 * Display
	 *
	 * @var string
     */
	protected $display = 'title';

	/**
	 * Select options
	 *
	 * @var array
     */
	protected $options = [];

	/**
	 * nullable?
	 *
	 * @var bool
     */
	protected $nullable = false;

	/**
	 * Initialize
     */
	public function initialize()
	{
		parent::initialize();

		AssetManager::addStyle('admin::default/css/formitems/select/chosen.css');
		AssetManager::addScript('admin::default/js/formitems/select/chosen.jquery.min.js');
		AssetManager::addScript('admin::default/js/formitems/select/init.js');
	}

	/**
	 * set/get model
	 *
	 * @param null $model
	 * @return $this
     */
	public function model($model = null)
	{
		if (is_null($model))
		{
			return $this->model;
		}
		$this->model = $model;
		return $this;
	}

	/**
	 * set/get display
	 *
	 * @param null $display
	 * @return $this|string
     */
	public function display($display = null)
	{
		if (is_null($display))
		{
			return $this->display;
		}
		$this->display = $display;
		return $this;
	}

	/**
	 * set/get options
	 *
	 * @param null $options
	 * @return $this|array|null
     */
	public function options($options = null)
	{
		if (is_null($options))
		{
			if ( ! is_null($this->model()) && ! is_null($this->display()))
			{
				$this->loadOptions();
			}
			$options = $this->options;
			asort($options);
			return $options;
		}
		$this->options = $options;
		return $this;
	}

	/**
	 * Load options
     */
	protected function loadOptions()
	{
		$repository = new BaseRepository($this->model());
		$key = $repository->model()->getKeyName();
		$options = $repository->query()->get()->lists($this->display(), $key);
		if ($options instanceof Collection)
		{
			$options = $options->all();
		}
		$this->options($options);
	}

	/**
	 * get view parameters
	 *
	 * @return array
     */
	public function getParams()
	{
		return $this->getParamsShowable() + [
			'options'  => $this->options(),
			'nullable' => $this->isNullable(),
		];
	}

	/**
	 * values
	 *
	 * @param $values
	 * @return $this|array|null|Select
     */
	public function enum($values)
	{
		return $this->options(array_combine($values, $values));
	}

	/**
	 * set/get nullable
	 *
	 * @param bool|true $nullable
	 * @return $this
     */
	public function nullable($nullable = true)
	{
		$this->nullable = $nullable;
		return $this;
	}

	/**
	 * is nullable?
	 *
	 * @return bool
     */
	public function isNullable()
	{
		return $this->nullable;
	}

}