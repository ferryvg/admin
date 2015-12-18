<?php namespace SleepingOwl\Admin\Columns\Column;

use AdminTemplate;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\View\View;
use JsonSerializable;

class ColumnHeader implements Renderable,JsonSerializable
{

	/**
	 * Header title
	 * @var string
	 */
	protected $title;
	/**
	 * Is column orderable?
	 * @var bool
	 */
	protected $orderable = true;

	/**
	 * Get or set title
	 * @param string|null $title
	 * @return $this|string
	 */
	public function title($title = null)
	{
		if (is_null($title))
		{
			return $this->title;
		}
		$this->title = $title;
		return $this;
	}

	/**
	 * Get or set column orderable feature
	 * @param bool|null $orderable
	 * @return $this|bool
	 */
	public function orderable($orderable = null)
	{
		if (is_null($orderable))
		{
			return $this->orderable;
		}
		$this->orderable = $orderable;
		return $this;
	}

	/**
	 * @return View
	 */
	public function render()
	{
		$params = [
			'title'     => $this->title(),
			'orderable' => $this->orderable(),
		];
		return view(AdminTemplate::view('column.header'), $params);
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return (gettype($this->render()) == 'string')? $this->render(): (string)$this->render();
	}

	/**
	 * (PHP 5 &gt;= 5.4.0)<br/>
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 */
	function jsonSerialize()
	{
		return [
			'title' => $this->title(),
            'orderable' => $this->orderable()
        ];
	}
}