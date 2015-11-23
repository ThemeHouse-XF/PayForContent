<?php

class ThemeHouse_PayForContent_Listener_FileHealthCheck
{

    public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes = array_merge($hashes,
            array(
                'library/ThemeHouse/PayForContent/ControllerAdmin/PaidContent.php' => '8235b9182ac1c26417edddc5b68f6ebf',
                'library/ThemeHouse/PayForContent/ControllerPublic/PaidContent.php' => 'd82b8d9ee7d0a15df1a868b3b50b66b9',
                'library/ThemeHouse/PayForContent/DataWriter/PaidContent.php' => '85d13e7325d5344063d52a7fa5f0f08b',
                'library/ThemeHouse/PayForContent/Extend/XenForo/ControllerPublic/Register.php' => '2e2a48466cc58e908ab381e7f648d12c',
                'library/ThemeHouse/PayForContent/Extend/XenForo/DataWriter/User.php' => '402213681e041306da5c3492137ae9ea',
                'library/ThemeHouse/PayForContent/Extend/XenResource/ControllerPublic/Resource.php' => 'f2db954a312196c203e2a95e5318a39c',
                'library/ThemeHouse/PayForContent/Extend/XenResource/DataWriter/Resource.php' => '77310608e461f9ccbcff0abf4b8c5ceb',
                'library/ThemeHouse/PayForContent/Extend/XenResource/Model/Category.php' => '02dd60f9a4f9f0df7173a40e3e790b40',
                'library/ThemeHouse/PayForContent/Extend/XenResource/Model/Resource.php' => '8bcb05bcc69ec7192d987949e931272d',
                'library/ThemeHouse/PayForContent/Extend/XenResource/ViewPublic/Helper/Resource.php' => '0f6e15ce529fd9fb892bdfe788c6d342',
                'library/ThemeHouse/PayForContent/Install/Controller.php' => 'a66c99727ab896fdcfe2af86ae4f94f8',
                'library/ThemeHouse/PayForContent/Listener/LoadClass.php' => '5626b8349fb2d177d7422e5f9faee894',
                'library/ThemeHouse/PayForContent/Model/PaidContent.php' => '775cecc368589ff8c54f582905220b77',
                'library/ThemeHouse/PayForContent/Option/ResourceFields.php' => '584a0945cf3dd59b70468d3224669fdc',
                'library/ThemeHouse/PayForContent/Option/ResourcePermissions.php' => 'dd41f292cf7b97f6beb71b92aabe5159',
                'library/ThemeHouse/PayForContent/PaidContentHandler/Abstract.php' => '65669b9f70a46dc8e39020aa479e0d56',
                'library/ThemeHouse/PayForContent/PaidContentHandler/Resource.php' => '413d6a9f6dd25628409b88df4bec4fad',
                'library/ThemeHouse/PayForContent/PaidContentProcessor/PayPal.php' => '2d62e44f72ca54d59800a82b3da35f0e',
                'library/ThemeHouse/PayForContent/Route/Prefix/PaidContent.php' => '15e641b4e80be9bba7e864364b5ece69',
                'library/ThemeHouse/PayForContent/Route/PrefixAdmin/PaidContent.php' => '90181a7db37b779d6930adb47e293b62',
                'library/ThemeHouse/PayForContent/ViewAdmin/PaidContent/List.php' => 'b3dfdbe5f2c5ea2eb9c8d73b03c388cb',
                'library/ThemeHouse/Install.php' => '18f1441e00e3742460174ab197bec0b7',
                'library/ThemeHouse/Install/20151109.php' => '2e3f16d685652ea2fa82ba11b69204f4',
                'library/ThemeHouse/Deferred.php' => 'ebab3e432fe2f42520de0e36f7f45d88',
                'library/ThemeHouse/Deferred/20150106.php' => 'a311d9aa6f9a0412eeba878417ba7ede',
                'library/ThemeHouse/Listener/ControllerPreDispatch.php' => 'fdebb2d5347398d3974a6f27eb11a3cd',
                'library/ThemeHouse/Listener/ControllerPreDispatch/20150911.php' => 'f2aadc0bd188ad127e363f417b4d23a9',
                'library/ThemeHouse/Listener/InitDependencies.php' => '8f59aaa8ffe56231c4aa47cf2c65f2b0',
                'library/ThemeHouse/Listener/InitDependencies/20150212.php' => 'f04c9dc8fa289895c06c1bcba5d27293',
                'library/ThemeHouse/Listener/LoadClass.php' => '5cad77e1862641ddc2dd693b1aa68a50',
                'library/ThemeHouse/Listener/LoadClass/20150518.php' => 'f4d0d30ba5e5dc51cda07141c39939e3',
                'paid_content_callback.php' => 'f5219b0467d8f720127535d9ac9ec88b',
            ));
    }
}