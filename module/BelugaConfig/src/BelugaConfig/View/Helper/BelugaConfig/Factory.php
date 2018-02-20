<?php
/**
 * Factory for RecordDriver view helper
 *
 */
namespace BelugaConfig\View\Helper\BelugaConfig;
use Zend\ServiceManager\ServiceManager;

class Factory
{
    /**
     *
     */
    public static function getConfigReader(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('BelugaConfig');
        return new ConfigReader($config);
    }
}
