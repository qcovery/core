<?php

namespace AvailabilityPlus\Resolver\Driver;

use Interop\Container\ContainerInterface;

class FolioFactory extends DriverWithHttpClientFactory
{
    /**
     * Create an object
     *
     * Creates the resolver like DriverWithHttpClientFactory and additionally sets
     * the cache used to store the Okapi token.
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
        $driver = parent::__invoke($container, $requestedName, $options);
        $driver->setCacheStorage(
            $container->get(\VuFind\Cache\Manager::class)->getCache('object', 'Folio')
        );

        return $driver;
    }

}
