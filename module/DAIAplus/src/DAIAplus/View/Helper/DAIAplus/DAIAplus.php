<?php
/**
 *
 */
namespace DAIAplus\View\Helper\DAIAplus;

class DAIAplus extends \Zend\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config)
    {
        $this->config = $config['DAIA'];
    }

    public function hideAvailabilityInfo($driver, $hideOnlyLink = false) {
        $formatList = ($hideOnlyLink) ? 'hideAvailabilityLink' : 'hideAvailability';
        if (!empty($this->config[$formatList])) {
            $hideFormats = explode(',', $this->config[$formatList]);
            $formats = $driver->getFormats();
            if (!empty(array_intersect($hideFormats, $formats))) {
                if (!empty($driver->getHierarchyTopID())) {
                    $hierarchyTopIDs = $driver->getHierarchyTopID();
                    if (in_array($driver->getUniqueID(), $hierarchyTopIDs)) {
                        return true;
                    }
                }
             }
         }
         return false;
    }
}
