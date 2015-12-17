<?php namespace SleepingOwl\Admin\Columns\Column;

use AdminTemplate;
use Illuminate\View\View;

class Text extends NamedColumn
{

	/**
	 * @return View
	 */
	public function render()
	{
		$params = [
			'value'  => $this->getValue($this->instance, $this->name()),
			'append' => $this->append(),
		];
		return view(AdminTemplate::view('column.string'), $params);
	}

}