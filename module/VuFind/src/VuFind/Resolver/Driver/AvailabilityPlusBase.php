<?php
/**
 * AvailabilityPlusBase Link Resolver Driver
 *
 * PHP version 7
 *
 * Copyright (C) Royal Holloway, University of London
 *
 * last update: 2010-10-11
 * tested with X-Server SFX 3.2
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
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:link_resolver_drivers Wiki
 */
namespace VuFind\Resolver\Driver;

/**
 * SFX Link Resolver Driver
 *
 * @category VuFind
 * @package  Resolver_Drivers
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:link_resolver_drivers Wiki
 */
class AvailabilityPlusBase extends AbstractBase
{
    /**
     * HTTP client
     *
     * @var \Zend\Http\Client
     */
    protected $httpClient;

    /**
     * Constructor
     *
     * @param string            $baseUrl    Base URL for link resolver
     * @param \Zend\Http\Client $httpClient HTTP client
     */
    public function __construct($baseUrl, \Zend\Http\Client $httpClient)
    {
        parent::__construct($baseUrl);
        $this->httpClient = $httpClient;
    }

    public function prepareUrl($resolver, $resolverData, $config)
    {
        if (!empty($resolverData) && !empty($config['ResolverBaseURL'][$resolver])) {
            $baseUrl = $config['ResolverBaseURL'][$resolver];
            $used_params = [];
            $params = '';

            if (is_array($resolverData)) {
                foreach ($resolverData as $resolverDate) {
                    if (is_array($resolverDate)) {
                        foreach ($resolverDate as $key => $value) {
                            if (!in_array($key, $used_params)) {
                                if (empty($params)) {
                                    $params .= '?' . $key . '=' . urlencode($value['data'][0]);
                                } else {
                                    $params .= '&' . $key . '=' . urlencode($value['data'][0]);
                                }
                                $used_params[] = $key;
                            }
                        }
                    }
                }
            }

            if (!empty($config['ResolverExtraParams'][$resolver])) $params .= $config['ResolverExtraParams'][$resolver];

            return $baseUrl . $params;
        }
        return '';
    }

    /**
     * Fetch Links
     *
     * Fetches a set of links corresponding to an OpenURL
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string         raw XML returned by resolver
     */
    public function fetchLinks($url)
    {
        // Make the call to SFX and load
        $feed = $this->httpClient->setUri($url)->send()->getBody();
        return $feed;
    }

    /**
     * Parse Links
     *
     * Parses an XML file returned by a link resolver
     * and converts it to a standardised format for display
     *
     * @param string $xmlstr Raw XML returned by resolver
     *
     * @return array         Array of values
     */
    public function parseLinks($xmlstr)
    {
        return $xmlstr;
    }
}
