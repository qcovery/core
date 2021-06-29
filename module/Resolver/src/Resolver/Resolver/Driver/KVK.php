<?php
/**
 * Demo Link Resolver Driver
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2015.
 *
 * last update: 2011-04-13
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
 * @package  Resolver_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:link_resolver_drivers Wiki
 */
namespace Resolver\Resolver\Driver;

use VuFind\Resolver\Driver\AbstractBase;

/**
 * Demo Link Resolver Driver
 *
 * @category VuFind
 * @package  Resolver_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:link_resolver_drivers Wiki
 */
class KVK extends AbstractBase
{
    /**
     * Parameters for link resolver
     *
     * @var array
     */
    protected $parameters;

    /**
     * Constructor
     *
     * @param string $baseUrl Base URL for link resolver
     */
    public function __construct($baseUrl = 'http://localhost')
    {
        parent::__construct($baseUrl);
    }

    /**
     * Set Base Url
     *
     * Set the basic url.
     *
     * @param string $baseURL (url-encoded)
     *
     * @return void
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Set Parameters 
     *
     * Set parameters.
     *
     * @param array $parameters
     *
     * @return void
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Get Resolver Url
     *
     * Transform the OpenURL as needed to get a working link to the resolver.
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string Returns resolver specific url
     */
    public function getResolverUrl($openURL)
    {
        $this->baseUrl .= '?' . implode('&', $this->parameters);
        $params = $this->mapOpenUrl($openURL);
        return parent::getResolverUrl($params);
    }

    protected function mapOpenUrl($openURL)
    {
        $mappedArray = [];
        $parameterArray = explode('&', $openURL);
        foreach ($parameterArray as $parameter) {
            list($key, $val) = explode('=', $parameter);
            switch ($key) {
                case 'rft.title%5B0%5D':
                    $mappedArray[] = 'TI=' . $val;
                    break;
                case 'rft.creator':
                    $mappedArray[] = 'AU=' . $val;
                    break;
                case 'rft.date':
                    $mappedArray[] = 'PY=' . $val;
                    break;
                case 'rft.isbn':
                    $mappedArray[] = 'SB=' . $val;
                    break;
                case 'rft.issn':
                    $mappedArray[] = 'SS=' . $val;
                    break;
            }
        }
	return implode('&', $mappedArray);
    }

    /**
     * Fetch Links
     *
     * Fetches a set of links corresponding to an OpenURL
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string
     */
    public function fetchLinks($openURL)
    {
        return $openURL;
    }

    /**
     * Parse Links
     *
     * Parses data returned by a link resolver
     * and converts it to a standardised format for display
     *
     * @param string $data Raw data
     *
     * @return array       Array of values
     */
    public function parseLinks($data)
    {
        return [
            [
                'href' => 'https://vufind.org/wiki?' . $data . '#print',
                'title' => 'Print',
                'coverage' => 'fake1',
                'service_type' => 'getHolding',
                'access' => 'unknown',
                'notes' => 'General notes',
            ],
            [
                'href' => 'https://vufind.org/wiki?' . $data . '#electronic',
                'title' => 'Electronic',
                'coverage' => 'fake2',
                'service_type' => 'getFullTxt',
                'access' => 'open',
                'authentication' => 'Authentication notes',
                'notes' => 'General notes',
            ],
        ];
    }
}
