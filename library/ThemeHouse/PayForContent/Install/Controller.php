<?php

class ThemeHouse_PayForContent_Install_Controller extends ThemeHouse_Install
{

    protected $_resourceManagerUrl = 'https://xenforo.com/community/resources/pay-for-content.3328/';

    protected function _getPrerequisites()
    {
        return array(
            'XenResource' => '1010000'
        );
    }

    protected function _getFieldChangesOnInstall()
    {
        return array(
            'xf_paid_content_th' => array(
                'limit' => '`purchase_limit` int UNSIGNED NOT NULL DEFAULT 0'
            )
        );
    }

    protected function _getTables()
    {
        return array(
            'xf_paid_content_th' => array(
                'paid_content_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'content_id' => 'int UNSIGNED NOT NULL',
                'content_type' => 'varchar(25) NOT NULL',
                'cost_amount' => 'decimal(10,2) UNSIGNED NOT NULL',
                'cost_currency' => 'varchar(3) NOT NULL',
                'user_group_ids' => 'varbinary(255) NOT NULL DEFAULT \'\'',
                'can_purchase' => 'tinyint UNSIGNED NOT NULL DEFAULT \'1\'',
                'purchase_limit' => 'int UNSIGNED NOT NULL DEFAULT 0',
                'priority' => 'int UNSIGNED NOT NULL DEFAULT 10',
                'start_date' => 'int unsigned NOT NULL DEFAULT 0',
                'end_date' => 'int unsigned NOT NULL DEFAULT 0',
                'paypal_email' => 'varchar(120) NOT NULL DEFAULT \'\''
            ),
            'xf_paid_content_active_th' => array(
                'paid_content_record_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'content_id' => 'int UNSIGNED NOT NULL',
                'content_type' => 'varchar(25) NOT NULL',
                'user_id' => 'int UNSIGNED NOT NULL',
                'extra' => 'mediumblob NOT NULL',
                'start_date' => 'int unsigned NOT NULL',
                'end_date' => 'int unsigned NOT NULL DEFAULT 0'
            ),
            'xf_paid_content_log_th' => array(
                'paid_content_log_id' => 'int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'paid_content_record_id' => 'int unsigned NOT NULL',
                'processor' => 'varchar(25) NOT NULL',
                'transaction_id' => 'varchar(50) NOT NULL',
                'subscriber_id' => 'varchar(50) NOT NULL DEFAULT \'\'',
                'transaction_type' => 'enum(\'payment\',\'cancel\',\'info\',\'error\') NOT NULL',
                'message' => 'varchar(255) NOT NULL DEFAULT \'\'',
                'transaction_details' => 'mediumblob NOT NULL',
                'log_date' => 'int unsigned NOT NULL DEFAULT \'0\''
            )
        );
    }

    protected function _getTableChanges()
    {
        return array(
            'xf_resource' => array(
                'paid_content_item_ids_th' => 'blob NULL'
            ),
            'xf_user_profile' => array(
                'paid_content_ids_th' => 'blob NULL'
            )
        );
    }

    protected function _getKeys()
    {
        return array(
            'xf_paid_content_th' => array(
                'priority' => array(
                    'priority'
                ),
                'start_date' => array(
                    'start_date'
                ),
                'end_date' => array(
                    'end_date'
                )
            )
        );
    }

    protected function _getContentTypeFields()
    {
        return array(
            'resource' => array(
                'paid_content_handler_class' => 'ThemeHouse_PayForContent_PaidContentHandler_Resource'
            )
        );
    }


    protected function _postInstall()
    {
        $addOn = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('YoYo_');
        if ($addOn) {
            $db->query("
                INSERT INTO xf_paid_content_th (paid_content_id, content_id, content_type, cost_amount, cost_currency, user_group_ids, can_purchase, purchase_limit, priority, start_date, end_date, paypal_email)
                    SELECT paid_content_id, content_id, content_type, cost_amount, cost_currency, user_group_ids, can_purchase, purchase_limit, priority, start_date, end_date, paypal_email
                        FROM xf_paid_content_waindigo"); 
            $db->query("
                INSERT INTO xf_paid_content_active_th (paid_content_record_id, content_id, content_type, user_id, extra, start_date, end_date)
                    SELECT paid_content_record_id, content_id, content_type, user_id, extra, start_date, end_date
                        FROM xf_paid_content_active_waindigo"); 
            $db->query("
                INSERT INTO xf_paid_content_log_th (paid_content_log_id, paid_content_record_id, processor, transaction_id, subscriber_id, transaction_type, message, transaction_details, log_date)
                    SELECT paid_content_log_id, paid_content_record_id, processor, transaction_id, subscriber_id, transaction_type, message, transaction_details, log_date
                        FROM xf_paid_content_log_waindigo"); 
            $db->query("
                UPDATE xf_resource
                    SET paid_content_item_ids_th=paid_content_item_ids_waindigo");
            $db->query("
                UPDATE xf_user_profile
                    SET paid_content_ids_th=paid_content_ids_waindigo");
        }
    }
}