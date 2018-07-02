<?php
/**
 * Libraries Module
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
 * @package  Libraries
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */
namespace Libraries;
use Libraries\Libraries;
use VuFind\Search\QueryAdapter;
use VuFindSearch\Query\Query;
use VuFindSearch\ParamBag;
use Zend\ServiceManager\ServiceLocatorInterface;

class Selector
{

    protected $Libraries;
    protected $requestObject;
    protected $searchService;
    protected $defaultLibraryCode = '';
    protected $libraryCode = '';
    protected $locationFacets = array();
    protected $locationValue = '';
    protected $locationFacet = '';
    protected $locationFacetPrefix = '';
    protected $selectorQuery;
    protected $counterTabCount = 0;

    public function __construct($requestObject)
    {
        $this->requestObject = $requestObject;
    }

    public function buildSelectorQuery($queryString) {
        $queryString = substr_replace(trim($queryString), '', 0, 1);
        $queryString = urldecode(str_replace('&amp;', '&', $queryString));
        $queryArray = explode('&', $queryString);
        $searchParams = array();

        foreach ($queryArray as $queryItem) {
            list($key, $value) = explode('=', $queryItem, 2);
            if (preg_match('/\[\]$/', $key)) {
                $key = str_replace('[]', '', $key);
                $searchParams[$key][] = $value;
            } elseif ($key == 'library') {
                $this->libraryCode = $value;
            } else {
                $this->requestObject->set($key, $value);
            }
        }

        $this->Libraries->selectLibrary($this->libraryCode);
        $locationFilter = $this->Libraries->getLocationFilter();
        $this->locationFacet = $locationFilter['field'];
        $this->locationFacetPrefix = $locationFilter['prefix'];

        if (count($searchParams) > 0) {
            foreach ($searchParams as $key => $values) {
                if ($key == 'filter' && !empty($this->locationFacet)) {
                    foreach ($values as $value) {
                        if (strpos($value, $this->locationFacet) === 0) {
                            $this->locationValue = $this->Libraries->getlocationValue($value);
                        } else {
                            $this->requestObject->set($key, $values);
                        }
                    }
                } else {
                    $this->requestObject->set($key, $values);
                }
            }
            $this->selectorQuery = QueryAdapter::fromRequest($this->requestObject, 'AllFields');
        } else {
            if (empty($this->requestObject->get('type'))) {
                $this->requestObject->set('type', 'AllFields');
            }
            $this->selectorQuery = new Query($this->requestObject->get('lookfor'), $this->requestObject->get('type'));
        }
    }

    public function getSelectorData($searchClassId) {
        if ($searchClassId == 'Primo') {
            return $this->getPrimoSelectorData();
        } elseif ($searchClassId == 'Findex') {
            return $this->getSolrSelectorData('Findex');
        } else {
            return $this->getSolrSelectorData();
        }
    }

    private function getSolrSelectorData($searchClassId = 'Solr') {
        $params = new ParamBag();

        $libraryFacet = array_shift($this->Libraries->getLibraryFacetFields($searchClassId));
        $libraryCodes = array_flip($this->Libraries->getLibraryFacetValues($searchClassId));
        $params->add('hl', 'false');
        $params->add('facet', 'true');
        $params->add('facet.mincount', 1);
        $params->add('facet.limit', 2000);
        $params->add('facet.sort', 'count');
        if (!empty($libraryFacet)) {
            $params->add('facet.field', $libraryFacet);
        }
        if (!empty($this->locationFacet)) {
            $params->add('facet.field', $this->locationFacet);
            if (!empty($this->locationFacetPrefix)) {
                $params->add('f.'.$this->locationFacet.'.facet.prefix', $this->locationFacetPrefix);
            }
        }
        $params->add('included_libraries', $this->Libraries->getLibraryFilters($this->defaultLibraryCode, $searchClassId, true, false));
        $params->add('excluded_libraries', $this->Libraries->getLibraryFilters($this->defaultLibraryCode, $searchClassId, false, false));

        $searchService = $this->serviceLocator->get('VuFind\Search');
        $result = $searchService->search($searchClassId, $this->selectorQuery, 0, 0, $params);

        $libraryData = array($this->defaultLibraryCode => array_merge(array('count' => $result->getTotal()), $this->Libraries->getLibrary($this->defaultLibraryCode)));

        $libraryCounts = array();

        $allFacets = $result->getFacets()->getFieldFacets()->getArrayCopy();

        $facets = array_intersect_key($allFacets[$libraryFacet]->toArray(), $libraryCodes);
        foreach ($facets as $value => $count) {
            $code = $libraryCodes[$value];
            $libraryCounts[$code] = $count;
        }

        foreach ($this->Libraries->getLibraryList(true, false, $searchClassId) as $code => $library) {
            $libraryCounts[$code] = (isset($libraryCounts[$code])) ? $libraryCounts[$code] : 0;
            $libraryData[$code] = array_merge(array('count' => $libraryCounts[$code]), $library);
        }

        if (!empty($this->locationFacet) && !empty($allFacets[$this->locationFacet])) {
            $this->locationFacets = $allFacets[$this->locationFacet]->toArray();
            $this->locationFacets = $this->Libraries->getLocationList($this->locationFacets);
        }
        $this->setCounterTabCount($params, 'Solr');
        return $libraryData;
    }

    private function getPrimoSelectorData() {
        $params = new ParamBag();
        $libraryFacet = array_shift($this->Libraries->getLibraryFacetFields('Primo'));
        $libraryCodes = array_flip($this->Libraries->getLibraryFacetValues('Primo'));
        $params->add('filterList', array('rtype' => array('Articles')));
        $params->add('included_libraries', $this->Libraries->getLibraryFilters($this->defaultLibraryCode, 'Primo', true, true));
        $params->add('excluded_libraries', $this->Libraries->getLibraryFilters($this->defaultLibraryCode, 'Primo', false, true));

        $searchService = $this->serviceLocator->get('VuFind\Search');
        $result = $searchService->search('Primo', $this->selectorQuery, 0, 0, $params);
        $libraryData = array($this->defaultLibraryCode => array_merge(array('count' => $result->getTotal()), $this->Libraries->getLibrary($this->defaultLibraryCode)));

        $libraryCounts = array();

        foreach ($this->Libraries->getLibraryFacetValues('Primo') as $code => $value) {
            $params->remove('included_libraries');
            $params->add('included_libraries', $this->Libraries->getLibraryFilters($code, 'Primo', true, true));
            $result = $searchService->search('Primo', $this->selectorQuery, 0, 0, $params);
            $libraryCounts[$code] = $result->getTotal();
        }

        foreach ($this->Libraries->getLibraryList(true, false, 'Primo') as $code => $library) {
            $libraryCounts[$code] = (isset($libraryCounts[$code])) ? $libraryCounts[$code] : 0;
            $libraryData[$code] = array_merge(array('count' => $libraryCounts[$code]), $library);
        }

        if (!empty($this->locationFacet) && !empty($allFacets[$this->locationFacet])) {
            $this->locationFacets = $allFacets[$this->locationFacet]->toArray();
            $this->locationFacets = $this->Libraries->getLocationList($this->locationFacets);
        }

        $this->setCounterTabCount($params, 'Primo');
        return $libraryData;
    }

    private function setCounterTabCount(ParamBag $params, $searchClassId) {
        $searchService = $this->serviceLocator->get('VuFind\Search');
        if ($searchClassId == 'Solr') {
            $this->requestObject->set('op0', array('contains'));
            $query = QueryAdapter::fromRequest($this->requestObject, 'AllFields');
            $params->add('included_libraries', $this->Libraries->getLibraryFilters($this->libraryCode, 'Primo', true, true));
            $params->add('excluded_libraries', $this->Libraries->getLibraryFilters($this->libraryCode, 'Primo', false, true));
            $params->add('filterList', array('rtype' => array('Articles')));
            $result = $searchService->search('Primo', $query, 0, 0, $params);
        } elseif ($searchClassId == 'Primo') {
            $params->add('included_libraries', $this->Libraries->getLibraryFilters($this->libraryCode, 'Solr', true, false));
            $params->add('excluded_libraries', $this->Libraries->getLibraryFilters($this->libraryCode, 'Solr', false, false));
            $result = $searchService->search('Solr', $this->selectorQuery, 0, 0, $params);
        }
        $this->counterTabCount = $result->getTotal();
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        $this->serviceLocator = $serviceLocator;
    }

    public function setLibraries() {
        $searchMemory = \VuFind\Service\Factory::getSearchMemory($this->serviceLocator);
        $this->Libraries = new Libraries($this->serviceLocator->get('VuFind\Config'), $searchMemory);
        $this->defaultLibraryCode = $this->Libraries->getDefaultLibraryCode();
        $this->libraryCode = $this->defaultLibraryCode;
    }

    public function getLocationFacets() {
        return $this->locationFacets;
    }

    public function getLocationFilter() {
        return array('field' => $this->locationFacet, 'value' => $this->locationValue);
    }

    public function getCounterTabCount() {
        return $this->counterTabCount;
    }
}

?>

