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
    public function getCoreFields($driver, $categories = [])
    {
        $solrMarcData = [];
        if (!empty($categories)) {
            foreach ($categories as $category) {
                foreach ($driver->getSolrMarcKeys($category) as $solrMarcKey) {
                    $solrMarcData[$solrMarcKey] = $driver->getMarcData($solrMarcKey);
                }
            }
        } else {
            foreach ($driver->getSolrMarcKeys() as $solrMarcKey) {
                $solrMarcData[$solrMarcKey] = $driver->getMarcData($solrMarcKey);
            }
        }
        return $solrMarcData;
    }
}
