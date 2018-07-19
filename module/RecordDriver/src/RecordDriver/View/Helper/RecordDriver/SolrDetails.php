<?php
/**
 *
 */
namespace RecordDriver\View\Helper\RecordDriver;

use Zend\View\Helper\AbstractHelper;
use RecordDriver\RecordDriver\SolrMarc as RecordDriver;

class SolrDetails extends AbstractHelper
{
    /**
     *
     */
    public function getCoreFields(RecordDriver $driver, $categories = [])
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
