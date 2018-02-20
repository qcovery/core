<?php
/**
 * AuthorFacets aspect of the Search Multi-class (Params)
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Search_SolrAuthorFacets
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Libraries\Search\SolrAuthorFacets;

use Libraries\Libraries;

/**
 * AuthorFacets Search Parameters
 *
 * @category VuFind
 * @package  Search_SolrAuthorFacets
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Params extends \VuFind\Search\SolrAuthorFacets\Params
{
    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options      Options to use
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     * @param HierarchicalFacetHelper      $facetHelper  Hierarchical facet helper
     */
    public function __construct($options, \VuFind\Config\PluginManager $configLoader,
                                HierarchicalFacetHelper $facetHelper = null
    ) {
        parent::__construct($options, $configLoader);

        $this->Libraries = new Libraries($configLoader);
        $this->initLibraries();
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
}
