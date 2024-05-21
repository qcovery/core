<?php
namespace PublicationSuggestion\View\Helper\PublicationSuggestion;

class PublicationSuggestion extends \Laminas\View\Helper\AbstractHelper
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
