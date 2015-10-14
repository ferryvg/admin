<?php

namespace SleepingOwl\Admin\Display;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Illuminate\Contracts\Support\Renderable;
use SleepingOwl\Admin\Interfaces\DisplayInterface;
use AdminTemplate;

/**
 * Class DisplayMultiDisplay
 * @package SleepingOwl\Admin\Display
 */
class DisplayMultiDisplay implements Renderable, DisplayInterface
{

    /**
     * class
     * @var
     */
    protected $class;

    /**
     * View to render
     * @var string
     */
    protected $view = 'multi_display';

    /**
     * Displays to render
     * @var
     */
    protected $displays = array();

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $params = $this->getParams();
        return view(AdminTemplate::view('display.' . $this->view), $params);
    }

    /**
     * Initialize display
     */
    public function initialize()
    {
        foreach ($this->displays as $display)
        {
            if ($display instanceof DisplayInterface)
            {
                $display->initialize();
            }
        }
    }

    /**
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
     * @return array
     */
    protected function getParams()
    {
        $params = [
            'displays' => $this->displays(),
        ];
        return $params;
    }

    /**
     * Set or get displays
     * @param null $displays
     * @return mixed
     */
    function displays($displays = null)
    {
        if ($displays == null) {
            return $this->displays;
        }
        if ( !is_array($displays) ) throw new InvalidArgumentException("Displays parameter have to be an array!");
        $this->displays = $displays;
        return $this;
    }
}