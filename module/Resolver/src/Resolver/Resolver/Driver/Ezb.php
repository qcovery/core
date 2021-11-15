<?php
/**
 * EZB Link Resolver Driver
 *
 * EZB is a free service -- the API endpoint is available at
 * http://services.dnb.de/fize-service/gvr/full.xml
 *
 * API documentation is available at
 * http://www.zeitschriftendatenbank.de/services/schnittstellen/journals-online-print
 *
 * PHP version 7
 *
 * Copyright (C) Markus Fischer, info@flyingfischer.ch
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
 * @author   Markus Fischer <info@flyingfischer.ch>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:link_resolver_drivers Wiki
 */
namespace Resolver\Resolver\Driver;

use DOMDocument;
use DOMXpath;
use VuFind\Net\UserIpReader;
use VuFind\Resolver\Driver\Ezb as EzbBase;

/**
 * EZB Link Resolver Driver
 *
 * @category VuFind
 * @package  Resolver_Drivers
 * @author   Markus Fischer <info@flyingfischer.ch>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:link_resolver_drivers Wiki
 */
class Ezb extends EzbBase
{
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

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }
}
