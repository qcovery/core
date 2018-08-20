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
use VuFind\Search\Results\PluginManager as ResultsManager;
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

        $libraryCode = $this->Libraries->getDefaultLibraryCode();

        $searchParams = [];
        foreach ($queryArray as $queryItem) {
            $arrayKey = false;
            list($key, $value) = explode('=', $queryItem, 2);
            if (strpos('[]', $key) > 0) {
                $key = str_replace('[]', '', $key);
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

        $searchClassId = trim($params->fromQuery('searchclass', 'Solr'));
        $this->Libraries->selectLibrary($libraryCode);
        $locationFilter = $this->Libraries->getLocationFilter();

        if (!empty($locationFilter) && !empty($searchParams['filter'])) {
            foreach ($searchParams['filter'] as $filter ) {
                if (strpos($filter, $locationFilter['field']) === 0) {
                    $locationFilter['value'] = $filter;
                }
            }
        }

        $searchParams['library'] = $libraryCode;
        $searchParams['included_libraries'] = $this->Libraries->getLibraryFilters($this->defaultLibraryCode, $searchClassId, true, false);
        $searchParams['excluded_libraries'] = $this->Libraries->getLibraryFilters($this->defaultLibraryCode, $searchClassId, false, false);

        $searchParams = array_merge(
            $searchParams,
            [
                'hl' => 'false',
                'facet' => 'true',
                'facet.mincount' => 1,
                'facet.limit' => 2000,
                'facet.sort' => 'count'
            ]
        );

        $libraryFacet = array_shift($this->Libraries->getLibraryFacetFields($searchClassId));
        $libraryCodes = array_flip($this->Libraries->getLibraryFacetValues($searchClassId));
        if (!empty($libraryFacet)) {
            $searchParams['facet.field'][] = $libraryFacet;
        }
        if (!empty($locationFilter)) {
            $searchParams['facet.field'][] = $locationFilter['facet'];
            if (!empty($locationFilter['prefix'])) {
                $searchParams['f.' . $locationFilter['facet'] . '.facet.prefix'] = $locationFilter['prefix'];
            }
        }

        $backend = $params->fromQuery('source', DEFAULT_SEARCH_BACKEND);
        $results = $this->resultsManager->get($backend);
        $paramsObj = $results->getParams();
        $paramsObj->addFacet($libraryFacet, null, false);
        //$paramsObj->addFacet($locationFilter['facet'], null, false);
        $paramsObj->initFromRequest(new Parameters($searchParams));

        $facets = $results->getFacetList();
//var_dump($facets);
/*
        $data = [];
        foreach ($records as $record) {
            $publishDates = $record->getPublicationDates();
            $data[] = ['id' => $record->getUniqueID(),
                       'title' => $record->getTitle(),
                       'publishDate' => $publishDates[0]];
        }
*/
        $data = $facets;
        return $this->formatResponse($data);


/*

        $this->Libraries->selectLibrary


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

-------------------------------------------------------------------------------



*/

    }

}


