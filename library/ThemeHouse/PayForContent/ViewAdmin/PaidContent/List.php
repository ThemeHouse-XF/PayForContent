<?php

class ThemeHouse_PayForContent_ViewAdmin_PaidContent_List extends XenForo_ViewAdmin_Base
{

    public function renderHtml()
    {
        $this->_params['renderedOptions'] = XenForo_ViewAdmin_Helper_Option::renderPreparedOptionsHtml($this,
            $this->_params['options'], $this->_params['canEditOptionDefinition']);
    }
}