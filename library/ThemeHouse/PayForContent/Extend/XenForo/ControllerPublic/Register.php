<?php

/**
 *
 * @see XenForo_ControllerPublic_Register
 */
class ThemeHouse_PayForContent_Extend_XenForo_ControllerPublic_Register extends XFCP_ThemeHouse_PayForContent_Extend_XenForo_ControllerPublic_Register
{

    public function actionIndex()
    {
        $response = parent::actionIndex();
        
        if ($response instanceof XenForo_ControllerResponse_View) {
            $paidContentId = $this->_input->filterSingle('paid_content_id', XenForo_Input::UINT);
            
            $response->params['paidContentId'] = $paidContentId;
        }
        
        return $response;
    }

    protected function _completeRegistration(array $user, array $extraParams = array())
    {
        $response = parent::_completeRegistration($user, $extraParams);
        
        if ($response instanceof XenForo_ControllerResponse_View) {
            $paidContentId = $this->_input->filterSingle('paid_content_id', XenForo_Input::UINT);
            
            if ($paidContentId) {
                /* @var $paidContentModel ThemeHouse_PayForContent_Model_PaidContent */
                $paidContentModel = $this->getModelFromCache('ThemeHouse_PayForContent_Model_PaidContent');
                
                $paidContentItem = $paidContentModel->preparePaidContent(
                    $paidContentModel->getPaidContentItemById($paidContentId));
                
                if (!$paidContentItem) {
                    return $response;
                }
                
                $paidContentHandler = $paidContentModel->getPaidContentHandler($paidContentItem['content_type']);
                
                if (!$paidContentHandler) {
                    return $response;
                }
                
                $content = $paidContentHandler->getContentById($paidContentItem['content_id']);
                
                if (!$content) {
                    return $response;
                }
                
                if ($paidContentModel->canPurchasePaidContentItem($paidContentItem, $errorPhraseKey, $user)) {
                    return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
                        XenForo_Link::buildPublicLink('paid-content/purchase', $paidContentItem));
                }
            }
        }
        
        return $response;
    }
}