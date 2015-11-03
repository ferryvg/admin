<?php

namespace SleepingOwl\Admin\Filter;

use Input;
use SleepingOwl\Admin\Interfaces\FilterInterface;

/**
 * Class FilterBase
 * @package SleepingOwl\Admin\Filter
 */
abstract class FilterBase implements FilterInterface
{

    /**
     * Filter name
     *
     * @var String
     */
    protected $name;

    /**
     * Filter alias
     *
     * @var
     */
    protected $alias;

    /**
     * Filter title
     *
     * @var
     */
    protected $title;

    /**
     * Filter value
     *
     * @var
     */
    protected $value;

    /**
     * @param $name
     */
    function __construct($name)
	{
		$this->name($name);
		$this->alias($name);
	}

    /**
     * @param null $name
     * @return $this|string
     */
    public function name($name = null)
	{
		if (is_null($name))
		{
			return $this->name;
		}
		$this->name = $name;
		return $this;
	}

    /**
     * @param null $alias
     * @return $this
     */
    public function alias($alias = null)
	{
		if (is_null($alias))
		{
			return $this->alias;
		}
		$this->alias = $alias;
		return $this;
	}

    /**
     * @param null $title
     * @return $this|mixed
     */
    public function title($title = null)
	{
		if (is_null($title))
		{
			if (is_callable($this->title))
			{
				return call_user_func($this->title, $this->value());
			}
			return $this->title;
		}
		$this->title = $title;
		return $this;
	}

    /**
     * @param null $value
     * @return $this
     */
    public function value($value = null)
	{
		if (is_null($value))
		{
			return $this->value;
		}
		$this->value = $value;
		return $this;
	}

    /**
     *
     */
    public function initialize()
	{
		$parameters = Input::all();
		$value = $this->value();
		if (is_null($value))
		{
			$value = array_get($parameters, $this->alias());
		}
		$this->value($value);
	}

    /**
     * @return bool
     */
    public function isActive()
	{
		return ! is_null($this->value());
	}

    /**
     * @param $query
     */
    public function apply($query)
	{
		$query->where($this->name(), $this->value());
	}

}