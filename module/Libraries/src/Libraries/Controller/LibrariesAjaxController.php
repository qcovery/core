<?php
/**
 * Ajax Controller for Libraries Extension
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
 * @package  Controller
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */
namespace Libraries\Controller;
use Libraries\Selector;
use VuFind\Controller\AjaxController;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * This controller handles global AJAX functionality
 *
 * @category VuFind2
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_a_controller Wiki
 */
class LibrariesAjaxController extends AjaxController
{
    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service locator
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);
    }

    /**
     * Get the number of Books for a selected library
     *
     * @return \Zend\Http\Response
     */
    protected function getLibraryFacetsAjax()
    {
        $searchClassId = trim($_GET['searchclass']);

        $Selector = new Selector($this->getRequest()->getQuery());
        $Selector->setServiceLocator($this->serviceLocator);
        $Selector->setLibraries();
        $Selector->buildSelectorQuery($_GET['querystring']);
        $libraryData = $Selector->getSelectorData($searchClassId);

        return $this->output([
            'libraryData' => $libraryData,
            'locationFacets' => $Selector->getLocationFacets(),
            'locationFilter' => $Selector->getLocationFilter(),
            'counterTabCount' => $Selector->getCounterTabCount()
        ], self::STATUS_OK);
    }

}


