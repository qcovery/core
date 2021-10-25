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
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getRvk($driver) {
        return '';
    }

    public function getRVKTree() {
        return '';
    }

    public function getRvkAndBklClassifications($driver) {
        return 'RVK BKL';
    }
}
