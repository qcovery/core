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
class HBZ extends AbstractBase
{

    use OpenUrlMapTrait;

    /**
     * Parameters for link resolver
     *
     * @var array
     */
    protected $parameters;

    /**
     * Map
     *
     * @var array
     */
    protected $map = [
        'rft.btitle' => 'title',//auch jtitle, atitle
        'rft.au' => 'author',
        'rft.date' => 'date',
        'rft.volume' => 'volume',
        'rft.issue' => 'issue',
        'rft.spage' => 'spage',
        'rft.isbn' => 'isbn',
        'rft.issn' => 'issn'];

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
        $paramsList = [];
        $paramsArray = $this->mapOpenUrl($openURL);
        $rawFormat = $paramsArray['format'] ?? '';
        $format = 'journal';
        switch($rawFormat) {
            case 'Journal':
            case 'eJournal':
                $genre = 'journal';
                break;
            case 'Serial Volume':
                $genre = 'issue';
                break;
            case 'Article':
            case 'electronic Article':
                $genre = 'article';
                break;
            case 'Book':
                $format = $genre = 'book'; 
                break;
            default:
                $format = 'book';
                $genre = 'document';
        }
        $this->baseUrl .= '&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3A' . $format;
        $this->baseUrl .= '&&rft.genre=' . $genre;
        unset($paramsArray['format']);
        foreach ($paramsArray as $key => $value) {
            $paramsList[] = $key . '=' . urlencode($value);
        }
        $params = implode('&', $paramsList);
        return parent::getResolverUrl($params);
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
        return $this->getResolverUrl($openURL);
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
                'href' => $data,
                'title' => 'HBZ-Link',
                'coverage' => '',
                'service_type' => 'getWebService',
                'access' => '',
                'notes' => '',
            ],
        ];
    }
}
