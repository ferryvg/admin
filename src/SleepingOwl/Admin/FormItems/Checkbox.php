<?php

namespace SleepingOwl\Admin\FormItems;

use Input;

/**
 * Class Checkbox
 * @package SleepingOwl\Admin\FormItems
 */
class Checkbox extends NamedFormItem
{

    use ShowableFormItem;

    /**
     * view to render
     *
     * @var string
     */
    protected $view = 'checkbox';

    /**
     * Save checkbox
     */
    public function save()
	{
		$name = $this->name();
		if ( ! Input::has($name))
		{
			Input::merge([$name => 0]);
		}
		parent::save();
	}


}