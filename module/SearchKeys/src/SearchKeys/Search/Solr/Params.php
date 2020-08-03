<?php
/**
 * Solr aspect of the Search Multi-class (Params)
**/

namespace SearchKeys\Search\Solr;

//use SearchKeys\Search\QueryAdapter;
use SearchKeys\Search\QueryAdapter;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use SearchKeys\Search\SearchKeysHelper;
use VuFind\Search\Solr\Params as BaseParams;

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

    /**
     * Build a string for onscreen display showing the
     *   query used in the search (not the filters).
     *
     * @return string user friendly version of 'query'
     */
    public function getDisplayQuery()
    {
        $translate = [$this, 'translate'];
        $showField = [$this->getOptions(), 'getHumanReadableFieldName'];

        return QueryAdapter::display($this->getQuery(), $translate, $showField);
    }

    /**
     * Build a string for onscreen display showing the
     *   query used in the search (not the filters).
     *
     * @return string raw version of 'query'
     */
    public function getRawQuery()
    {
        $config = $this->configLoader->get('searchkeys');
        $translate = $config->get('translate-solr');
        // Build display query:
        $query = QueryAdapter::display($this->getQuery(), NULL, array($this, 'returnIdentic'));
        if (isset($translate)) {
            foreach($translate as $translateTo => $translateFrom) {
                $query = preg_replace('/{'.$translateTo.'}/', $translateFrom, $query);
            }
        }
        return preg_replace('/^\((.*?)\)/', '$1', $query);
    }

    public function returnIdentic($item) {
        return $item;
    }

}


