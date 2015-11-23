<?php

/**
 * Handles paid content processing with PayPal.
 */
class ThemeHouse_PayForContent_PaidContentProcessor_PayPal
{

    /**
     *
     * @var Zend_Controller_Request_Http
     */
    protected $_request;

    /**
     *
     * @var XenForo_Input
     */
    protected $_input;

    /**
     * List of filtered input for handling a callback.
     *
     * @var array
     */
    protected $_filtered = null;

    /**
     * Info about the paid content item being processed.
     *
     * @var array false
     */
    protected $_paidContentItem = false;

    /**
     * Info about the user the paid content is for.
     *
     * @var array false
     */
    protected $_user = false;

    /**
     * The paid content record ID inserted/updated.
     *
     * @var integer null
     */
    protected $_paidContentRecordId = null;

    /**
     * The paid content record being processed.
     *
     * @var array false
     */
    protected $_paidContentRecord = false;

    /**
     *
     * @var ThemeHouse_PayForContent_Model_PaidContent
     */
    protected $_paidContentModel = null;

    /**
     * Initializes handling for processing a request callback.
     *
     * @param Zend_Controller_Request_Http $request
     */
    public function initCallbackHandling(Zend_Controller_Request_Http $request)
    {
        $this->_request = $request;
        $this->_input = new XenForo_Input($request);
        
        $this->_filtered = $this->_input->filter(
            array(
                'test_ipn' => XenForo_Input::UINT,
                'business' => XenForo_Input::STRING,
                'receiver_email' => XenForo_Input::STRING,
                'txn_type' => XenForo_Input::STRING,
                'txn_id' => XenForo_Input::STRING,
                'parent_txn_id' => XenForo_Input::STRING,
                'mc_currency' => XenForo_Input::STRING,
                'mc_gross' => XenForo_Input::UNUM,
                'payment_status' => XenForo_Input::STRING,
                'custom' => XenForo_Input::STRING,
                'subscr_id' => XenForo_Input::STRING
            ));
        
        $this->_paidContentModel = XenForo_Model::create('ThemeHouse_PayForContent_Model_PaidContent');
    }

    /**
     * Validates the callback request is valid.
     * If failure happens, the response should
     * tell the processor to retry.
     *
     * @param string $errorString Output error string
     *
     * @return boolean
     */
    public function validateRequest(&$errorString)
    {
        try {
            if ($this->_filtered['test_ipn'] && XenForo_Application::debugMode()) {
                $validator = XenForo_Helper_Http::getClient('https://www.sandbox.paypal.com/cgi-bin/webscr');
            } else {
                $validator = XenForo_Helper_Http::getClient('https://www.paypal.com/cgi-bin/webscr');
            }
            $validator->setParameterPost('cmd', '_notify-validate');
            $validator->setParameterPost($_POST);
            $validatorResponse = $validator->request('POST');
            
            if (!$validatorResponse || $validatorResponse->getBody() != 'VERIFIED' ||
                 $validatorResponse->getStatus() != 200) {
                $errorString = 'Request not validated';
                return false;
            }
        } catch (Zend_Http_Client_Exception $e) {
            $errorString = 'Connection to PayPal failed';
            return false;
        }
        
        return true;
    }

    /**
     * Validates pre-conditions on the callback.
     * These represent things that likely wouldn't get fixed
     * (and generally shouldn't happen), so retries are not necessary.
     *
     * @param string $errorString
     *
     * @return boolean
     */
    public function validatePreConditions(&$errorString)
    {
        $itemParts = explode(',', $this->_filtered['custom'], 4);
        if (count($itemParts) != 4) {
            $errorString = 'Invalid item (custom)';
            return false;
        }
        
        list($userId, $paidContentId, $validationType, $validation) = $itemParts;
        // $validationType allows validation method changes
        
        $user = XenForo_Model::create('XenForo_Model_User')->getFullUserById($userId);
        if (!$user) {
            $errorString = 'Invalid user';
            return false;
        }
        $this->_user = $user;
        
        $tokenParts = explode(',', $validation);
        if (count($tokenParts) != 3 || sha1($tokenParts[1] . $user['csrf_token']) != $tokenParts[2]) {
            $errorString = 'Invalid validation';
            return false;
        }
        
        $paidContentItem = $this->_paidContentModel->getPaidContentItemById($paidContentId);
        if (!$paidContentItem) {
            $errorString = 'Invalid paid content';
            return false;
        }
        if ($this->_paidContentModel->canPurchasePaidContentItem($paidContentId, $errorPhraseKey, $user)) {
            $errorString = 'User not eligible for paid content';
            return false;
        }
        $this->_paidContentItem = $paidContentItem;
        
        $business = strtolower($this->_filtered['business']);
        $receiverEmail = strtolower($this->_filtered['receiver_email']);
        
        $options = XenForo_Application::get('options');
        $accounts = preg_split('#\r?\n#', $options->payPalAlternateAccounts, -1, PREG_SPLIT_NO_EMPTY);
        $accounts[] = $options->payPalPrimaryAccount;
        if ($paidContentItem['paypal_email']) {
            $accounts[] = $paidContentItem['paypal_email'];
        }
        
        $matched = false;
        foreach ($accounts as $account) {
            $account = trim(strtolower($account));
            if ($account && ($business == $account || $receiverEmail == $account)) {
                $matched = true;
                break;
            }
        }
        
        if (!$matched) {
            $errorString = 'Invalid business or receiver_email';
            return false;
        }
        
        if (!$this->_filtered['txn_id']) {
            $errorString = array(
                'info',
                'No txn_id. No action to take.'
            );
            return false;
        }
        
        $transaction = $this->_paidContentModel->getProcessedTransactionLog($this->_filtered['txn_id']);
        if ($transaction) {
            $errorString = array(
                'info',
                'Transaction already processed. Skipping.'
            );
            return false;
        }
        
        $paidContentRecord = $this->_paidContentModel->getActivePaidContentRecord($this->_user['user_id'], 
            $this->_paidContentItem['content_id'], $this->_paidContentItem['content_type']);
        if ($paidContentRecord) {
            $this->_paidContentRecordId = $paidContentRecord['paid_content_record_id'];
            $this->_paidContentRecord = $paidContentRecord;
        }
        
        /*
         * TODO: do we have a log from a previous part of this subscription to
         * work with?
         */
        
        switch ($this->_filtered['txn_type']) {
            case 'web_accept':
            case 'subscr_payment':
                $paymentAmountPassed = (round($this->_filtered['mc_gross'], 2) ==
                     round($paidContentItem['cost_amount'], 2) &&
                     strtolower($this->_filtered['mc_currency']) == $paidContentItem['cost_currency']);
                
                /*
                 * TODO: check amount against subscription payments when we
                 * allow subscriptions
                 */
                
                if (!$paymentAmountPassed) {
                    $errorString = 'Invalid payment amount';
                    return false;
                }
        }
        
        return true;
    }

    /**
     * Once all conditions are validated, process the transaction.
     *
     * @return array [0] => log type (payment, cancel, info), [1] => log message
     */
    public function processTransaction()
    {
        switch ($this->_filtered['txn_type']) {
            case 'web_accept':
            case 'subscr_payment':
                if ($this->_filtered['payment_status'] == 'Completed') {
                    $this->_paidContentRecordId = $this->_paidContentModel->createPaidContentRecord(
                        $this->_user['user_id'], $this->_paidContentItem, !empty($this->_filtered['parent_txn_id']));
                    
                    return array(
                        'payment',
                        'Payment received'
                    );
                }
                break;
        }
        
        /*
         * TODO: Process refunds/cancellations.
         */
        
        return array(
            'info',
            'OK, no action'
        );
    }

    /**
     * Get details for use in the log.
     *
     * @return array
     */
    public function getLogDetails()
    {
        $details = $_POST;
        $details['_callbackIp'] = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false);
        
        return $details;
    }

    /**
     * Gets the transaction ID.
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->_filtered['txn_id'];
    }

    /**
     * Gets the subscriber ID.
     *
     * @return string
     */
    public function getSubscriberId()
    {
        return $this->_filtered['subscr_id'];
    }

    /**
     * Gets the ID of the processor.
     *
     * @return string
     */
    public function getProcessorId()
    {
        return 'paypal';
    }

    /**
     * Gets the ID of the paid content record changed.
     *
     * @return integer
     */
    public function getPaidContentRecordId()
    {
        return intval($this->_paidContentRecordId);
    }

    /**
     * Logs the request.
     *
     * @param string $type Log type (info, payment, cancel, error)
     * @param string $message Log message
     * @param array $extra Extra details to log (not including output from
     * getLogDetails)
     */
    public function log($type, $message, array $extra)
    {
        $paidContentRecordId = $this->getPaidContentRecordId();
        $processor = $this->getProcessorId();
        $transactionId = $this->getTransactionId();
        $subscriberId = $this->getSubscriberId();
        $details = $this->getLogDetails() + $extra;
        
        $this->_paidContentModel->logProcessorCallback($paidContentRecordId, $processor, $transactionId, $type, 
            $message, $details, $subscriberId);
    }
}