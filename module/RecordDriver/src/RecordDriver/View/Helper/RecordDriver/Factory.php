<?php
/**
 * Factory for RecordDriver view helper
 *
 */
namespace RecordDriver\View\Helper\RecordDriver;
use Zend\ServiceManager\ServiceManager;

class Factory
{
    /**
     *
     */
    public static function getSolrDetails(ServiceManager $sm)
    {
        return new SolrDetails();
    }
}
