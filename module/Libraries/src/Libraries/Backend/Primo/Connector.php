<?php
/**
 * Primo Central Connector with Libraries Extension
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
namespace Libraries\Backend\Primo;

use Zend\Log\LoggerInterface;
use Zend\Http\Client as HttpClient;

class Connector extends \VuFindSearch\Backend\Primo\Connector
{

    /**
     * Support method for query() -- perform inner search logic
     *
     * @param string $institution Institution
     * @param string $terms       Associative array:
     * @param array  $args        Associative array of optional arguments:
     * @return array              An array of query results
     */
    protected function performSearch($institution, $terms, $args)
    {
        if (!empty($args['included_libraries'])) {
            $institution = $args['included_libraries'];
        }
        return \VuFindSearch\Backend\Primo\Connector::performSearch($institution, $terms, $args);
    }
}

