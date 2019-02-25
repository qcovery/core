<?php
/**
 *
 */
namespace RVK\View\Helper\RVK;

class RVK extends \Zend\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config, \VuFind\Search\Memory $memory)
    {
        $this->config = $config;
    }

    public function getRvk($driver) {
        return '';
    }
}
