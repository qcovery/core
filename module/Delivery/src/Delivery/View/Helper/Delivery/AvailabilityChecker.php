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
    public function check($driver) {
        $this->AvailabilityHelper->setSolrDriver($driver);
        return ($this->AvailabilityHelper->checkItem()) ? 'available' : 'not available'; 
    }
}
