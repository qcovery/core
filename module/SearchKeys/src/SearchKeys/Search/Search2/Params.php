<?php

/**
 * Search Params for second Solr index
 *
 * PHP version 7
 *
 * Copyright (C) Staats- und Universitätsbibliothek Hamburg 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category SearchKeys
 * @package  Search_Search2
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace SearchKeys\Search\Search2;

//use SearchKeys\Search\QueryAdapter;                                     
use SearchKeys\Search\QueryAdapter;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use SearchKeys\Search\SearchKeysHelper;
use VuFind\Search\Search2\Params as BaseParams;                                            

/**
 * Search Params for second Solr index
 *
 * @category VuFind
 * @package  Search_Search2
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
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
                $request = $this->searchKeysHelper->processSearchKeys($request, $this->getOptions(), $config, 'Search2');
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
        $translate = $config->get('translate-search2');                  
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
