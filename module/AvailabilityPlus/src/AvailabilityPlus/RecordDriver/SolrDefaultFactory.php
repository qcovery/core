<?php
namespace AvailabilityPlus\RecordDriver;

use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Factory for SolrDefault record drivers.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @link     https://vufind.org/wiki/development Wiki
 */
class SolrDefaultFactory extends \VuFind\RecordDriver\AbstractBaseFactory
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
        $searchConfig = $container->get('VuFind\Config\PluginManager')->get('searches');
        $solrMarcYaml = 'solrmarc.yaml';
        $finalOptions = [null, $searchConfig, $solrMarcYaml];
        $driver = parent::__invoke($container, $requestedName, $finalOptions);
        $driver->attachSearchService($container->get('VuFindSearch\Service'));
        return $driver;
    }
}
