<?php

/**
 * Factory for GBV Findex Central backends.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2013.
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
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Findex\Search\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for GBV Findex backends.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class FindexBackendFactory extends \VuFind\Search\Factory\SolrDefaultBackendFactory
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->searchConfig = 'Findex';
//        $this->searchYaml = 'searchspecs.yaml';
//        $this->facetConfig = 'facets';
    }

    /**
     * Findex configuration
     *
     * @var \Zend\Config\Config
     */
    protected $findexConfig;

    /**
     * Create the backend.
     *
     * @param ServiceLocatorInterface $serviceLocator Superior service manager
     *
     * @return BackendInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $backend = parent::createService($serviceLocator);
        $this->searchConfig = 'Findex';
        return $backend;
    }

    /**
     * Get the Solr core.
     *
     * @return string
     */
    protected function getSolrCore()
    {
        $core = $this->config->get('Findex')->General->default_core;
        return isset($core)
            ? $core : 'biblio';
    }

    /**
     * Get the Solr URL.
     *
     * @return string|array
     */
    protected function getSolrUrl()
    {
        //$url = $this->findexConfig->General->url;
        $url = $this->config->get('Findex')->General->url;
        $core = $this->getSolrCore();
        if (is_object($url)) {
            return array_map(
                function ($value) use ($core) {
                    return "$value/$core";
                },
                $url->toArray()
            );
        }
        return "$url/$core";
    }
}
