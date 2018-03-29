<?php

namespace PAIA;

use \Zend\View\Helper\AbstractHelper;
use Zend\View\HelperPluginManager as ServiceManager;
use Beluga\Search\Factory\PrimoBackendFactory;
use Beluga\Search\Factory\SolrDefaultBackendFactory;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManagerInterface;
use VuFindSearch\Query\Query;
use \SimpleXMLElement;

class PAIAHelper extends AbstractHelper implements ServiceLocatorAwareInterface
{

   protected $serviceLocator;

   protected $paiaConfig;
   protected $belugaConfig;
   protected $ftcheckConfig;
   protected $proxyConfig;
   
   public function __construct()
   {
        $this->paiaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/PAIA.ini'), true);
        $this->belugaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/Beluga.ini'), true);
		$this->ftcheckConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/fulltext-availability-check.ini'), true);
		$this->proxyConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/fulltext-proxy-check.ini'), true);
   }

   /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator() {
        return $this->serviceLocator;
    }
    
	/**
     * Get Results from external DAIA Service
     *
     * @return Array
     */
	//TODO: Currently only working with DAIAplus Service - DAIAplus Service needs to be set up to conform to DAIA request specifications
    public function getDaiaResults($ppn, $list = false, $site = 'Default', $language = 'en') {
		$url_path = $this->paiaConfig['DAIA']['url'].'?id=ppn:'.$ppn.'&format=json'.'&site='.$site.'&language='.$language.'&list='.$list;
		$daia = file_get_contents($url_path);
        $daiaJson = json_decode($daia, true);
		
		return $daiaJson;
	}

	/**
     * Get Results from external E-Availability Service
     *
     * @return Array
     */
	//TODO: Complete function once external service is available
	public function getElectronicAvailability($ppn, $openUrl, $url_access, $url_access_level, $first_matching_issn, $GVKlink, $doi, $requesturi, $list) {
		return array();
	}
}

?>
