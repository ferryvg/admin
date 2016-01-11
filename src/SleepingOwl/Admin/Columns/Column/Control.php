<?php namespace SleepingOwl\Admin\Columns\Column;

use AdminTemplate;
use Illuminate\View\View;
use SleepingOwl\Admin\AssetManager\AssetManager;

class Control extends BaseColumn
{

	/**
	 * Column view
	 *
	 * @var string
	 */
	protected $view = 'control';

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

	        $this->label(trans('admin::lang.table.column.control'));

		$this->orderable(false);
	}

	/**
	 * Initialize column
	 *
	 */
	public function initialize()
	{
		parent::initialize();

		AssetManager::addScript('admin::default/js/bootbox.js');
		AssetManager::addScript('admin::default/js/columns/control.js');
	}

	/**
	 * Check if instance supports soft-deletes and trashed
	 *
	 * @return bool
	 */
	protected function trashed()
	{
		if (method_exists($this->instance, 'trashed'))
		{
			return $this->instance->trashed();
		}
		return false;
	}

    /**
     * Check if instance is showable
	 *
     * @return bool
     */
    protected function showable()
    {
        return $this->model()->showable(null,$this->instance);
    }

    /**
     * Get instance show url
	 *
     * @return string
     */
    protected function showUrl()
    {
        return $this->model()->showUrl($this->instance->getKey());
    }

	/**
	 * Check if instance editable
	 *
	 * @return bool
	 */
	protected function editable()
	{
		return ! $this->trashed() && $this->model()->editable(null,$this->instance);
	}

	/**
	 * Get instance edit url
	 *
	 * @return string
	 */
	protected function editUrl()
	{
		return $this->model()->editUrl($this->instance->getKey());
	}

	/**
	 * Check if instance is deletable
	 *
	 * @return bool
	 */
	protected function deletable()
	{
        return ! $this->trashed() && $this->model()->deletable(null,$this->instance);
	}

    /**
	 * Get instance delete url
	 *
	 * @return string
	 */
	protected function deleteUrl()
	{
		return $this->model()->deleteUrl($this->instance->getKey());
	}

	/**
	 * Check if instance is restorable
	 *
	 * @return bool
	 */
	protected function restorable()
	{
        return $this->trashed() && $this->model()->restorable(null,$this->instance);
	}

	/**
	 * Get instance restore url
	 *
	 * @return string
	 */
	protected function restoreUrl()
	{
		return $this->model()->restoreUrl($this->instance->getKey());
	}

    /**
     * Check if instance is force deletable
	 *
     * @return bool
     */
    protected function forceDeletable()
    {
        return ! is_null($this->model()->forceDeletable($this->instance));
    }

    /**
     * Get instance force delete url
	 *
     * @return string
     */
    public function forceDeleteUrl()
    {
        return $this->model()->forceDeleteUrl($this->instance->getKey());
    }

	/**
	 * Render the view
	 *
	 * @return View
	 */
	public function render()
	{
        $params = $this->getParams();
		return view(AdminTemplate::view('column.' . $this->view), $params);
	}

    /**
     * Get render parameters
	 *
     * @return array
     */
    protected function getParams()
    {
        $params = [
            'showUrl' => $this->showUrl(),
            'showable' => $this->showable(),
            'editable' => $this->editable(),
            'editUrl' => $this->editUrl(),
            'deletable' => $this->deletable(),
            'deleteUrl' => $this->deleteUrl(),
            'restorable' => $this->restorable(),
            'restoreUrl' => $this->restoreUrl(),
            'forceDeletable' => $this->forceDeletable(),
            'forceDeleteUrl' => $this->forceDeleteUrl(),
        ];

        return $params;
    }
}
