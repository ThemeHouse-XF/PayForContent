<?php

/**
 * Route prefix handler for paid content in the public system.
 */
class ThemeHouse_PayForContent_Route_Prefix_PaidContent implements XenForo_Route_Interface
{

    /**
     * Match a specific route for an already matched prefix.
     *
     * @see XenForo_Route_Interface::match()
     */
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerParam($routePath, $request, 'paid_content_id');
        return $router->getRouteMatch('ThemeHouse_PayForContent_ControllerPublic_PaidContent', $action, 'members');
    }

    /**
     * Method to build a link to the specified page/action with the provided
     * data and params.
     *
     * @see XenForo_Route_BuilderInterface
     */
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        $action = XenForo_Link::getPageNumberAsAction($action, $extraParams);
        return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 
            'paid_content_id', 'title');
    }
}