<?php
/**
 * Factory for RecordDriver view helper
 *
 */
namespace Delivery\View\Helper\Delivery;
use Zend\ServiceManager\ServiceManager;

class Factory
{
    /**
     *
     */
    public static function getAvailabilityChecker(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('delivery');
        return new AvailabilityChecker($config['default']);
    }
}
