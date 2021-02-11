<?php
/**
 * Params Extension for Libraries Module
 *
 * PHP version 5
 *
 * Copyright (C) Staats- und Universitätsbibliothek 2017.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Search
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */
namespace Libraries\Search\Solr;

use Libraries\Libraries;
use VuFindSearch\ParamBag;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use SearchKeys\Search\SearchKeysHelper;

//use VuFind\Search\Solr\Params as BaseParams;
use SearchKeys\Search\Solr\Params as BaseParams;

class Params extends BaseParams
{
    protected $Libraries = null;
    protected $selectedLibrary = null;
    protected $includedLibraries = null;
    protected $excludedLibraries = null;
    protected $facetFieldPrefix = [];

    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options      Options to use
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     */
    public function __construct($options, \VuFind\Config\PluginManager $configLoader,
        HierarchicalFacetHelper $facetHelper = null,
        SearchKeysHelper $searchKeysHelper,
        \VuFind\Search\Memory $searchMemory
    ) {
        parent::__construct($options, $configLoader, $facetHelper, $searchKeysHelper);
        $this->Libraries = new Libraries($configLoader->get('libraries'), $searchMemory);
    }


    /**
     * Format a single filter for use in getFilterList().
     *
     * @param string $field     Field name
     * @param string $value     Field value
     * @param string $operator  Operator (AND/OR/NOT)
     * @param bool   $translate Should we translate the label?
     *
     * @return array
     */
    protected function formatFilterListEntry($field, $value, $operator, $translate)
    {
        $filter = parent::formatFilterListEntry(
            $field, $value, $operator, $translate
        );
        if ($filter['field'] == '#') {
            $filter['field'] = 'Location';
            $filter['displayText'] = $this->Libraries->getLocationValue($filter['displayText']);
        }
        return $filter;
    }

    public function setFacetFieldPrefix($field, $prefix) {
        $this->facetFieldPrefix[$field] = $prefix;
    }

    /**
     * Return current facet configurations
     *
     * @return array $facetSet
     */
    public function getFacetSettings()
    {
        $facetSet = parent::getFacetSettings();
        foreach ($this->facetFieldPrefix as $field => $prefix) {
            $facetSet["f.{$field}.facet.prefix"] = $prefix;
        }
        return $facetSet;
    }

    /**
     * Create search backend parameters for advanced features.
     *
     * @return ParamBag
     */
    public function getBackendParameters()
    {
        $backendParams = parent::getBackendParameters();
        if (empty($this->includedLibraries)) {
            $this->initLibraries();
        }
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

