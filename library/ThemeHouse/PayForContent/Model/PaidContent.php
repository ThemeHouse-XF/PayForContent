<?php

/**
 * Model for paid content.
 */
class ThemeHouse_PayForContent_Model_PaidContent extends XenForo_Model
{

    /**
     * Gets paid content that match the specified criteria.
     *
     * @param array $conditions List of conditions.
     * @param array $fetchOptions
     *
     * @return array [paid content id] => info.
     */
    public function getPaidContentItems(array $conditions = array(), array $fetchOptions = array())
    {
        $whereClause = $this->preparePaidContentItemConditions($conditions, $fetchOptions);
        
        $sqlClauses = $this->preparePaidContentItemFetchOptions($fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        
        return $this->fetchAllKeyed(
            $this->limitQueryResults(
                '
                SELECT paid_content.*
                    ' . $sqlClauses['selectFields'] . '
                FROM xf_paid_content_th AS paid_content
                ' . $sqlClauses['joinTables'] . '
                WHERE ' . $whereClause . '
                ' . $sqlClauses['orderClause'] . '
            ', $limitOptions['limit'], $limitOptions['offset']), 
            'paid_content_id');
    }

    /**
     * Gets the paid content that matches the specified criteria.
     *
     * @param array $conditions List of conditions.
     * @param array $fetchOptions Options that affect what is fetched.
     *
     * @return array false
     */
    public function getPaidContentItem(array $conditions = array(), array $fetchOptions = array())
    {
        $paidContentItems = $this->getPaidContentItems($conditions, $fetchOptions);
        
        return reset($paidContentItems);
    }

    /**
     * Gets a paid content by ID.
     *
     * @param integer $paidContentId
     * @param array $fetchOptions Options that affect what is fetched.
     *
     * @return array false
     */
    public function getPaidContentItemById($paidContentId, array $fetchOptions = array())
    {
        $conditions = array(
            'paid_content_id' => $paidContentId
        );
        
        return $this->getPaidContentItem($conditions, $fetchOptions);
    }

    /**
     * Gets the total number of a paid content that match the specified
     * criteria.
     *
     * @param array $conditions List of conditions.
     *
     * @return integer
     */
    public function countPaidContentItems(array $conditions = array())
    {
        $fetchOptions = array();
        
        $whereClause = $this->preparePaidContentItemConditions($conditions, $fetchOptions);
        $joinOptions = $this->preparePaidContentItemFetchOptions($fetchOptions);
        
        return $this->_getDb()->fetchOne(
            '
            SELECT COUNT(*)
            FROM xf_paid_content_th AS paid_content
            ' . $joinOptions['joinTables'] . '
            WHERE ' . $whereClause . '
        ');
    }

    /**
     * Groups paid content items.
     *
     * @param array $paidContentItems
     *
     * @return array [] => info
     */
    public function getGroupedPaidContentItems(array $paidContentItems)
    {
        $grouped = array();
        foreach ($paidContentItems as $item) {
            $grouped[$item['content_type']][$item['content_id']] = $item['content_id'];
        }
        
        if (!$grouped) {
            return array();
        }
        
        $handlers = $this->getPaidContentHandlers();
        
        foreach ($grouped as $contentType => &$content) {
            if (!empty($handlers[$contentType])) {
                $handler = $handlers[$contentType];
                $content = $handler->getContentByIds($content);
            } else {
                unset($grouped[$contentType]);
            }
        }
        
        $output = array();
        foreach ($paidContentItems as $entry) {
            if (isset($grouped[$entry['content_type']][$entry['content_id']])) {
                $entry['content'] = $grouped[$entry['content_type']][$entry['content_id']];
                $output[$entry['paid_content_id']] = $entry;
            }
        }
        
        return $output;
    }

    /**
     * Gets the default paid content record.
     *
     * @return array
     */
    public function getDefaultPaidContentItem()
    {
        return array(
            'paid_content_id' => '',
            'purchase_limit' => -1,
            'priority' => 10
        );
    }

    /**
     * Prepares a set of conditions to select paid content against.
     *
     * @param array $conditions List of conditions.
     * @param array $fetchOptions The fetch options that have been provided. May
     * be edited if criteria requires.
     *
     * @return string Criteria as SQL for where clause
     */
    public function preparePaidContentItemConditions(array $conditions, array &$fetchOptions)
    {
        $db = $this->_getDb();
        $sqlConditions = array();
        
        if (isset($conditions['paid_content_ids']) && !empty($conditions['paid_content_ids'])) {
            $sqlConditions[] = 'paid_content.paid_content_id IN (' . $db->quote($conditions['paid_content_ids']) . ')';
        } elseif (isset($conditions['paid_content_id'])) {
            $sqlConditions[] = 'paid_content.paid_content_id = ' . $db->quote($conditions['paid_content_id']);
        }
        
        if (isset($conditions['content_ids']) && !empty($conditions['content_ids'])) {
            $sqlConditions[] = 'paid_content.content_id IN (' . $db->quote($conditions['content_ids']) . ')';
        } elseif (isset($conditions['content_id'])) {
            $sqlConditions[] = 'paid_content.content_id = ' . $db->quote($conditions['content_id']);
        }
        
        if (isset($conditions['content_types']) && !empty($conditions['content_types'])) {
            $sqlConditions[] = 'paid_content.content_type IN (' . $db->quote($conditions['content_types']) . ')';
        } elseif (isset($conditions['content_type'])) {
            $sqlConditions[] = 'paid_content.content_type = ' . $db->quote($conditions['content_type']);
        }
        
        if (isset($conditions['start_date']) && is_array($conditions['start_date'])) {
            $isZero = '';
            if ($conditions['start_date'][0] == '>' || $conditions['start_date'][0] == '>=') {
                $isZero = 'paid_content.start_date = 0 OR ';
            }
            $sqlConditions[] = $isZero . $this->getCutOffCondition("paid_content.start_date", $conditions['start_date']);
        }
        
        if (isset($conditions['end_date']) && is_array($conditions['end_date'])) {
            $isZero = '';
            if ($conditions['end_date'][0] == '>' || $conditions['end_date'][0] == '>=') {
                $isZero = 'paid_content.end_date = 0 OR ';
            }
            $sqlConditions[] = $isZero . $this->getCutOffCondition("paid_content.end_date", $conditions['end_date']);
        }
        
        $this->_preparePaidContentItemConditions($conditions, $fetchOptions, $sqlConditions);
        
        return $this->getConditionsForClause($sqlConditions);
    }

    /**
     * Method designed to be overridden by child classes to add to set of
     * conditions.
     *
     * @param array $conditions List of conditions.
     * @param array $fetchOptions The fetch options that have been provided. May
     * be edited if criteria requires.
     * @param array $sqlConditions List of conditions as SQL snippets. May be
     * edited if criteria requires.
     */
    protected function _preparePaidContentItemConditions(array $conditions, array &$fetchOptions, array &$sqlConditions)
    {
    }

    /**
     * Checks the 'join' key of the incoming array for the presence of the
     * FETCH_x bitfields in this class
     * and returns SQL snippets to join the specified tables if required.
     *
     * @param array $fetchOptions containing a 'join' integer key built from
     * this class's FETCH_x bitfields.
     *
     * @return string containing selectFields, joinTables, orderClause keys.
     * Example: selectFields = ', user.*, foo.title'; joinTables = ' INNER JOIN
     * foo ON (foo.id = other.id) '; orderClause = 'ORDER BY x.y'
     */
    public function preparePaidContentItemFetchOptions(array &$fetchOptions)
    {
        $selectFields = '';
        $joinTables = '';
        $orderBy = 'paid_content.priority ASC,
            paid_content.end_date = 0,
            paid_content.end_date ASC,
            paid_content.start_date = 0,
            paid_content.start_date ASC';
        
        $this->_preparePaidContentItemFetchOptions($fetchOptions, $selectFields, $joinTables, $orderBy);
        
        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables,
            'orderClause' => ($orderBy ? "ORDER BY $orderBy" : '')
        );
    }

    /**
     * Method designed to be overridden by child classes to add to SQL snippets.
     *
     * @param array $fetchOptions containing a 'join' integer key built from
     * this class's FETCH_x bitfields.
     * @param string $selectFields = ', user.*, foo.title'
     * @param string $joinTables = ' INNER JOIN foo ON (foo.id = other.id) '
     * @param string $orderBy = 'x.y ASC, x.z DESC'
     */
    protected function _preparePaidContentItemFetchOptions(array &$fetchOptions, &$selectFields, &$joinTables, &$orderBy)
    {
    }

    public function preparePaidContent(array $paidContent)
    {
        if (!empty($paidContent['cost_amount']) && !isset($paidContent['currency_old'])) {
            if (isset($paidContent['currency'])) {
                $paidContent['currency_old'] = $paidContent['currency'];
            } else {
                $paidContent['currency_old'] = '';
            }
            $paidContent['currency'] = strtoupper($paidContent['cost_currency']);
        }
        
        if (!empty($paidContent['cost_amount'])) {
            if ($paidContent['cost_amount'] == '0.00') {
                $paidContent['costPhrase'] = new XenForo_Phrase('free');
            } else {
                $paidContent['costPhrase'] = XenForo_Locale::numberFormat($paidContent['cost_amount'], 2) . ' ' .
                     $paidContent['currency'];
            }
        }
        
        if (!empty($paidContent['paid_content_id'])) {
            $paidContent['paid_content_title'] = new XenForo_Phrase(
                $this->getPaidContentPhraseName($paidContent['paid_content_id']));
        }
        
        if (isset($paidContent['user_group_ids']) && $paidContent['user_group_ids'] != -1) {
            $paidContent['userGroupIds'] = explode(',', $paidContent['user_group_ids']);
        }
        
        if (!empty($paidContent['start_date'])) {
            $paidContent['startDate'] = XenForo_Locale::date($paidContent['start_date'], 'picker');
        }
        
        if (!empty($paidContent['end_date'])) {
            $paidContent['endDate'] = XenForo_Locale::date($paidContent['end_date'], 'picker');
        }
        
        return $paidContent;
    }

    public function preparePaidContentItems(array $paidContentItems)
    {
        foreach ($paidContentItems as &$paidContent) {
            $paidContent = $this->preparePaidContent($paidContent);
        }
        
        return $paidContentItems;
    }

    /**
     * Gets the paid content handler object for a specified content type.
     *
     * @param string $contentType
     *
     * @return ThemeHouse_PayForContent_PaidContentHandler_Abstract $handler
     */
    public function getPaidContentHandler($contentType)
    {
        if (!$contentType) {
            return null;
        }
        
        $class = $this->getContentTypeField($contentType, 'paid_content_handler_class');
        if (!class_exists($class)) {
            return null;
        }
        
        $class = XenForo_Application::resolveDynamicClass($class);
        return new $class();
    }

    /**
     * Gets all paid content handler classes.
     *
     * @return array
     */
    public function getPaidContentHandlers()
    {
        $classes = $this->getContentTypesWithField('paid_content_handler_class');
        $handlers = array();
        foreach ($classes as $contentType => $class) {
            if (!class_exists($class)) {
                continue;
            }
            
            $class = XenForo_Application::resolveDynamicClass($class);
            $handlers[$contentType] = new $class();
        }
        
        return $handlers;
    }

    /**
     * Gets the specified active paid content record, based on user and paid
     * content.
     *
     * @param integer $userId
     * @param integer $paidContentId
     *
     * @return array false
     */
    public function getActivePaidContentRecord($userId, $contentId, $contentType)
    {
        return $this->_getDb()->fetchRow(
            '
			SELECT paid_content_active.*,
				user.*
			FROM xf_paid_content_active_th AS paid_content_active
			INNER JOIN xf_user AS user ON
				(user.user_id = paid_content_active.user_id)
			WHERE paid_content_active.user_id = ?
				AND paid_content_active.content_id = ?
                AND paid_content_active.content_type = ?
		', 
            array(
                $userId,
                $contentId,
                $contentType
            ));
    }

    /**
     * Gets the specified active paid content record.
     *
     * @param integer $paidContentRecordId
     *
     * @return array false
     */
    public function getActivePaidContentRecordById($paidContentRecordId)
    {
        return $this->_getDb()->fetchRow(
            '
			SELECT paid_content_active.*,
				user.*
			FROM xf_paid_content_active_th AS paid_content_active
			INNER JOIN xf_user AS user ON
				(user.user_id = user_upgrade_active.user_id)
			WHERE paid_content_active.paid_content_record_id = ?
		', $paidContentRecordId);
    }

    /**
     * Inserts a paid content record for the user with the specified paid
     * content.
     *
     * @param integer $userId
     * @param array $paidContentItem Info about paid content to create record
     * for
     * @param boolean $allowInsertUnpurchasable Allow insert of a new record
     * even if not purchasable
     *
     * @return integer false content record ID
     */
    public function createPaidContentRecord($userId, array $paidContentItem, $allowInsertUnpurchasable = false)
    {
        $db = $this->_getDb();
        
        $active = $this->getActivePaidContentRecord($userId, $paidContentItem['content_id'], 
            $paidContentItem['content_type']);
        if ($active) {
            // TODO: extend subscriptions in future
            
            return $active['paid_content_record_id'];
        } else {
            if (!$paidContentItem['can_purchase'] && !$allowInsertUnpurchasable) {
                return false;
            }
            
            if ($paidContentItem['user_group_ids'] != '-1') {
                /* @var $userModel XenForo_Model_User */
                $userModel = $this->getModelFromCache('XenForo_Model_User');
                
                $user = $userModel->getUserById($userId);
                
                if (!$userModel->isMemberOfUserGroup($user, explode(',', $paidContentItem['user_group_ids']))) {
                    return false;
                }
            }
            
            // TODO: allow for end dates in future
            $endDate = 0;
            
            $extra = array(
                'cost_amount' => $paidContentItem['cost_amount'],
                'cost_currency' => $paidContentItem['cost_currency']
            );
            
            XenForo_Db::beginTransaction($db);
            
            $db->insert('xf_paid_content_active_th',
                array(
                    'user_id' => $userId,
                    'content_id' => $paidContentItem['content_id'],
                    'content_type' => $paidContentItem['content_type'],
                    'extra' => serialize($extra),
                    'start_date' => XenForo_Application::$time,
                    'end_date' => $endDate
                ));
            $paidContentRecordId = $db->lastInsertId();
            
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_User');
            $dw->setExistingData($userId);
            $dw->addPaidContent($paidContentItem['content_id'], $paidContentItem['content_type']);
            $dw->save();
            
            $db->query(
                '
                UPDATE xf_paid_content_th
                SET purchase_limit = purchase_limit - 1, can_purchase = IF(purchase_limit = 0, 0, 1)
                WHERE content_id = ? AND content_type = ?
                ', 
                array(
                    $paidContentItem['content_id'],
                    $paidContentItem['content_type']
                ));
            
            XenForo_Db::commit($db);
            
            return $paidContentRecordId;
        }
    }

    /**
     * Logs a payment processor callback request.
     *
     * @param integer $paidContentRecordId Paid content record ID this applies
     * to, if known
     * @param string $processor
     * @param string $transactionId
     * @param string $transactionType Type of transaction: info, payment,
     * cancel, error
     * @param string $message
     * @param array $details List of additional details about call
     * @param string $subscriberId
     *
     * @return integer Log record ID
     */
    public function logProcessorCallback($paidContentRecordId, $processor, $transactionId, $transactionType, $message, 
        array $details, $subscriberId = '')
    {
        $this->_getDb()->insert('xf_paid_content_log_th',
            array(
                'paid_content_record_id' => $paidContentRecordId,
                'processor' => $processor,
                'transaction_id' => $transactionId,
                'transaction_type' => $transactionType,
                'message' => substr($message, 0, 255),
                'transaction_details' => serialize($details),
                'log_date' => XenForo_Application::$time,
                'subscriber_id' => $subscriberId
            ));
        
        return $this->_getDb()->lastInsertId();
    }

    /**
     * Gets any log record that indicates a transaction has been processed.
     *
     * @param string $transactionId
     *
     * @return array false
     */
    public function getProcessedTransactionLog($transactionId)
    {
        if ($transactionId === '') {
            return array();
        }
        
        return $this->fetchAllKeyed(
            '
			SELECT *
			FROM xf_paid_content_log_th
			WHERE transaction_id = ?
				AND transaction_type IN (\'payment\', \'cancel\')
			ORDER BY log_date
		', 'paid_content_log_id', $transactionId);
    }

    /**
     * Gets the name of a paid content phrase.
     *
     * @param string $paidContentId
     *
     * @return string
     */
    public function getPaidContentPhraseName($paidContentId)
    {
        return 'paid_content_' . $paidContentId;
    }

    /**
     * Gets the master title phrase value for the specified paid content entry.
     *
     * @param string $paidContentId
     *
     * @return string
     */
    public function getPaidContentMasterTitlePhraseValue($paidContentId)
    {
        $phraseName = $this->getPaidContentPhraseName($paidContentId);
        return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
    }

    /**
     * Determines if the viewing user can purchase the specified paid content
     * item.
     *
     * @param array $paidContentItem
     * @param string $errorPhraseKey
     * @param array|null $viewingUser
     *
     * @return boolean
     */
    public function canPurchasePaidContentItem(array $paidContentItem, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);
        
        if (!$viewingUser['user_id']) {
            return false;
        }
        
        if ($paidContentItem['start_date'] && $paidContentItem['start_date'] > XenForo_Application::$time) {
            return false;
        }
        
        if ($paidContentItem['end_date'] && $paidContentItem['end_date'] < XenForo_Application::$time) {
            return false;
        }
        
        $userModel = $this->getModelFromCache('XenForo_Model_User');
        
        if ($paidContentItem['user_group_ids'] != -1 &&
             !$userModel->isMemberOfUserGroup($viewingUser, explode(',', $paidContentItem['user_group_ids']))) {
            return false;
        }
        
        return true;
    }

    /**
     *
     * @return XenForo_Model_Phrase
     */
    protected function _getPhraseModel()
    {
        return $this->getModelFromCache('XenForo_Model_Phrase');
    }
}