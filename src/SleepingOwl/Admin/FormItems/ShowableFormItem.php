<?php

namespace SleepingOwl\Admin\FormItems;

use Admin;
use Request;

/**
 * Class ShowableFormItem
 * @package PrivacyDriver\SleepingOwl\FormItem
 */
trait ShowableFormItem
{

    /**
     * Get params for view
     *
     * @return mixed
     */
    public function getParams()
    {
        $disabled = $this->isFormItemDisabled();
        $params = parent::getParams();
        if ($disabled) {
            $params = $params + [ 'disabled' => 'disabled'];
        }
        return $params;
    }

    /**
     * Check if form itema has to be disabled
     *
     * @return bool
     */
    public function isFormItemDisabled() {
        if ($this->isShowURL(Request::url())){
            return true;
        }
        return false;
    }

    /**
     * Check if a URl is show URL
     *
     * @param $url
     * @return bool
     */
    private function isShowURL($url) {
        if ($this->instance->exists) {
            $match=false;
            foreach (Admin::modelAliases() as $alias ) {
                if ($url === route('admin.model.show',[$alias,$this->instance->id])) $match=true;
            }
            return $match;
        }
        return false;
    }
}