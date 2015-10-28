<?php namespace SleepingOwl\Admin\FormItems;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class MultiSelect
 * @package SleepingOwl\Admin\FormItems
 */
class MultiSelect extends Select
{

	/**
	 * view to render
	 *
	 * @var string
     */
	protected $view = 'multiselect';

	/**
	 * value
	 *
	 * @return $this|array|mixed|NamedFormItem|static
     */
	public function value()
	{
		$value = parent::value();
		if ($value instanceof Collection  && $value->count() > 0)
		{
			$value = $value->lists($value->first()->getKeyName());
		}
		if ($value instanceof Collection)
		{
			$value = $value->toArray();
		}
		return $value;
	}

}
