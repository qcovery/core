<?php
/**
 * Cover Controller
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace BeluginoCover\Controller;

use VuFind\Cover\CachingProxy;
use BeluginoCover\Cover\Loader;
use VuFind\Session\Settings as SessionSettings;

/**
 * Generates covers for book entries
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class CoverController extends \VuFind\Controller\CoverController
{
    /**
     * Constructor
     *
     * @param Loader          $loader Cover loader
     * @param CachingProxy    $proxy  Proxy loader
     * @param SessionSettings $ss     Session settings
     */
    public function __construct(Loader $loader, CachingProxy $proxy,
        SessionSettings $ss
    ) {
        $this->loader = $loader;
        $this->proxy = $proxy;
        $this->sessionSettings = $ss;
    }

    /**
     * Convert image parameters into an array for use by the image loader.
     *
     * @return array
     */
    protected function getImageParams()
    {
        $params = $this->params();  // shortcut for readability
        return [
            // Legacy support for "isn" param which has been superseded by isbn:
            'isbn' => $params()->fromQuery('isbn') ?: $params()->fromQuery('isn'),
            'size' => $params()->fromQuery('size'),
            'type' => $params()->fromQuery('contenttype'),
            'title' => $params()->fromQuery('title'),
            'author' => $params()->fromQuery('author'),
            'callnumber' => $params()->fromQuery('callnumber'),
            'issn' => $params()->fromQuery('issn'),
            'oclc' => $params()->fromQuery('oclc'),
            'upc' => $params()->fromQuery('upc'),
            'recordid' => $params()->fromQuery('recordid'),
            'source' => $params()->fromQuery('source'),
            'format' => $params()->fromQuery('format'),
        ];
    }
}
