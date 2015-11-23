<?php

/**
 *
 * @see XenForo_DataWriter_User
 */
class ThemeHouse_PayForContent_Extend_XenForo_DataWriter_User extends XFCP_ThemeHouse_PayForContent_Extend_XenForo_DataWriter_User
{

    /**
     *
     * @see XenForo_DataWriter_User::_getFields()
     */
    protected function _getFields()
    {
        $fields = parent::_getFields();

        $fields['xf_user_profile']['paid_content_ids_th'] = array(
            array(
                'type' => self::TYPE_SERIALIZED,
                'default' => ''
            )
        );

        return $fields;
    }

    /**
     *
     * @param int $contentId
     * @param string $contentType
     */
    public function addPaidContent($contentId, $contentType)
    {
        $paidContentIds = $this->get('paid_content_ids_th');

        if ($paidContentIds) {
            $paidContentIds = unserialize($paidContentIds);
        }

        $paidContentIds[$contentType][$contentId] = 1;

        $this->set('paid_content_ids_th', serialize($paidContentIds));
    }
}