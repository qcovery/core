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
namespace RelevancePicker\Search\Solr;

use VuFindSearch\ParamBag;
use VuFind\Search\Solr\HierarchicalFacetHelper;
use SearchKeys\Search\SearchKeysHelper;

//use VuFind\Search\Solr\Params as BaseParams;
use Libraries\Search\Solr\Params as BaseParams;

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
        parent::__construct($options, $configLoader, $facetHelper, $searchKeysHelper, $searchMemory);
    }

    /**
     * Create search backend parameters for advanced features.
     *
     * @return ParamBag
     */
    public function getBackendParameters()
    {
        $backendParams = parent::getBackendParameters();
        $backendParams->add('debugQuery', 'on');
        return $backendParams;
    }
}

