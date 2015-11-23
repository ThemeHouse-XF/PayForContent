<?php

/**
 * Admin controller for handling actions on paid content.
 */
class ThemeHouse_PayForContent_ControllerAdmin_PaidContent extends XenForo_ControllerAdmin_Abstract
{

    /**
     * Shows a list of paid content.
     *
     * @return XenForo_ControllerResponse_View
     */
    public function actionIndex()
    {
        $paidContentItemModel = $this->_getPaidContentModel();
        $optionModel = XenForo_Model::create('XenForo_Model_Option');
        
        $paidContentItems = $paidContentItemModel->getPaidContentItems();
        $paidContentItems = $paidContentItemModel->getGroupedPaidContentItems($paidContentItems);
        
        foreach ($paidContentItems as $paidContentId => $paidContentItem) {
            $paidContentItems[$paidContentId]['masterTitle'] = $paidContentItemModel->getPaidContentMasterTitlePhraseValue(
                $paidContentId);
        }
        
        $options = $optionModel->getOptionsByIds(
            array(
                'payPalPrimaryAccount',
                'payPalAlternateAccounts'
            ));
        krsort($options); // just make sure the primary account is first
        
        $viewParams = array(
            'paidContentItems' => $paidContentItems,
            
            'options' => $optionModel->prepareOptions($options),
            'canEditOptionDefinition' => $optionModel->canEditOptionAndGroupDefinitions()
        );
        return $this->responseView('ThemeHouse_PayForContent_ViewAdmin_PaidContent_List',
            'th_paid_content_item_list_payforcontent', $viewParams);
    }

    /**
     * Helper to get the paid content add/edit form controller response.
     *
     * @param array $paidContentItem
     *
     * @return XenForo_ControllerResponse_View
     */
    protected function _getPaidContentItemAddEditResponse(array $paidContentItem)
    {
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
            
            'userGroups' => $userGroups
        );
        
        return $this->responseView('ThemeHouse_PayForContent_ViewAdmin_PaidContentItem_Edit',
            'th_paid_content_item_edit_payforcontent', $viewParams);
    }

    /**
     * Displays a form to add a new paid content.
     *
     * @return XenForo_ControllerResponse_View
     */
    public function actionAdd()
    {
        $paidContentItem = $this->_getPaidContentModel()->getDefaultPaidContentItem();
        
        return $this->_getPaidContentItemAddEditResponse($paidContentItem);
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
        $paidContentItem['masterTitle'] = $paidContentModel->getPaidContentMasterTitlePhraseValue($paidContentId);
        
        $handler = $paidContentModel->getPaidContentHandler($paidContentItem['content_type']);
        
        $paidContentItem['title'] = $handler->getContentTitle($paidContentItem['content_id']);
        
        return $this->_getPaidContentItemAddEditResponse($paidContentItem);
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
        
        if ($this->_input->filterSingle('reload', XenForo_Input::STRING)) {
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED, 
                XenForo_Link::buildAdminLink('paid-content/edit', $writer->getMergedData()));
        } else {
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
                XenForo_Link::buildAdminLink('paid-content') . $this->getLastHash($writer->get('paid_content_id')));
        }
    }

    /**
     * Deletes a paid content.
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionDelete()
    {
        $paidContentId = $this->_input->filterSingle('paid_content_id', XenForo_Input::STRING);
        $paidContentItem = $this->_getPaidContentItemOrError($paidContentId);
        
        $writer = XenForo_DataWriter::create('ThemeHouse_PayForContent_DataWriter_PaidContent');
        $writer->setExistingData($paidContentItem);
        
        if ($this->isConfirmedPost()) { // delete paid content
            $writer->delete();
            
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
                XenForo_Link::buildAdminLink('paid-content'));
        } else { // show delete confirmation prompt
            $writer->preDelete();
            $errors = $writer->getErrors();
            if ($errors) {
                return $this->responseError($errors);
            }
            
            $viewParams = array(
                'paidContentItem' => $paidContentItem
            );
            
            return $this->responseView('ThemeHouse_PayForContent_ViewAdmin_PaidContentItem_Delete',
                'th_paid_content_item_delete_payforcontent', $viewParams);
        }
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