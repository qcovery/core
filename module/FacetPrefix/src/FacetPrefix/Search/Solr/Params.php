<?php
/**
 * Solr aspect of the Search Multi-class (Params)
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2011.
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
 * @package  Search_Solr
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace FacetPrefix\Search\Solr;

use VuFindSearch\ParamBag;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use VuFind\Search\Solr\Params as BaseParams;

/**
 * Solr Search Parameters
 *
 * @category VuFind
 * @package  Search_Solr
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Params extends BaseParams
{
    use \VuFind\Search\Params\FacetLimitTrait;
    use \FacetPrefix\Search\Params\FacetRestrictionsTrait;

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

        $config = $configLoader->get($options->getFacetsIni());
        $this->initFacetRestrictionsFromConfig($config->Results_Settings ?? null);
    }

    /**
     * Return current facet configurations
     *
     * @return array $facetSet
     */
    public function getFacetSettings()
    {
        // Build a list of facets we want from the index
        $facetSet = [];

        if (!empty($this->facetConfig)) {
            $facetSet = parent::getFacetSettings();
            foreach (array_keys($this->facetConfig) as $facetField) {
                $fieldPrefix = $this->getFacetPrefixForField($facetField);
                if (!empty($fieldPrefix)) {
                    $facetSet["f.{$facetField}.facet.prefix"] = $fieldPrefix;
                }
                $fieldMatches = $this->getFacetMatchesForField($facetField);
                if (!empty($fieldMatches)) {
                    $facetSet["f.{$facetField}.facet.matches"] = $fieldMatches;
                }
            }
        }
        return $facetSet;
    }
}
