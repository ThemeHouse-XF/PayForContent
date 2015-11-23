<?php

abstract class ThemeHouse_PayForContent_PaidContentHandler_Abstract
{

    /**
     * Standard approach to caching other model objects for the lifetime of the
     * model.
     *
     * @var array
     */
    protected $_modelCache = array();

    /**
     * Fetches the content required by paid content.
     *
     * @param array $contentIds
     *
     * @return array
     */
    abstract public function getContentByIds(array $contentIds);

    /**
     * Fetches the content required by paid content.
     *
     * @param int $contentId
     *
     * @return array
     */
    abstract public function getContentById($contentId);

    /**
     * Fetches the content title for the specified paid content.
     *
     * @param int $contentId
     *
     * @return string
     */
    abstract public function getContentTitle($contentId);

    abstract public function getTitleForContent(array $content);

    abstract public function addPaidContentIdToContent($paidContentId, $contentId);

    abstract public function removePaidContentIdFromContent($paidContentId, $contentId);

    abstract public function canManagePaidContent(array $content);

    abstract public function getBreadcrumbsForContent(array $content);

    abstract public function getMajorSection();

    /**
     * Gets the specified model object from the cache.
     * If it does not exist, it will be instantiated.
     *
     * @param string $class Name of the class to load
     *
     * @return XenForo_Model
     */
    public function getModelFromCache($class)
    {
        if (!isset($this->_modelCache[$class])) {
            $this->_modelCache[$class] = XenForo_Model::create($class);
        }

        return $this->_modelCache[$class];
    }
}