<?php
/**
 *
 */
namespace Delivery\View\Helper\Delivery;

use Delivery\AvailabilityHelper;

class AvailabilityChecker extends \Zend\View\Helper\AbstractHelper
{

    protected $AvailabilityHelper;

    public function __construct($config)
    {
        $this->AvailabilityHelper = new AvailabilityHelper(null, $config['default']);
    }

    /**
     *
     */
    public function check($driver)
    {
        $this->AvailabilityHelper->setSolrDriver($driver);
        return ($this->AvailabilityHelper->checkSignature()) ? 'available' : 'not available'; 
    }

    /**
     *
     */
    public function getHierarchyTopID($driver)
    {
        $deliveryArticleData = $driver->getMarcData('DeliveryDataArticle');
        return $deliveryArticleData[2]['ppn']['data'][0] ?? '';
    }
}
