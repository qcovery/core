<?php

/**
 * Registry for search backends.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2017.
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
 * @package  Search
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Libraries\Search;

/**
 * Registry for search backends.
 *
 * @category VuFind
 * @package  Search
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class BackendRegistry extends \VuFind\Search\BackendRegistry
{
    /**
     * Default plugin factories.
     *
     * @var array
     */
    protected $factories = [
        'BrowZine' => 'VuFind\Search\Factory\BrowZineBackendFactory',
        'EDS' => 'VuFind\Search\Factory\EdsBackendFactory',
        'EIT' => 'VuFind\Search\Factory\EITBackendFactory',
        'LibGuides' => 'VuFind\Search\Factory\LibGuidesBackendFactory',
        'Pazpar2' => 'VuFind\Search\Factory\Pazpar2BackendFactory',
        'Primo' => 'VuFind\Search\Factory\PrimoBackendFactory',
        'Search2' => 'Libraries\Search\Factory\Search2BackendFactory',
        'Solr' => 'Libraries\Search\Factory\SolrDefaultBackendFactory',
        'SolrAuth' => 'VuFind\Search\Factory\SolrAuthBackendFactory',
        'SolrReserves' => 'VuFind\Search\Factory\SolrReservesBackendFactory',
        'SolrWeb' => 'VuFind\Search\Factory\SolrWebBackendFactory',
        'Summon' => 'VuFind\Search\Factory\SummonBackendFactory',
        'WorldCat' => 'VuFind\Search\Factory\WorldCatBackendFactory',
    ];
}
