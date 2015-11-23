<?php

class ThemeHouse_PayForContent_Listener_LoadClass extends ThemeHouse_Listener_LoadClass
{

    protected function _getExtendedClasses()
    {
        return array(
            'ThemeHouse_PayForContent' => array(
                'model' => array(
                    'XenResource_Model_Resource',
                    'XenResource_Model_Category'
                ),
                'controller' => array(
                    'XenResource_ControllerPublic_Resource',
                    'XenForo_ControllerPublic_Register'
                ),
                'datawriter' => array(
                    'XenForo_DataWriter_User',
                    'XenResource_DataWriter_Resource'
                ),
                'helper' => array(
                    'XenResource_ViewPublic_Helper_Resource'
                ),
            ),
        );
    }

    public static function loadClassModel($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_PayForContent_Listener_LoadClass', $class, $extend, 'model');
    }

    public static function loadClassController($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_PayForContent_Listener_LoadClass', $class, $extend, 'controller');
    }

    public static function loadClassDataWriter($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_PayForContent_Listener_LoadClass', $class, $extend, 'datawriter');
    }

    public static function loadClassHelper($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_PayForContent_Listener_LoadClass', $class, $extend, 'helper');
    }
}