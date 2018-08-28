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
use Libraries\Search\Results\PluginManager as ResultsManager;
use VuFind\Search\Memory;
use Libraries\Libraries;
use VuFind\AjaxHandler\AbstractBase;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Stdlib\Parameters;
use Zend\Config\Config;
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
     * Libraries
     *
     * @var Libraries
     */
    protected $Libraries;

    /**
     * ResultsManager
     *
     * @var ResultsManager
     */
    protected $resultsManager;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service locator
     */
    public function __construct(Config $config, ResultsManager $resultsManager, Memory $searchMemory)
    {
        $this->resultsManager = $resultsManager;
        $this->Libraries = new Libraries(
        	$config,
        	$searchMemory
        );
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
        $queryString = $params->fromQuery('querystring');
        $queryString = urldecode(
            str_replace('&amp;', '&',
                substr_replace(
                    trim($queryString), '', 0, 1
                )
            )
        );
        $queryArray = explode('&', $queryString);

        $searchParams = [];
        foreach ($queryArray as $queryItem) {
            $arrayKey = false;
            list($key, $value) = explode('=', $queryItem, 2);
            if (preg_match('/([0-9]\[\]$)/', $key, $matches)) {
                $key = str_replace($matches[1], '', $key);
                $arrayKey = true;
            }
            if ($key == 'library') {
                $libraryCode = $value;
            } else {
                if ($arrayKey) {
                    $searchParams[$key][] = $value;
                } else {
                    $searchParams[$key] = $value;
                }
            }
        }
        $backend = $params->fromQuery('source', DEFAULT_SEARCH_BACKEND);
        $selectedLibrary = $this->Libraries->selectLibrary($libraryCode);
        $locationFilter = $this->Libraries->getLocationFilter();
        $libraryFacet = array_shift($this->Libraries->getLibraryFacetFields($backend));
        $libraryCodes = array_flip($this->Libraries->getLibraryFacetValues($backend));
        $results = $this->resultsManager->get($backend);
        $paramsObj = $results->getParams();
        $paramsObj->addFacet($libraryFacet, null, false);
        if (!empty($locationFilter['field'])) {
            $paramsObj->addFacet($locationFilter['field'], null, false);
            $paramsObj->setFacetFieldPrefix($locationFilter['field'], $locationFilter['prefix']);
        }
        $paramsObj->setFacetLimit(2000); 
        $paramsObj->getOptions()->disableHighlighting();
        $paramsObj->getOptions()->spellcheckEnabled(false);
        
        $paramsObj->initFromRequest(new Parameters($searchParams));

        $facetList = $results->getFacetList();
        $libraryList = $facetList['collection_details']['list'];
        $locationList = $facetList['standort_iln_str_mv']['list'];

//print_r($facetList);
        $libraryData = [];
        foreach ($libraryList as $libraryItem) {
            $library = $this->Libraries->getLibrary($libraryItem['value']);
            $libraryData[$libraryItem['value']] = ['fullname' => $library['fullname'], 'count' => $libraryItem['count']];
        }
        $libraryData = array_intersect_key($libraryData, $libraryCodes);

        $locationFacets = [];
//print_r($locationList);
//print_r($iln);
        foreach ($locationList as $locationItem) {
            $locationFacets[$locationItem['value']] = $locationItem['count'];
        }
        $locationFacets = $this->Libraries->getLocationList($locationFacets);

        $data = [
            'libraryCount' => count($libraryData) + 2,
            'libraryData' => $libraryData,
            'locationFacets' => $locationFacets,
            'locationFilter' => ['field' => $locationFilter['field'], 'value' => ''],
        ];
        return $this->formatResponse($data);
    }

}


