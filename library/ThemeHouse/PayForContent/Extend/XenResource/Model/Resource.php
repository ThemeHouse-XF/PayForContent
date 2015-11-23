<?php

/**
 *
 * @see XenResource_Model_Resource
 */
class ThemeHouse_PayForContent_Extend_XenResource_Model_Resource extends XFCP_ThemeHouse_PayForContent_Extend_XenResource_Model_Resource
{
    
    // 0x01 reserved by ThemeHouse_ResourceEvents
    const FETCH_PAID_CONTENT = 0x02;

    protected static $_isPaidContent = false;

    /**
     *
     * @see XenResource_Model_Resource::getResourceById()
     */
    public function getResourceById($resourceId, array $fetchOptions = array())
    {
        $resource = parent::getResourceById($resourceId, $fetchOptions);
        
        if ($resource) {
            $this->_checkIsPaidContent($resource);
        }
        
        return $resource;
    }

    /**
     *
     * @see XenResource_Model_Resource::prepareResourceFetchOptions()
     */
    public function prepareResourceFetchOptions(array $fetchOptions)
    {
        $resourceFetchOptions = parent::prepareResourceFetchOptions($fetchOptions);
        
        $selectFields = $resourceFetchOptions['selectFields'];
        $joinTables = $resourceFetchOptions['joinTables'];
        $db = $this->_getDb();
        
        if (isset($fetchOptions['paidContentUserId'])) {
            if (!empty($fetchOptions['paidContentUserId'])) {
                $selectFields .= ',
					IF(paid_content_active.user_id IS NULL, 0, 1) AS is_paid';
                $joinTables .= '
					LEFT JOIN xf_paid_content_active_th AS paid_content_active
						ON (paid_content_active.content_id = resource.resource_id
                        AND paid_content_active.content_type = \'resource\'
						AND paid_content_active.user_id = ' .
                     $this->_getDb()->quote($fetchOptions['paidContentUserId']) . ')';
            } else {
                $selectFields .= ',
					0 AS is_paid';
            }
        }
        
        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

    /**
     *
     * @see XenResource_Model_Resource::prepareResource()
     */
    public function prepareResource(array $resource, array $category = null, array $viewingUser = null)
    {
        $this->_checkIsPaidContent($resource, $viewingUser);
        
        /* @var $paidContentModel ThemeHouse_PayForContent_Model_PaidContent */
        $paidContentModel = $this->getModelFromCache('ThemeHouse_PayForContent_Model_PaidContent');
        
        $resource = $paidContentModel->preparePaidContent($resource);
        
        $resource = parent::prepareResource($resource, $category, $viewingUser);
        
        if (!empty($resource['is_paid'])) {
            $resource['cost'] = '';
        } elseif (!empty($resource['costPhrase'])) {
            $resource['cost'] = $resource['costPhrase'];
        }
        
        $resource['canManagePaidContent'] = $this->canManagePaidContent($resource, $category);
        
        return $resource;
    }

    public function prepareResourceCustomFields(array $resource, array $category, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);
        
        $xenOptions = XenForo_Application::get('options');
        
        $customFields = @unserialize($resource['custom_resource_fields']);
        if (is_array($customFields)) {
            if (!self::isPaidContent()) {
                foreach ($xenOptions->th_payForContent_resourceFields as $paidFieldId) {
                    unset($customFields[$paidFieldId]);
                }
            }
            $resource['custom_resource_fields'] = serialize($customFields);
        }
        
        $resource = parent::prepareResourceCustomFields($resource, $category, $viewingUser);
        
        return $resource;
    }

    protected function _checkIsPaidContent(array &$resource, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);
        
        if (!empty($resource['paid_content_item_ids_th'])) {
            if (!isset($resource['paidContent'])) {
                $resource['paidContent'] = $this->_getPaidContentModel()->getPaidContentItems(
                    array(
                        'content_type' => 'resource',
                        'content_id' => $resource['resource_id'],
                        'start_date' => array(
                            '<=',
                            XenForo_Application::$time
                        ),
                        'end_date' => array(
                            '>=',
                            XenForo_Application::$time
                        )
                    ));
            }
            
            $resource['paidContent'] = $this->_getPaidContentModel()->preparePaidContentItems($resource['paidContent']);
            
            $paidContent = array();
            $paidContentUserGroupIds = array();
            foreach ($resource['paidContent'] as $paidContentId => $paidContentItem) {
                if ($paidContentItem['user_group_ids'] == '-1') {
                    $paidContent[$paidContentId] = $paidContentItem;
                    break;
                }
                $paidContentItemUserGroupIds = explode(',', $paidContentItem['user_group_ids']);
                foreach ($paidContentItemUserGroupIds as $paidContentItemUserGroupId) {
                    if (!in_array($paidContentItemUserGroupId, $paidContentUserGroupIds)) {
                        $paidContent[$paidContentId] = $paidContentItem;
                        continue;
                    }
                    $paidContentUserGroupIds[] = $paidContentItemUserGroupId;
                }
            }
            $resource['paidContent'] = $paidContent;
            
            if (empty($paidContent)) {
                // do nothing
            } elseif (count($paidContent) == 1) {
                $_paidContent = reset($paidContent);
                $userModel = $this->_getUserModel();
                if ($_paidContent['user_group_ids'] == '-1' ||
                     $userModel->isMemberOfUserGroup($viewingUser, explode(',', $_paidContent['user_group_ids']))) {
                    $resource = array_merge($resource, $_paidContent);
                }
            } else {
                $userModel = $this->_getUserModel();
                foreach ($paidContent as $paidContentId => $_paidContent) {
                    if ($_paidContent['user_group_ids'] == '-1' ||
                         $userModel->isMemberOfUserGroup($viewingUser, explode(',', $_paidContent['user_group_ids']))) {
                        $resource = array_merge($resource, $_paidContent);
                        break;
                    }
                }
            }
        }
        
        if (!empty($paidContent) && $resource['cost_amount'] == '0.00') {
            $resource['is_paid'] = 1;
            self::$_isPaidContent = true;
        } elseif (!empty($viewingUser['paid_content_ids_th']) && !empty($resource['resource_id'])) {
            $paidContentIds = unserialize($viewingUser['paid_content_ids_th']);
            
            if (!empty($paidContentIds['resource'][$resource['resource_id']])) {
                $resource['is_paid'] = 1;
                self::$_isPaidContent = true;
            } else {
                $resource['is_paid'] = 0;
                self::$_isPaidContent = false;
            }
        }
    }

    public static function isPaidContent()
    {
        return self::$_isPaidContent;
    }

    /**
     * Determines if a user can feature/unfeature a given resource.
     * Does not check viewing perms.
     *
     * @param array $resource
     * @param array $category
     * @param string $errorPhraseKey
     * @param array $viewingUser
     * @param array|null $categoryPermissions
     *
     * @return boolean
     */
    public function canManagePaidContent(array $resource, array $category, &$errorPhraseKey = '', 
        array $viewingUser = null, array $categoryPermissions = null)
    {
        $this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);
        
        return ($viewingUser['user_id'] &&
             XenForo_Permission::hasContentPermission($categoryPermissions, 'managePaidContent'));
    }

    /**
     *
     * @return ThemeHouse_PayForContent_Model_PaidContent
     */
    protected function _getPaidContentModel()
    {
        return $this->getModelFromCache('ThemeHouse_PayForContent_Model_PaidContent');
    }

    /**
     *
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }
}