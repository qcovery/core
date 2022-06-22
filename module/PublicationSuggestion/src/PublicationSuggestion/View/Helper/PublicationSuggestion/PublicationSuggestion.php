<?php
namespace PublicationSuggestion\View\Helper\PublicationSuggestion;

class PublicationSuggestion extends \Zend\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config)
    {
        $this->config = $config->toArray();
    }

}
