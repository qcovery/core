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
use VuFind\Net\UserIpReader;

/**
 * Demo Link Resolver Driver
 *
 * @category VuFind
 * @package  Resolver_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:link_resolver_drivers Wiki
 */
class JOP extends AbstractBase
{

    use OpenUrlMapTrait;

    /**
     * Parameters for link resolver
     *
     * @var array
     */
    protected $parameters;

    /**
     * Mapr
     *
     * @var array
     */
    protected $map = [
        'title' => 'title',
        'genre' => 'genre',
        'issn' => 'issn'];

    /**
     * HTTP client
     *
     * @var \Laminas\Http\Client
     */
    protected $httpClient;

    /**
     * User IP address reader
     *
     * @var UserIpReader
     */
    protected $userIpReader;

    /**
     * Constructor
     *
     * @param string $baseUrl Base URL for link resolver
     */
    public function __construct($baseUrl, \Laminas\Http\Client $httpClient,
        UserIpReader $userIpReader)
    {
        parent::__construct($baseUrl);
        $this->httpClient = $httpClient;
        $this->userIpReader = $userIpReader;
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
        $this->baseUrl .= '?' . implode('&', (array)$this->parameters);

        $ipAddr = $this->userIpReader !== null
            ? $this->userIpReader->getUserIp()
            : $_SERVER['REMOTE_ADDR'];
        $this->baseUrl .= '&pid=client_ip%3D' . urlencode($ipAddr);

        $paramsList = [];
        $paramsArray = $this->mapOpenUrl($openURL);
        $format = $this->mapFormat($paramsArray['format']);
        if (in_array($format, ['article', 'journal'])) {
            unset($paramsArray['format']);
            $paramsArray['genre'] = $format;
            foreach ($paramsArray as $key => $value) {
                $paramsList[] = $key . '=' . urlencode($value);
            }
        }
        $params = implode('&', $paramsList);
        return parent::getResolverUrl($params);
    }

    protected function mapFormat($format) {
        $format = strtolower($format);
        if ($format = 'ejournal') {
            return 'journal';
        } elseif ($format = 'electronic article') {
            return 'article';
        }
        return $format;
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
    public function fetchLinks($openURL)
    {
        // Get the actual resolver url for the given openUrl
        $url = $this->getResolverUrl($openURL);
        // Make the call to the EZB and load results
        $feed = $this->httpClient->setUri($url)->send()->getBody();
        return $feed;
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
        $records = [];
        try {
            $xml = new \SimpleXmlElement($data);
        } catch (\Exception $e) {
            return $records;
        }

        $root = $xml->xpath("//ElectronicData//ResultList");
        $xml = $root[0];
        foreach ($xml->children() as $target) {
            $record = [];
            $record['title'] = (string)$target->Title;
            $record['href'] = (string)$target->AccessURL;
            $record['coverage'] = '';
            $record['service_type'] = 'electronic';
            $record['access'] = (string)$target->AccessLevel;
            $notes = [];
            foreach ((array)$target->Additionals as $additional) {
                $notes[] = $additional;
            }
            $record['notes'] = implode(', ', $notes); 
            $records[] = $record;
        }

        $root = $xml->xpath("//PrintData//ResultList");
        $xml = $root[0];
        foreach ($xml->children() as $target) {
            $record = [];
            $record['title'] = (string)$target->Title;
            $record['href'] = (string)$target->AccessURL;
            $record['coverage'] = '';
            $record['service_type'] = 'getHolding';
            $record['access'] = (string)$target->AccessLevel;
            $notes = [];
            foreach ((array)$target->Additionals as $additional) {
                $notes[] = $additional;
            }
            $record['notes'] = implode(', ', $notes);
            $records[] = $record;
        }

        return $records;
    }
}
