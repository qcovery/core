<?php
/**
 * Factory for RecordDriver view helper
 *
 */
namespace Libraries\View\Helper\Libraries;
use Libraries\Libraries;
use Zend\ServiceManager\ServiceManager;

class Factory
{
    /**
     *
     */
    public static function getConnectedLibraries(ServiceManager $sm)
    {
        $Librares = new Libraries($sm->getServiceLocator()->get('VuFind\Config'));
        return new ConnectedLibraries($Librares);
    }
}
