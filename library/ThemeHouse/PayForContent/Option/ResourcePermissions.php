<?php

class ThemeHouse_PayForContent_Option_ResourcePermissions
{

    /**
     * Renders checkboxes allowing the selection of permissions.
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
        $permissionModel = XenForo_Model::create('XenForo_Model_Permission');

        $permissions = $permissionModel->fetchAllKeyed(
            '
			SELECT *
			FROM xf_permission
			WHERE permission_group_id = \'resource\'
			AND permission_type = \'flag\'
			ORDER BY interface_group_id DESC, display_order ASC
		', 'permission_id');

        unset($permissions['add']);

        $permissions = $permissionModel->preparePermissions($permissions);

        $options = array();

        $selectedPermissions = $preparedOption['option_value'];

        foreach ($permissions AS $permissionId => $permission)
        {
            $options[$permissionId] = array(
                'value' => $permissionId,
                'label' => $permission['title'],
                'selected' => in_array($permissionId, $selectedPermissions),
            );
        }

        $preparedOption['formatParams'] = $options;

        $extra['class'] = 'checkboxColumns';

        return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
            'option_list_option_checkbox', $view, $fieldPrefix, $preparedOption, $canEdit, $extra);
    }
}