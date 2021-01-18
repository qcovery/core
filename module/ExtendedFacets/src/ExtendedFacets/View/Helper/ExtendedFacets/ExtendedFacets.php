<?php
/**
 *
 */
namespace ExtendedFacets\View\Helper\ExtendedFacets;

class ExtendedFacets extends \Laminas\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config, \VuFind\Search\Memory $memory)
    {
        $this->config = $config;
    }

    public function processFacets($facetSet) {

        $facetSettings = $this->config->get('facets');

        foreach ($facetSettings->ShowFacetValue as $showFacet => $showFacetValues) {
            foreach ($facetSet as $facet => $value) {
                if (!in_array($value['value'], $showFacetValues->toArray())) {
                    unset($facetSet[$facet]);
                }
            }
        }

        return $facetSet;
    }
}
