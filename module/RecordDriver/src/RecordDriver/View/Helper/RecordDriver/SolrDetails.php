<?php
/**
 *
 */
namespace RecordDriver\View\Helper\RecordDriver;

class SolrDetails extends \Zend\View\Helper\AbstractHelper
{
    /**
     *
     */
    public function __construct()
    {
    }

    /**
     *
     */
    public function getCoreFields($driver, $category = '')
    {
        $solrMarcData = array();
        foreach ($driver->getSolrMarcKeys($category) as $solrMarcKey) {
            $solrMarcData[$solrMarcKey] = $driver->getMarcData($solrMarcKey);
        }
        return $solrMarcData;
    }
}
