<?php
namespace AvailabilityPlus\Resolver\Driver;

use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

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
        $resolverPath = (string)$requestedName;
        $splittedResolverPath = explode('\\', $resolverPath);
        $resolverName = $splittedResolverPath[count($splittedResolverPath) - 1];

        return new $requestedName(
            $container,
            $config['ResolverBaseURL'][$resolverName],
            $container->get(\VuFindHttp\HttpService::class)->createClient(),
            $config['ResolverExtraParams'][$resolverName],
            "availabilityplus-resolver-{$resolverName}.yaml",
            $container->get(\VuFind\Crypt\HMAC::class),
            $config[$resolverName],
            $container->get('ControllerPluginManager')->get('url')
        );
    }
}
