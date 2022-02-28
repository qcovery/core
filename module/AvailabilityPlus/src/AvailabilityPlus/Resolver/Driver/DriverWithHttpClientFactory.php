<?php

namespace AvailabilityPlus\Resolver\Driver;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class DriverWithHttpClientFactory extends \VuFind\Resolver\Driver\DriverWithHttpClientFactory
{

    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        $config = $container->get('VuFind\Config\PluginManager')->get('availabilityplus-resolver');
        $resolverName = (string)$requestedName;
        $resolverName = substr($resolverName, strrpos($resolverName, '\\') + 1);
        return new $requestedName(
            $config['ResolverBaseURL'][$resolverName],
            $container->get('VuFindHttp\HttpService')->createClient(),
            $config['ResolverExtraParams'][$resolverName],
            'test options',
            $container->get('VuFind\Crypt\HMAC')
        );
    }

}

