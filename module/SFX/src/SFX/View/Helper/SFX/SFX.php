<?php
/**
 *
 */
namespace SFX\View\Helper\SFX;

class SFX extends \Zend\View\Helper\AbstractHelper
{
    protected $Libraries;

    /**
     *
     */
    public function __construct($config, \VuFind\Search\Memory $memory)
    {
    }

    public function buttons() {
        return '';
    }
}
