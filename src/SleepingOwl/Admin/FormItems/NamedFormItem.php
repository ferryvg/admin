<?php namespace SleepingOwl\Admin\FormItems;

use Dachnik\Models\Eloquent\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Input;

abstract class NamedFormItem extends BaseFormItem
{
	protected $path;
	protected $name;
	protected $attribute;
	protected $label;
	protected $defaultValue;
	protected $readonly;
	protected $save = true;

	function __construct($path, $label = null)
	{
		$this->label = $label;
		$parts = explode(".", $path);
		if (count($parts) > 1) {
			$this->path = $path;
			$this->name = $parts[0] . "[" . implode("][", array_slice($parts, 1)) . "]";
			$this->attribute = implode(".", array_slice(explode(".", $path), -1, 1));
		} else {
			$this->path = $path;
			$this->name = $path;
			$this->attribute = $path;
		}
	}

	public function path($path = null)
	{
		if (is_null($path))
		{
			return $this->path;
		}
		$this->path = $path;
		return $path;
	}

	public function attribute($attribute = null)
	{
		if (is_null($attribute))
		{
			return $this->attribute;
		}
		$this->attribute = $attribute;
		return $attribute;
	}

	public function name($name = null)
	{
		if (is_null($name))
		{
			return $this->name;
		}
		$this->name = $name;
		return $this;
	}

	public function label($label = null)
	{
		if (is_null($label))
		{
			return $this->label;
		}
		$this->label = $label;
		return $this;
	}

	public function getParams()
	{
		return parent::getParams() + [
				'name'      => $this->name(),
				'label'     => $this->label(),
				'readonly'  => $this->readonly(),
				'value'     => $this->value()
		];
	}

	public function defaultValue($defaultValue = null)
	{
		if (is_null($defaultValue))
		{
			return $this->defaultValue;
		}
		$this->defaultValue = $defaultValue;
		return $this;
	}

	public function readonly($readonly = null)
	{
		if (is_null($readonly))
		{
			return $this->readonly;
		}

		$this->readonly = $readonly;

		return $this;
	}

	public function value()
	{
		$instance = $this->instance();

		if ( ! is_null($value = old($this->path())))
		{
			return $value;
		}

		$input = Input::all();

		if (($value = array_get($input, $this->path())) !== null)
		{
			return $value;
		}

		if ( ! is_null($instance))
		{
			$exploded = explode('.', $this->path());
			$i = 1;
			$count = count($exploded);

			if (1 < $count) {
				$i++;

				foreach ($exploded as $relation) {
					if ($instance->{$relation} instanceof Model) {
						$instance = $instance->{$relation};
					} elseif ($count === $i) {
						$value = $instance->getAttribute($relation);
					} else {
						throw new \LogicException("Can not fetch value for field '{$this->path()}'. Probably relation definition is incorrect");
					}
				}
			} else {
				$value = $instance->getAttribute($this->attribute());
			}

			if (null !== $value) {
				return $value;
			}
		}
		return $this->defaultValue();
	}

	public function setSave($save = null)
	{
		if (is_null($save))
		{
			return $this->save;
		}
		$this->save = $save;
		return $this;
	}

	public function save()
	{
		if ($this->save){
			/** @var Category $instance */
			$instance = $this->instance();
			$attribute = $this->attribute();

			if (null === Input::get($this->path())) {
				$value = null;
			} else {
				$value = $this->value();
			}

			$nested = explode('.', $this->path());
			$count = count($nested);
			$i = 1;

			if (1 < $count) {
				$i++;
				$previousModel = $this->instance();

				/** @var \Eloquent $model */
				foreach ($nested as $model) {
					$nestedModel = null;

					if ($previousModel->{$model} instanceof  \Eloquent) {
						$nestedModel = &$previousModel->{$model};
					} elseif (method_exists($previousModel, $model)) {
						/** @var Relation $relation */
						$relation = $previousModel->{$model}();

						switch (get_class($relation)) {
							case BelongsTo::class:
								$nestedModel = $relation->getRelated();
								$relation->associate($nestedModel);
								break;
							case HasOne::class:
								$nestedModel = $relation->create();
								$instance->{$model} = $nestedModel;
								break;
						}
					}

					$previousModel = $nestedModel;

					if ($i === $count) {
						break;
					} elseif (null === $nestedModel)  {
						throw new \LogicException("Field «{$this->path()}» can't be mapped to relations of model " . get_class($this->instance()). ". Probably some dot delimeted segment is not a supported relation type");
					}
				}

				$instance = $previousModel;
			}

			$instance->{$attribute} = $value;
		}
	}

	public function required()
	{
		return $this->validationRule('required');
	}

	public function unique()
	{
		return $this->validationRule('_unique');
	}

	public function getValidationRules()
	{
		$rules = parent::getValidationRules();
		array_walk($rules, function (&$item)
		{
			if ($item == '_unique')
			{
				$table = $this->instance()->getTable();
				$item = 'unique:' . $table . ',' . $this->attribute();
				if ($this->instance()->exists())
				{
					$item .= ',' . $this->instance()->getKey();
				}
			}
		});
		return [
				$this->name() => $rules
		];
	}
}