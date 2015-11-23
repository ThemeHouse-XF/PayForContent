<?php

/**
 *
 * @see XenResource_DataWriter_Resource
 */
class ThemeHouse_PayForContent_Extend_XenResource_DataWriter_Resource extends XFCP_ThemeHouse_PayForContent_Extend_XenResource_DataWriter_Resource
{

    /**
     *
     * @see XenResource_DataWriter_Resource::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_resource'] = array_merge($fields['xf_resource'],
            array(
                'paid_content_item_ids_th' => array(
                    'type' => self::TYPE_STRING,
                    'default' => ''
                )
            ));

        return $fields;
    }

    protected function _getExistingData($data)
    {
        $existingData = parent::_getExistingData($data);

        if ($existingData === false) {
            return $existingData;
        }

        if (isset($existingData['xf_resource']['currency_old'])) {
            $existingData['xf_resource']['currency'] = $existingData['xf_resource']['currency_old'];
            unset($existingData['xf_resource']['currency_old']);
        }

        return $existingData;
    }

    /**
     *
     * @see XenResource_DataWriter_Resource::rebuildCounters()
     */
    public function rebuildCounters()
    {
        parent::rebuildCounters();

        $this->rebuildPaidContentIdCaches();
    }

    public function rebuildPaidContentIdCaches()
    {
        $paidContent = $this->_getPaidContentModel()->getPaidContentItems(
            array(
                'content_type' => 'resource',
                'content_id' => $this->get('resource_id')
            ));

        $paidContentIds = array_keys($paidContent);

        ksort($paidContentIds);

        $this->set('paid_content_item_ids_th', implode(',', $paidContentIds));
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