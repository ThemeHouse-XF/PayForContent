<?php

/**
 *
 * @see XenResource_Model_Category
 */
class ThemeHouse_PayForContent_Extend_XenResource_Model_Category extends XFCP_ThemeHouse_PayForContent_Extend_XenResource_Model_Category
{

    /**
     *
     * @see XenResource_Model_Category::getCategoryPermCache()
     */
	public function standardizeViewingUserReferenceForCategory($categoryId, array &$viewingUser = null, array &$categoryPermissions = null)
	{
        parent::standardizeViewingUserReferenceForCategory($categoryId, $viewingUser, $categoryPermissions);

        $resourceModel = $this->getModelFromCache('XenResource_Model_Resource');

        $isPaidContent = ThemeHouse_PayForContent_Extend_XenResource_Model_Resource::isPaidContent();

        $additionalPermissions = XenForo_Application::get('options')->th_payForContent_resourcePermissions;

        if ($isPaidContent) {
            foreach ($additionalPermissions as $additionalPermission) {
                $categoryPermissions[$additionalPermission] = 1;
            }
        }
    }
}