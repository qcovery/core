<?php
/**
 *
 */
namespace RecordDriver\View\Helper\RecordDriver;

use Zend\View\Helper\AbstractHelper;

class SolrDetails extends AbstractHelper
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
            foreach ($driver->getSolrMarcKeys([], false) as $solrMarcKey) {
                $solrMarcData[$solrMarcKey] = $driver->getMarcData($solrMarcKey);
            }
        }
        return $solrMarcData;
    }
}
