<?php
/**
 * Ajax Controller Module
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace BelugaConfig\Controller;

use VuFind\Exception\Auth as AuthException;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * This controller handles global AJAX functionality
 *
 * @category VuFind
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class AjaxController extends \VuFind\Controller\AjaxController
{

    /**
     * Get Tab Result Count
     *
     * Get the search result count for inactive search tabs.
     *
     * @return \Zend\Http\Response
     * @author Johannes Schultze <schultze@effective-webwork.de>
     */
    protected function getTabResultCountAjax()
    {
        $runner = $this->serviceLocator->get('VuFind\SearchRunner');

        // Send both GET and POST variables to search class:
        $request = $this->getRequest()->getQuery()->toArray() + $this->getRequest()->getPost()->toArray();

        if ($results = $runner->run($request, $request['class'], null, null)){
            if ($results) {
                return $this->output(number_format($results->getResultTotal(), 0, ",", "." ), self::STATUS_OK);
            }
        } else {
            return $this->output(0, self::STATUS_OK);
        }
    }
}
