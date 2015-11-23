<?php

/**
 *
 * @see XenResource_ControllerPublic_Resource
 */
class ThemeHouse_PayForContent_Extend_XenResource_ControllerPublic_Resource extends XFCP_ThemeHouse_PayForContent_Extend_XenResource_ControllerPublic_Resource
{

    /**
     *
     * @see XenResource_ControllerPublic_Resource::_getResourceListFetchOptions()
     */
    protected function _getResourceListFetchOptions()
    {
        $fetchOptions = parent::_getResourceListFetchOptions();
        
        if (!empty($fetchOptions['th_join'])) {
            $fetchOptions['th_join'] |= ThemeHouse_PayForContent_Extend_XenResource_Model_Resource::FETCH_PAID_CONTENT;
        } else {
            $fetchOptions['th_join'] = ThemeHouse_PayForContent_Extend_XenResource_Model_Resource::FETCH_PAID_CONTENT;
        }
        
        $fetchOptions['paidContentUserId'] = XenForo_Visitor::getUserId();
        
        return $fetchOptions;
    }

    /**
     *
     * @see XenResource_ControllerPublic_Resource::_getResourceViewInfo()
     */
    protected function _getResourceViewInfo(array $fetchOptions = array())
    {
        if (!empty($fetchOptions['th_join'])) {
            $fetchOptions['th_join'] |= ThemeHouse_PayForContent_Extend_XenResource_Model_Resource::FETCH_PAID_CONTENT;
        } else {
            $fetchOptions['th_join'] = ThemeHouse_PayForContent_Extend_XenResource_Model_Resource::FETCH_PAID_CONTENT;
        }
        
        $fetchOptions['paidContentUserId'] = XenForo_Visitor::getUserId();
        
        return parent::_getResourceViewInfo($fetchOptions);
    }
}