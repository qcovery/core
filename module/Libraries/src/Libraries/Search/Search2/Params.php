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
 * @category VuFind
 * @package  Search_Search2
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace Libraries\Search\Search2;

use Libraries\Libraries;
use VuFindSearch\ParamBag;
use VuFind\Search\Solr\HierarchicalFacetHelper;

/**
 * Search Params for second Solr index
 *
 * @category VuFind
 * @package  Search_Search2
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Params extends \SearchKeys\Search\Search2\Params
{
    protected $Libraries = null;
    protected $selectedLibrary = null;
    protected $includedLibraries = null;
    protected $excludedLibraries = null;

    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options      Options to use
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     */
    public function __construct($options, \VuFind\Config\PluginManager $configLoader,
        \VuFind\Search\Memory $searchMemory,
        HierarchicalFacetHelper $facetHelper = null
    ) {
        parent::__construct($options, $configLoader, $facetHelper);
        $this->Libraries = new Libraries($configLoader, $searchMemory);
    }

    /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings()
    {
        // Define Filter Query
        $filterQuery = $this->getHiddenFilters();
        $orFilters = array();
        foreach ($this->filterList as $field => $filter) {
            if ($orFacet = (substr($field, 0, 1) == '~')) {
                $field = substr($field, 1);
            }
            foreach ($filter as $value) {
                // Special case -- allow trailing wildcards, ranges and already ored facets:
                if (substr($value, -1) == '*'
                    || preg_match('/\[[^\]]+\s+TO\s+[^\]]+\]/', $value)
                    || preg_match('/^(.+)\sOR\s(.+)$/', $value, $matches)
                ) {
                    $q = $field.':'.$value;
                } else {
                // Be sure that values aren't slashed twice
                    $q = $field.':"'.addcslashes(stripcslashes($value), '"\\').'"';
                }
                if ($orFacet) {
                    $orFilters[$field] = isset($orFilters[$field])
                        ? $orFilters[$field] : array();
                    $orFilters[$field][] = $q;
                } else {
                    $filterQuery[] = $q;
                }
            }
        }
        foreach ($orFilters as $field => $parts) {
            $filterQuery[] = '{!tag=' . $field . '_filter}' . $field
                . ':(' . implode(' OR ', $parts) . ')';
        }
        return $filterQuery;
    }

    /**
     * Create search backend parameters for advanced features.
     *
     * @return ParamBag
     */
    public function getBackendParameters()
    {
        $backendParams = parent::getBackendParameters();
        if (!empty($this->includedLibraries)) {
            $backendParams->add('included_libraries', $this->includedLibraries);
        }
        if (!empty($this->excludedLibraries)) {
            $backendParams->add('excluded_libraries', $this->excludedLibraries);
        }
        return $backendParams;
    }

    /**
     * Initialize library settings.
     *
     * @return void
     */
    public function initLibraries()
    {
        $this->selectedLibrary = $this->Libraries->selectLibrary();
        $this->includedLibraries = $this->Libraries->getLibraryFilters($this->selectedLibrary['code'], $this->getsearchClassId(), true);
        $this->excludedLibraries = $this->Libraries->getLibraryFilters('', $this->getsearchClassId(), false);
    }

    /**
     * Initialize library settings.
     *
     * @return object
     */
    public function getLibraries()
    {
        return $this->Libraries;
    }

    /**
     * Initialize library settings.
     *
     * @return array
     */
    public function getIncludedLibraries()
    {
        return $this->includedLibraries;
    }

    /**
     * Initialize library settings.
     *
     * @param string
     *
     * @return object
     */
    public function getSelectedLibrary()
    {
        return $this->Libraries->getLibrary();
    }

    /**
     * Pull the search parameters
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    public function initFromRequest($request)
    {
        parent::initFromRequest($request);
        $this->initLibraries();
    }
}
