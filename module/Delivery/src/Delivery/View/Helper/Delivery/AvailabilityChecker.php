<?php
/**
 *
 */
namespace Delivery\View\Helper\Delivery;

use Delivery\Availability;

class AvailabilityChecker extends \Zend\View\Helper\AbstractHelper
{

    protected $Availability;

    public function __construct($config)
    {
        $this->Availability = new Availability();
        $this->Availability->setDeliveryConfig($config);
    }

    /**
     *
     */
    public function check($driver) {
        $this->Availability->setSolrDriver($driver);
        return ($this->Availability->checkItem()) ? 'available' : 'not available'; 
    }
}
