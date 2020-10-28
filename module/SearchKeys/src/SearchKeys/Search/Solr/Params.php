<?php
/**
 * Solr aspect of the Search Multi-class (Params)
**/

namespace SearchKeys\Search\Solr;

use VuFind\Search\QueryAdapter;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use SearchKeys\Search\SearchKeysHelper;
use FacetPrefix\Search\Solr\Params as BaseParams;

class Params extends BaseParams
{
    /**
     * SearchKeys Helper
     *
     * @var SearchKeysHelper
     */
    protected $searchKeysHelper;

    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options      Options to use
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     * @param HierarchicalFacetHelper      $facetHelper  Hierarchical facet helper
     * @param SearchKeysHelper             $searchKeysHelper  SearchKeys Helper
     */
    public function __construct($options, \VuFind\Config\PluginManager $configLoader,
        HierarchicalFacetHelper $facetHelper = null,
        SearchKeysHelper $searchKeysHelper
    ) {
        $this->searchKeysHelper = $searchKeysHelper;
        parent::__construct($options, $configLoader, $facetHelper);
    }

    /**
     * Initialize the object's search settings from a request object.
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    protected function initSearch($request)
    {
        if (empty($request->get('overrideIds', null))) {
            $config = $this->configLoader->get('searchkeys');
            if (isset($this->searchKeysHelper)) {
                $request = $this->searchKeysHelper->processSearchKeys($request, $this->getOptions(), $config, 'Solr');
            }  
        }
        parent::initSearch($request);
    }
}


