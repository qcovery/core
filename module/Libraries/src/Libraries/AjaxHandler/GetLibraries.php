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
namespace Libraries\AjaxHandler;

use Libraries\Selector;
use VuFind\AjaxHandler\AbstractBase;
use Zend\Mvc\Controller\Plugin\Params;
//use VuFind\Controller\AjaxController;
//use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * This controller handles global AJAX functionality
 *
 * @category VuFind2
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_a_controller Wiki
 */
class GetLibraries extends AbstractBase
{
    /**
     * Request
     *
     * @var Request
     */
    protected $request;



    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service locator
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the number of Books for a selected library
     *
     * @return \Zend\Http\Response
     */
    protected function getLibraryFacetsAjax()
    {
        $searchClassId = trim($_GET['searchclass']);

        $Selector = new Selector($this->request->getQuery());
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

/*-------------------------------------------------------------------------------*/


        $backend = $params->fromQuery('source', DEFAULT_SEARCH_BACKEND);
        $results = $this->resultsManager->get($backend);
        $paramsObj = $results->getParams();
        $paramsObj->initFromRequest(new Parameters(['lookfor' => 'hierarchy_top_id:'.$ppn]));

        $records = $results->getResults();
        $data = [];
        foreach ($records as $record) {
            $publishDates = $record->getPublicationDates();
            $data[] = ['id' => $record->getUniqueID(),
                       'title' => $record->getTitle(),
                       'publishDate' => $publishDates[0]];
        }
        return $this->formatResponse($data);



    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $searchClassId = $params->fromQuery('searchclass');
        $selector = new Selector();
        $selector->setLibraries();//$config und \VuFind\Search\Memory
        $selector->buildSelectorQuery($params->fromQuery('querystring');//requestObject; hier: Rückgabe nur ein Parameter Array
        $libraryData = $selector->getSelectorData($searchClassId);//searchService; hier: Rückgabe nur ein Parameter Array


    }
}


