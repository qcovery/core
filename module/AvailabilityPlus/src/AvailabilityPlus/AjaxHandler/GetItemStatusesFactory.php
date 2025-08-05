<?php
namespace AvailabilityPlus\AjaxHandler;

use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Factory for GetItemStatus AJAX handler.
 *
 * @category VuFind
 * @package  AJAX
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetItemStatusesFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }
        return new $requestedName(
            $container->get(\VuFind\Record\Loader::class),
            $container->get(\VuFind\Config\PluginManager::class)->get('availabilityplus'),
            $container->get('ViewRenderer'),
            $container->get(\VuFind\Resolver\Driver\PluginManager::class),
            $container->get(\VuFind\Config\PluginManager::class)->get('availabilityplus-resolver'),
            $container->get('ControllerPluginManager')->get('url')
        );
    }
}
