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

use VuFind\Search\QueryAdapter;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use SearchKeys\Search\SearchKeysHelper;

/**
 * Search Params for second Solr index
 *
 * @category VuFind
 * @package  Search_Search2
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Params extends \VuFind\Search\Search2\Params
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
     * @param \Laminas\StdLib\Parameters $request Parameter object representing user
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
}
