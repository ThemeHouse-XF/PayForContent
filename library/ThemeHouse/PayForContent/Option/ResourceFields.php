<?php

class ThemeHouse_PayForContent_Option_ResourceFields
{

    /**
     * Renders checkboxes allowing the selection of fields.
     *
     * @param XenForo_View $view View object
     * @param string $fieldPrefix Prefix for the HTML form field name
     * @param array $preparedOption Prepared option info
     * @param boolean $canEdit True if an "edit" link should appear
     *
     * @return XenForo_Template_Abstract Template object
     */
    public static function renderOption(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        /* @var $fieldModel XenResource_Model_ResourceField */
        $fieldModel = XenForo_Model::create('XenResource_Model_ResourceField');
        
        $fields = $fieldModel->getResourceFields();
        
        if (!$fields) {
            return;
        }
        
        $fields = $fieldModel->prepareResourceFields($fields);
        
        $options = array();
        
        $selectedFields = $preparedOption['option_value'];
        
        foreach ($fields as $fieldId => $field) {
            $options[$fieldId] = array(
                'value' => $fieldId,
                'label' => $field['title'],
                'selected' => in_array($fieldId, $selectedFields)
            );
        }
        
        $preparedOption['formatParams'] = $options;
        
        $extra['class'] = 'checkboxColumns';
        
        return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_checkbox', $view, 
            $fieldPrefix, $preparedOption, $canEdit, $extra);
    }
}