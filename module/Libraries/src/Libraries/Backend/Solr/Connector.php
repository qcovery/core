<?php
/**
 * Solr Connector with Libraries Extension
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
 * @package  Backend
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */
namespace Libraries\Backend\Solr;

use VuFindSearch\Backend\Solr\HandlerMap;
use VuFindSearch\ParamBag;

use Laminas\Http\Request;
use Laminas\Http\Client as HttpClient;

class Connector extends \VuFindSearch\Backend\Solr\Connector
{
    /**
     * Library Filter Type: either 'url' if url-Filters are supported
     *                          or 'fq' otherwise
     *
     * @var string
     */
    const LIBRARY_FILTER_TYPE = 'filter';

    /**
     * Send query to SOLR and return response body.
     *
     * @param string   $handler SOLR request handler to use
     * @param ParamBag $params  Request parameters
     *
     * @return string Response body
     */
    public function query($handler, ParamBag $params, bool $cacheable = false)
    {
        $this->addLibraryFilter($handler, $params);
        $urlSuffix = '';
        $paramString = implode('&', $params->request());
        if (strlen($paramString) > self::MAX_GET_URL_LENGTH) {
            $method = Request::METHOD_POST;
            $callback = function ($client) use ($paramString) {
                $client->setRawBody($paramString);
                $client->setEncType(HttpClient::ENC_URLENCODED);
                $client->setHeaders(['Content-Length' => strlen($paramString)]);
	    };
	} else {
            $method = Request::METHOD_GET;
            $urlSuffix .= ((strpos($this->url, '?') === false) ? '?' : '&') . $paramString;
            $callback = null;
        }

        $this->debug(sprintf('Query %s', $paramString));
	return $this->trySolrUrls($method, $urlSuffix, $callback, $cacheable);
    }

    /**
     * Beluga Core Libraries
     * Set Library Filters
     *
     * param string   $handler SOLR request handler to use
     * @param ParamBag $params  Request parameters
     *
     * @return void
     */
    private function addLibraryFilter($handler, ParamBag $params)
    {
        $includedLibraries = $params->get('included_libraries');
        $excludedLibraries = $params->get('excluded_libraries');

        $params->remove('included_libraries');
        $params->remove('excluded_libraries');
        $space = (self::LIBRARY_FILTER_TYPE == 'url') ? '%20' : '+';
        if (!empty($includedLibraries)) {
            $libraryFilter = '('.implode($space.'OR'.$space, $includedLibraries).')';
            if (!empty($excludedLibraries)) {
                $libraryFilter = '('.$libraryFilter.$space.'NOT'.$space.'('.implode($space.'OR'.$space, $excludedLibraries).'))';
            }
        }
        if (self::LIBRARY_FILTER_TYPE == 'url') {
            $this->url = (empty($libraryFilter)) ? $this->url . '/filter/' . $handler : $this->url . '/filter/' . $libraryFilter . '/' . $handler;
        } else {
            $this->url = (empty($libraryFilter)) ? $this->url . '/' . $handler : $this->url . '/' . $handler . '?fq=' . $libraryFilter;
        }
    }
}


