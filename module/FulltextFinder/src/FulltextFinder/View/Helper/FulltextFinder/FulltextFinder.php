<?php
/**
 *
 */
namespace FulltextFinder\View\Helper\FulltextFinder;

class FulltextFinder extends \Zend\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function show() {
        return 'FulltextFinder';
    }
}
