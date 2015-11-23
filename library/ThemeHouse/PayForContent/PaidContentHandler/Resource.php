<?php

class ThemeHouse_PayForContent_PaidContentHandler_Resource extends ThemeHouse_PayForContent_PaidContentHandler_Abstract
{

    public function getContentByIds(array $contentIds)
    {
        $resourceModel = $this->_getResourceModel();
        
        return $resourceModel->getResourcesByIds($contentIds);
    }

    public function getContentById($contentId)
    {
        $resourceModel = $this->_getResourceModel();
        
        $fetchOptions = array(
            'join' => XenResource_Model_Resource::FETCH_CATEGORY
        );
        
        return $resourceModel->getResourceById($contentId, $fetchOptions);
    }

    public function getContentTitle($contentId)
    {
        $resource = $this->getContentById($contentId);
        
        return $this->getTitleForContent($resource);
    }

    public function getTitleForContent(array $content)
    {
        return $content['title'];
    }

    public function addPaidContentIdToContent($paidContentId, $contentId)
    {
        /* @var $dw XenResource_DataWriter_Resource */
        $dw = XenForo_DataWriter::create('XenResource_DataWriter_Resource');
        
        $dw->setExistingData($contentId);
        $dw->rebuildPaidContentIdCaches();
        
        $dw->save();
    }

    public function removePaidContentIdFromContent($paidContentId, $contentId)
    {
        /* @var $dw XenResource_DataWriter_Resource */
        $dw = XenForo_DataWriter::create('XenResource_DataWriter_Resource');
        
        $dw->setExistingData($contentId);
        $dw->rebuildPaidContentIdCaches();
        
        $dw->save();
    }

    public function canManagePaidContent(array $content)
    {
        $resourceModel = $this->_getResourceModel();
        
        return $resourceModel->canManagePaidContent($content, $content);
    }

    public function getBreadcrumbsForContent(array $content)
    {
        $breadCrumbs = $this->_getCategoryModel()->getCategoryBreadcrumb($content);
        $breadCrumbs = array_values($breadCrumbs);
        $breadCrumbs[] = array(
            'href' => XenForo_Link::buildPublicLink('full:resources', $content),
            'value' => $content['title']
        );
        
        return $breadCrumbs;
    }

    public function getMajorSection()
    {
        return 'resources';
    }

    /**
     *
     * @return XenResource_Model_Resource
     */
    protected function _getResourceModel()
    {
        return $this->getModelFromCache('XenResource_Model_Resource');
    }

    /**
     *
     * @return XenResource_Model_Category
     */
    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('XenResource_Model_Category');
    }
}