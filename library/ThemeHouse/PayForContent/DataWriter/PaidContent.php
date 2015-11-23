<?php

/**
 * Data writer for paid content.
 */
class ThemeHouse_PayForContent_DataWriter_PaidContent extends XenForo_DataWriter
{

    /**
     * Constant for extra data that holds the value for the phrase
     * that is the title of this paid content entry.
     *
     * This value is required on inserts.
     *
     * @var string
     */
    const DATA_TITLE = 'phraseTitle';

    /**
     * Title of the phrase that will be created when a call to set the
     * existing data fails (when the data doesn't exist).
     *
     * @var string
     */
    protected $_existingDataErrorPhrase = 'th_requested_paid_content_item_not_found_payforcontent';

    /**
     * Gets the fields that are defined for the table.
     * See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        return array(
            'xf_paid_content_th' => array(
                'paid_content_id' => array(
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ),
                'content_id' => array(
                    'type' => self::TYPE_UINT,
                    'required' => true
                ),
                'content_type' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true
                ),
                'cost_amount' => array(
                    'type' => self::TYPE_FLOAT,
                    'required' => true,
                    'verification' => array(
                        '$this',
                        '_verifyCostAmount'
                    )
                ),
                'cost_currency' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true,
                    'allowedValues' => array(
                        'usd',
                        'cad',
                        'aud',
                        'gbp',
                        'eur'
                    )
                ),
                'user_group_ids' => array(
                    'type' => self::TYPE_UNKNOWN,
                    'default' => '',
                    'verification' => array(
                        'XenForo_DataWriter_Helper_User',
                        'verifyExtraUserGroupIds'
                    )
                ),
                'purchase_limit' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'can_purchase' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 1
                ),
                'priority' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 10
                ),
                'start_date' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'end_date' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'paypal_email' => array(
                    'type' => self::TYPE_STRING,
                    'maxLength' => 120,
                    'verification' => array(
                        '$this',
                        '_verifyEmail'
                    ),
                    'requiredError' => 'please_enter_valid_email'
                )
            )
        )
        ;
    }

    /**
     * Gets the actual existing data out of data that was passed in.
     * See parent for explanation.
     *
     * @param mixed
     *
     * @return array false
     */
    protected function _getExistingData($data)
    {
        if (!$paidContentId = $this->_getExistingPrimaryKey($data, 'paid_content_id')) {
            return false;
        }
        
        $paidContentItem = $this->_getPaidContentModel()->getPaidContentItemById($paidContentId);
        if (!$paidContentItem) {
            return false;
        }
        
        return $this->getTablesDataFromArray($paidContentItem);
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'paid_content_id = ' . $this->_db->quote($this->getExisting('paid_content_id'));
    }

    /**
     * Verifies that the cost of the upgrade is valid.
     *
     * @param float $cost
     *
     * @return boolean
     */
    protected function _verifyCostAmount(&$cost)
    {
        if ($cost < 0) {
            $this->error(new XenForo_Phrase('th_please_enter_a_cost_no_less_than_zero_payforcontent'),
                'cost_amount');
            return false;
        } else {
            return true;
        }
    }

    /**
     * Verification callback to check the email address is in a valid form
     *
     * @param string Email Address
     *
     * @return bool
     */
    protected function _verifyEmail(&$email)
    {
        if ($this->isUpdate() && $email === $this->getExisting('paypal_email')) {
            return true;
        }
        
        if ($email === '') {
            return true;
        }
        
        if (!XenForo_Helper_Email::isEmailValid($email)) {
            $this->error(new XenForo_Phrase('please_enter_valid_email'), 'email');
            return false;
        }
        
        if (XenForo_Helper_Email::isEmailBanned($email)) {
            $this->error(new XenForo_Phrase('email_address_you_entered_has_been_banned_by_administrator'), 'email');
            return false;
        }
        
        return true;
    }

    protected function _preSave()
    {
        if ($this->get('cost_amount') == '0.00' && $this->get('purchase_limit')) {
            $this->error(new XenForo_Phrase('th_purchase_limit_not_available_for_free_content_payforcontent'),
                'purchase_limit');
        }
    }

    /**
     * Post-save handling.
     */
    protected function _postSave()
    {
        $titlePhrase = $this->getExtraData(self::DATA_TITLE);
        if ($titlePhrase !== null) {
            $this->_insertOrUpdateMasterPhrase($this->_getTitlePhraseName($this->get('paid_content_id')), $titlePhrase);
        }
        
        if ($this->isChanged('content_id') || $this->isChanged('content_type')) {
            $handler = $this->_getPaidContentModel()->getPaidContentHandler($this->get('content_type'));
            
            $handler->addPaidContentIdToContent($this->get('paid_content_id'), $this->get('content_id'));
            
            if ($this->isUpdate()) {
                if ($this->isChanged('content_type')) {
                    $handler = $this->_getPaidContentModel()->getPaidContentHandler($this->getExisting('content_type'));
                    
                    $handler->removePaidContentIdFromContent($this->get('paid_content_id'), 
                        $this->getExisting('content_id'));
                }
            }
        }
    }

    /**
     * Post-delete handling.
     */
    protected function _postDelete()
    {
        $paidContentId = $this->get('paid_content_id');
        
        $this->_deleteMasterPhrase($this->_getTitlePhraseName($paidContentId));
    }

    /**
     * Gets the name of the title phrase for this paid content entry.
     *
     * @param string $navigationId
     *
     * @return string
     */
    protected function _getTitlePhraseName($paidContentId)
    {
        return $this->_getPaidContentModel()->getPaidContentPhraseName($paidContentId);
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
}