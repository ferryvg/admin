<?php

namespace SleepingOwl\Admin\FormItems;

/**
 * Class Text
 * @package SleepingOwl\Admin\FormItems
 */
class Text extends NamedFormItem
{

	use ShowableFormItem;

    /**
     * view to render
     *
     * @var string
     */
    protected $view = 'text';

}