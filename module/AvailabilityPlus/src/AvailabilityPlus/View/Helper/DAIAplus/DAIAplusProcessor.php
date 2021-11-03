<?php
/**
 *
 */
namespace AvailabilityPlus\View\Helper\DAIAplus;

class DAIAplusProcessor extends \Zend\View\Helper\AbstractHelper
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
        $hierarchyTopIDs = $driver->getHierarchyTopID();
        if (!empty($this->config[$formatList])) {
            $hideFormats = explode(',', $this->config[$formatList]);
            $formats = $driver->getFormats();
            if (!empty(array_intersect($hideFormats, $formats))) {
                if (!empty($hierarchyTopIDs)) {
                    if (in_array($driver->getUniqueID(), $hierarchyTopIDs)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
