<?php

class ThemeHouse_PayForContent_ControllerPublic_PaidContent extends XenForo_ControllerPublic_Abstract
{

    public function actionIndex()
    {
        $paidContentId = $this->_input->filterSingle('paid_content_id', XenForo_Input::UINT);
        if ($paidContentId) {
            return $this->responseReroute(__CLASS__, 'edit');
        }
        
        $contentId = $this->_input->filterSingle('content_id', XenForo_Input::STRING);
        $contentType = $this->_input->filterSingle('content_type', XenForo_Input::STRING);
        
        $paidContentModel = $this->_getPaidContentModel();
        
        $paidContentHandler = $paidContentModel->getPaidContentHandler($contentType);
        
        if (!$paidContentHandler) {
            return $this->responseNoPermission();
        }
        
        $this->_routeMatch->setSections($paidContentHandler->getMajorSection());
        
        $content = $paidContentHandler->getContentById($contentId);
        
        if (!$content || !$paidContentHandler->canManagePaidContent($content)) {
            return $this->responseNoPermission();
        }
        
        $paidContentItems = $paidContentModel->getPaidContentItems(
            array(
                'content_type' => $contentType,
                'content_id' => $contentId
            ));
        
        $paidContentItems = $paidContentModel->preparePaidContentItems($paidContentItems);
        
        /* @var $userGroupModel XenForo_Model_UserGroup */
        $userGroupModel = $this->getModelFromCache('XenForo_Model_UserGroup');
        
        $userGroups = $userGroupModel->getAllUserGroups();
        
        $viewParams = array(
            'title' => $paidContentHandler->getTitleForContent($content),
            'paidContentItems' => $paidContentItems,
            'userGroups' => $userGroups,
            
            'contentType' => $contentType,
            'contentId' => $contentId,
            
            'breadCrumbs' => $paidContentHandler->getBreadcrumbsForContent($content)
        );
        
        return $this->responseView('ThemeHouse_PayForContent_ViewPublic_PaidContent',
            'th_paid_content_payforcontent', $viewParams);
    }

    /**
     * Helper to get the paid content add/edit form controller response.
     *
     * @param array $paidContentItem
     * @param ThemeHouse_PayForContent_PaidContentHandler_Abstract $paidContentHandler
     * @param array $content
     *
     * @return XenForo_ControllerResponse_View
     */
    protected function _getPaidContentItemAddEditResponse(array $paidContentItem, 
        ThemeHouse_PayForContent_PaidContentHandler_Abstract $paidContentHandler, array $content)
    {
        $this->_routeMatch->setSections($paidContentHandler->getMajorSection());
        
        $paidContentItem = $this->_getPaidContentModel()->preparePaidContent($paidContentItem);
        
        $userGroups = $this->_getUserGroupModel()->getAllUserGroups();
        
        if (!empty($paidContentItem['paid_content_id'])) {
            $selUserGroupIds = explode(',', $paidContentItem['user_group_ids']);
            if (in_array(-1, $selUserGroupIds)) {
                $allUserGroups = true;
                $selUserGroupIds = array_keys($userGroups);
            } else {
                $allUserGroups = false;
            }
        } else {
            $allUserGroups = true;
            $selUserGroupIds = array_keys($userGroups);
        }
        
        if (empty($paidContentItem['start_date'])) {
            $paidContentItem['startDate'] = XenForo_Locale::date(XenForo_Application::$time, 'picker');
        }
        
        if (empty($paidContentItem['end_date'])) {
            $startTime = !empty($paidContentItem['start_date']) ? $paidContentItem['start_date'] : XenForo_Application::$time;
            $paidContentItem['endDate'] = XenForo_Locale::date($startTime + 7 * 24 * 60 * 60, 'picker');
        }
        
        $viewParams = array(
            'paidContentItem' => $paidContentItem,
            
            'allUserGroups' => $allUserGroups,
            'selUserGroupIds' => $selUserGroupIds,
            
            'userGroups' => $userGroups,
            
            'breadCrumbs' => $paidContentHandler->getBreadcrumbsForContent($content)
        );
        
        return $this->responseView('ThemeHouse_PayForContent_ViewPublic_PaidContentItem_Edit',
            'th_paid_content_item_edit_payforcontent', $viewParams);
    }

    /**
     * Displays a form to add a new paid content.
     *
     * @return XenForo_ControllerResponse_View
     */
    public function actionAdd()
    {
        $contentId = $this->_input->filterSingle('content_id', XenForo_Input::STRING);
        $contentType = $this->_input->filterSingle('content_type', XenForo_Input::STRING);
        
        $paidContentModel = $this->_getPaidContentModel();
        
        $paidContentHandler = $paidContentModel->getPaidContentHandler($contentType);
        
        if (!$paidContentHandler) {
            return $this->responseNoPermission();
        }
        
        $content = $paidContentHandler->getContentById($contentId);
        
        if (!$content || !$paidContentHandler->canManagePaidContent($content)) {
            return $this->responseNoPermission();
        }
        
        $paidContentItem = $this->_getPaidContentModel()->getDefaultPaidContentItem();
        
        $paidContentItem['content_id'] = $contentId;
        $paidContentItem['content_type'] = $contentType;
        
        return $this->_getPaidContentItemAddEditResponse($paidContentItem, $paidContentHandler, $content);
    }

    /**
     * Displays a form to edit an existing paid content.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionEdit()
    {
        $paidContentId = $this->_input->filterSingle('paid_content_id', XenForo_Input::STRING);
        
        if (!$paidContentId) {
            return $this->responseReroute(__CLASS__, 'add');
        }
        
        $paidContentModel = $this->_getPaidContentModel();
        
        $paidContentItem = $this->_getPaidContentItemOrError($paidContentId);
        
        $paidContentHandler = $paidContentModel->getPaidContentHandler($paidContentItem['content_type']);
        
        if (!$paidContentHandler) {
            return $this->responseNoPermission();
        }
        
        $content = $paidContentHandler->getContentById($paidContentItem['content_id']);
        
        if (!$content || !$paidContentHandler->canManagePaidContent($content)) {
            return $this->responseNoPermission();
        }
        
        $paidContentItem['masterTitle'] = $paidContentModel->getPaidContentMasterTitlePhraseValue($paidContentId);
        
        $handler = $paidContentModel->getPaidContentHandler($paidContentItem['content_type']);
        
        $paidContentItem['title'] = $handler->getContentTitle($paidContentItem['content_id']);
        
        return $this->_getPaidContentItemAddEditResponse($paidContentItem, $paidContentHandler, $content);
    }

    /**
     * Inserts a new paid content or updates an existing one.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionSave()
    {
        $this->_assertPostOnly();
        
        $paidContentId = $this->_input->filterSingle('paid_content_id', XenForo_Input::STRING);
        
        $input = $this->_input->filter(
            array(
                'content_type' => XenForo_Input::STRING,
                'content_id' => XenForo_Input::UINT,
                'cost_amount' => XenForo_Input::STRING,
                'cost_currency' => XenForo_Input::STRING,
                'purchase_limit_on' => XenForo_Input::UINT,
                'purchase_limit' => XenForo_Input::UINT,
                'title' => XenForo_Input::STRING,
                'user_group_type' => XenForo_Input::STRING,
                'user_group_ids' => array(
                    XenForo_Input::UINT,
                    'array' => true
                ),
                'priority' => XenForo_Input::UINT,
                'start_date' => XenForo_Input::DATE_TIME,
                'end_date' => array(
                    XenForo_Input::DATE_TIME,
                    'dayEnd' => true
                ),
                'paypal_email' => XenForo_Input::STRING
            ));
        
        $paidContentHandler = $this->_getPaidContentModel()->getPaidContentHandler($input['content_type']);
        
        if (!$paidContentHandler) {
            return $this->responseNoPermission();
        }
        
        $content = $paidContentHandler->getContentById($input['content_id']);
        
        if (!$content || !$paidContentHandler->canManagePaidContent($content)) {
            return $this->responseNoPermission();
        }
        
        if ($input['user_group_type'] == 'all') {
            $input['user_group_ids'] = array(
                -1
            );
        }
        unset($input['user_group_type']);
        
        if ($input['purchase_limit_on'] && !$input['purchase_limit']) {
            $input['can_purchase'] = false;
        } else {
            $input['can_purchase'] = true;
        }
        unset($input['purchase_limit_on']);
        
        $writer = XenForo_DataWriter::create('ThemeHouse_PayForContent_DataWriter_PaidContent');
        if ($paidContentId) {
            $writer->setExistingData($paidContentId);
        }
        $writer->setExtraData(ThemeHouse_PayForContent_DataWriter_PaidContent::DATA_TITLE, $input['title']);
        unset($input['title']);
        $writer->bulkSet($input);
        $writer->save();
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
            XenForo_Link::buildPublicLink('paid-content', null, 
                array(
                    'content_id' => $input['content_id'],
                    'content_type' => $input['content_type']
                )));
    }

    public function actionPurchase()
    {
        $visitor = XenForo_Visitor::getInstance();
        $xenOptions = XenForo_Application::get('options');
        $paidContentId = $this->_input->filterSingle('paid_content_id', XenForo_Input::UINT);
        
        $paidContentModel = $this->_getPaidContentModel();
        
        $paidContentItem = $paidContentModel->preparePaidContent($this->_getPaidContentItemOrError($paidContentId));
        
        $paidContentHandler = $paidContentModel->getPaidContentHandler($paidContentItem['content_type']);
        
        if (!$paidContentHandler) {
            return $this->responseNoPermission();
        }
        
        $content = $paidContentHandler->getContentById($paidContentItem['content_id']);
        
        if (!$content) {
            return $this->responseNoPermission();
        }
        
        if (!$visitor['user_id'] &&
             ($paidContentItem['user_group_ids'] == -1 ||
             in_array(XenForo_Model_User::$defaultRegisteredGroupId, explode(',', $paidContentItem['user_group_ids'])))) {
            return $this->responseReroute('XenForo_ControllerPublic_Register', 'index');
        }
        
        if (!$paidContentModel->canPurchasePaidContentItem($paidContentItem)) {
            return $this->responseNoPermission();
        }
        
        if ($this->_checkCsrfFromToken(null, false)) {
            $paths = XenForo_Application::getRequestPaths(new Zend_Controller_Request_Http());
            $baseUrl = $paths['fullBasePath'];
            
            $params = array(
                'cmd' => '_xclick',
                'amount' => $paidContentItem['cost_amount'],
                'business' => $paidContentItem['paypal_email'] ? $paidContentItem['paypal_email'] : $xenOptions->payPalPrimaryAccount,
                'currency_code' => $paidContentItem['currency'],
                'item_name' => $paidContentHandler->getTitleForContent($content),
                'quantity' => 1,
                'no_note' => 1,
                'custom' => implode(',', 
                    array(
                        $visitor->user_id,
                        $paidContentItem['paid_content_id'],
                        'token',
                        $visitor->csrf_token_page
                    )),
                'charset' => 'utf-8',
                'email' => $visitor->email,
                'return' => XenForo_Link::buildPublicLink('full:paid-content/purchase-success'),
                'cancel_return' => XenForo_Link::buildPublicLink('full:index'),
                'notify_url' => $baseUrl . 'paid_content_callback.php'
            );
            
            $payPalUrl = $this->_input->filterSingle('payPalUrl', XenForo_Input::STRING);
            if (!$payPalUrl) {
                $payPalUrl = 'https://www.paypal.com/cgi-bin/websrc';
            }
            
            // Redirect to paypal
            $url = $payPalUrl . '?' . XenForo_Link::buildQueryString($params);
            
            header('Location: ' . $url);
            exit();
        }
        
        $viewParams = array(
            'title' => $paidContentHandler->getTitleForContent($content),
            'breadCrumbs' => $paidContentHandler->getBreadcrumbsForContent($content),
            'paidContentItem' => $paidContentItem
        );
        
        return $this->responseView('ThemeHouse_PayForContent_ViewPublic_PaidContent_PurchaseConfirm',
            'th_purchase_confirm_payforcontent', $viewParams);
    }

    public function actionPurchaseSuccess()
    {
        return $this->responseView('ThemeHouse_PayForContent_ViewPublic_PaidContent_Success',
            'th_paid_content_purchased_payforcontent');
    }

    /**
     * Gets a valid paid content or throws an exception.
     *
     * @param string $paidContentId
     *
     * @return array
     */
    protected function _getPaidContentItemOrError($paidContentId)
    {
        $paidContentItem = $this->_getPaidContentModel()->getPaidContentItemById($paidContentId);
        if (!$paidContentItem) {
            throw $this->responseException(
                $this->responseError(new XenForo_Phrase('th_requested_paid_content_item_not_found_payforcontent'),
                    404));
        }
        
        return $paidContentItem;
    }

    /**
     * Get the paid content model.
     *
     * @return ThemeHouse_PayForContent_Model_PaidContent
     */
    protected function _getPaidContentModel()
    {
        return $this->getModelFromCache('ThemeHouse_PayForContent_Model_PaidContent');
    }

    /**
     * Get the user group model.
     *
     * @return XenForo_Model_UserGroup
     */
    protected function _getUserGroupModel()
    {
        return $this->getModelFromCache('XenForo_Model_UserGroup');
    }
}