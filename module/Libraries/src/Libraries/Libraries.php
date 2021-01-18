<?php
/**
 * Module Libraries: basic class
 *
 * PHP version 7
 *
 * Copyright (C) Staats- und Universitätsbibliothek Hamburg 2018.
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
 * @category VuFind
 * @package  Libraries
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/beluga-core
 */
namespace Libraries;

use VuFind\Search\Memory;
use Laminas\Session\Container as SessionContainer;
use Laminas\Config\Config;

class Libraries
{

    /**
     * Libraries to include
     *
     * @var array
     */
    protected $includedLibraries;

    /**
     * Libraries to exclude
     *
     * @var array
     */
    protected $excludedLibraries;

    /**
     * Default library (if none is chosen)
     * Default library could and should be sth like "all libraries"
     *
     * @var array
     */
    protected $defaultLibraries;

    /**
     * Current library
     *
     * @var array
     */
    protected $selectedLibrary;

    /**
     * Locations of a library
     *
     * @var array
     */
    protected $locations;

    /**
     * Search memory
     *
     * @var VuFind\Search\Memory
     */
    protected $searchMemory;

    /**
     * Data for external links to libraries
     *
     * @var array
     */
    protected $externalLinkData;

    /**
     * Constructor
     *
     * @param Config        $config                Configuration of Libraries
     * @param Memory        $searchMemory          Query object to use to update
     */
    public function __construct(Config $config, Memory $searchMemory = null)
    {
        $this->searchMemory = $searchMemory;
        $this->includedLibraries = array();
        $this->excludedLibraries = array();
        $this->defaultLibraries = array();

        foreach ($config as $dataObject) {
            $data = $dataObject->toArray();
            $libraryCode = $data['code'];
            if ($data['action'] == 'include') {
                $this->includedLibraries[$libraryCode] = $data;
            } elseif ($data['action'] == 'exclude') {
                $this->excludedLibraries[$libraryCode] = $data;
            } elseif ($data['action'] == 'default') {
                $this->defaultLibraries[$libraryCode] = $data;
            } elseif ($data['action'] == 'location') {
                $this->locations[$libraryCode] = array();
                foreach ($data as $key => $value) {
                    if ($key != 'action') {
                        $this->locations[$libraryCode][$key] = $value;
                    }
                }
            } elseif ($data['action'] == 'externalLink') {
                $this->$externalLinkData[$libraryCode] = $data;
            }
        }
        uasort($this->includedLibraries, function($a, $b) {return strcmp($a['sort'], $b['sort']);});
        $this->selectLibrary();
    }

    /**
     * Check if a identifying code is valid 
     *
     * @param string        $libraryCode           code
     *
     * @return boolean
     */
    private function checkLibrary($libraryCode) {
        return (!empty($libraryCode)
                && (in_array($libraryCode, array_keys($this->includedLibraries))
                    || in_array($libraryCode, array_keys($this->defaultLibraries)))
                );
    }

    /**
     * Select a current library and return according data
     *
     * @param string        $libraryCode           code
     *
     * @return array
     */
    public function selectLibrary($libraryCode = null) {
        $validatedLibraryCode = null;
        if ($this->checkLibrary($libraryCode)) {
            $validatedLibraryCode = $libraryCode;
        } else {
            $libraryCode = (isset($_GET['library'])) ? $_GET['library'] : '';
            if ($this->checkLibrary($libraryCode)) {
                $validatedLibraryCode = $libraryCode;
            } else {
                if (!empty($this->searchMemory)) {
                    $libraryCode = $this->searchMemory->retrieveLastSetting('Libraries', 'selectedLibrary'); 
                }
                if ($this->checkLibrary($libraryCode)) {
                    $validatedLibraryCode = $libraryCode;
                } else {
                    $default = (count($this->defaultLibraries) > 0) ? array_keys($this->defaultLibraries) : array_keys($this->includedLibraries);
                    $validatedLibraryCode = array_shift($default);
                }
            }
        }
        if (!empty($validatedLibraryCode)) {
            $this->selectedLibrary = $this->getLibrary($validatedLibraryCode);
        }
        if (!empty($this->searchMemory)) {
            $this->searchMemory->rememberLastSettings('Libraries', array('selectedLibrary' => $this->selectedLibrary['code']));
        }
        return $this->selectedLibrary;
    }

    /**
     * Get the library data
     *
     * @param string        $libraryCode           code
     *
     * @return array
     */
    public function getLibrary($libraryCode = '') {
        if (empty($libraryCode)) {
            return $this->selectLibrary();
        } elseif (isset($this->defaultLibraries[$libraryCode])) {
            return $this->defaultLibraries[$libraryCode];
        } elseif (isset($this->includedLibraries[$libraryCode])) {
            return $this->includedLibraries[$libraryCode];
        } elseif (isset($this->excludedLibraries[$libraryCode])) {
            return $this->excludedLibraries[$libraryCode];
        } else {
            return null;
        }
    }

    /**
     * Get the code of the default library
     *
     * @param string        $searchClassId           Searchclass id
     *
     * @return string
     */
    public function getDefaultLibraryCode($searchClassId = null) {
        $searchClassId = strtolower($searchClassId);
        $codes = [];
        foreach ($this->defaultLibraries as $library) {
            if (empty($searchClassId) || isset($library[$searchClassId])){
                $codes[] = $library['code'];
            }
        }
        return array_shift($codes);
    }

    /**
     * Get the codes of all includes libraries
     *
     * @param string        $searchClassId           Searchclass id
     *
     * @return array
     */
    public function getLibraryCodes($searchClassId = 'solr') {
        $searchClassId = strtolower($searchClassId);
        $codes = [];
        foreach (array_merge($this->defaultLibraries, $this->includedLibraries) as $library) {
            if (isset($library[$searchClassId])){
                $codes = array_merge($codes, explode(',', $library[$searchClassId]));
            }
        }
        return $codes;
    }

    /**
     * Get the facet fields identifying includes libraries
     *
     * @param string        $searchClassId           Searchclass id
     *
     * @return array
     */
    public function getLibraryFacetField($searchClassId) {
        $searchClassId = strtolower($searchClassId);
        $selectedLibrary = $this->selectLibrary();
        if (isset($selectedLibrary[$searchClassId.'-field'])) {
            return $selectedLibrary[$searchClassId.'-field'];
        }
        return '';
    }

    /**
     * Get the facet values identifying includes libraries
     *
     * @param string        $searchClassId           Searchclass id
     *
     * @return array
     */
    public function getLibraryFacetValues($searchClassId) {
        $searchClassId = strtolower($searchClassId);
        $values = [];
         foreach (array_merge($this->defaultLibraries, $this->includedLibraries) as $library => $data) {
            if (isset($data[$searchClassId])) {
                $values[$library] = $data[$searchClassId];
            }
        }
        return $values;
    }

    /**
     * Get the facet values identifying includes libraries
     *
     * @param string        $searchClassId           Searchclass id
     *
     * @return array
     */
    public function getFacetSearch($searchClassId) {
        $searchClassId = strtolower($searchClassId);
         foreach (array_merge($this->defaultLibraries, $this->includedLibraries) as $library => $data) {
            if (isset($data[$searchClassId.'-facetsearch'])) {
                return $data['code'];
            }
        }
        return '';
    }

    /**
     * Generate the filters to select libraries
     *
     * @param string        $libraryCode             code of the selected library
     * @param string        $searchClassId           Searchclass id
     * @param boolean       $included                whether to select included or excluded libraries
     * @param boolean       $getDefaultInsteadOfAll  whether to select default library or all libraries if
     *                                                no library is chosen 
     *
     * @return array
     */
    public function getLibraryFilters($libraryCode = '', $searchClassId, $included = true, $getDefaultInsteadOfAll = false) {
        $libraryFilters = [];
        $searchClassId = strtolower($searchClassId);
        $libraries = ($included) ? $this->includedLibraries : $this->excludedLibraries;
        if ($getDefaultInsteadOfAll && (empty($libraryCode) || !in_array($libraryCode, array_keys($libraries)))) {
            if (!$included) {
                return $libraryFilters;
            }
            $libraries = $this->defaultLibraries;
            $libraryCode = $this->getDefaultLibraryCode();
        }
        if (!empty($libraryCode) && in_array($libraryCode, array_keys($libraries))) {
            $data = $libraries[$libraryCode];
            if (!empty($data[$searchClassId])) {
                $filterValues = explode(',', $data[$searchClassId]);
                if (!empty($data[$searchClassId.'-field'])) {
                    foreach ($filterValues as $filterValue) {
                        $libraryFilters[] = $data[$searchClassId.'-field'] . ':' . $filterValue;
                    }
                } else {
                    foreach ($filterValues as $filterValue) {
                        $libraryFilters[] = $filterValue;
                    }
                }
            }
        } else {
            foreach ($libraries as $library => $data) {
                if (!empty($data[$searchClassId])) {
                    $filterValues = explode(',', $data[$searchClassId]);
                    if (!empty($data[$searchClassId.'-field'])) {
                        foreach ($filterValues as $filterValue) {
                            $libraryFilters[] = $data[$searchClassId.'-field'] . ':' . $filterValue;
                        }
                    } else {
                        foreach ($filterValues as $filterValue) {
                            $libraryFilters[] = $filterValue;
                        }
                    }
                }
            }
        }
        return $libraryFilters;
    }

    /**
     * Get a list of all libraries
     *
     * @param boolean       $includedOnly            whether to select only included libraries
     * @param boolean       $withDefault             whether to select default library as well
     * @param string        $searchClassId           Searchclass id
     *
     * @return array
     */
    public function getLibraryList($includedOnly = true, $withDefault = true, $searchClassId = '') {
        $searchClassId = strtolower($searchClassId);
        if ($withDefault) {
            $libraryList = ($includedOnly) ? array_merge($this->defaultLibraries, $this->includedLibraries) : array_merge($this->defaultLibraries, $this->includedLibraries, $this->excludedLibraries);
        } else {
            $libraryList = ($includedOnly) ? $this->includedLibraries : array_merge($this->includedLibraries, $this->excludedLibraries);
        }
        if ($searchClassId != '') {
            foreach ($libraryList as $key => $library) {
                if (!isset($library[$searchClassId])) {
                    unset($libraryList[$key]);
                }
            }
        }
        return $libraryList;
    }

    /**
     * Get filter values for locations
     *
     * @return array
     */
    public function getLocationFilter() {
        if (isset($this->locations[$this->selectedLibrary['code']])) {
            $locations = $this->locations[$this->selectedLibrary['code']];
            return array(
               'field' => $locations['solr-field'],
               'prefix' => $locations['filter-prefix']
            );
        }
        return null;
    }

    /**
     * Determine a proper filter value by a filter
     *
     * @return array
     */
    public function getLocationValue($filter) {
        if (isset($this->locations[$this->selectedLibrary['code']])) {
            $locations = $this->locations[$this->selectedLibrary['code']];
            $filter = str_replace(['(', ')'], '', $filter);
            list($field, $filterString) = explode(':', $filter, 2);
            if ($locations['solr-field'] == $field) {
                list($rawFilter, ) = explode('OR', $filterString, 2);
                $filterValue = str_replace(array('*', '\/', '\:'), array('.*', '/', ':'), trim($rawFilter, ' +"'));
                if (!empty($locations['filter-prefix'])) {
                    $filterValue = str_replace($locations['filter-prefix'], '', $filterValue);
                }
                $locationValue = $locations[$filterValue];
                if (isset($locationValue)) {
                    return $locationValue;
                }
            }
        }
        return $filter;
    }

    /**
     * Get a list of locations (to build a location facet)
     *
     * @return array
     */
    public function getLocationList($locationFacets) {
        $locationList = array();
        if (isset($this->locations[$this->selectedLibrary['code']])) {
            $locations = $this->locations[$this->selectedLibrary['code']];
            foreach ($locationFacets as $facetKey => $facetValue) {
                foreach ($locations as $key => $value) {
                    if (in_array($key, ['code', 'solr-field', 'filter-prefix'])) {
                        continue;
                    }
                    if (!empty($locations['filter-prefix'])) {
                        $key = $locations['filter-prefix'].$key;
                    }
                    if (preg_match('#^'.$key.'$#', $facetKey)) {
                        if (!isset($locationList[$value])) {
                            $filterList[$value] = [];
                            $locationList[$value] = ['count' => 0];
                        }
                        $locationList[$value]['count'] += intval($facetValue);
                        $key = str_replace([':', '/'], ['\:', '\/'], $key);
                        if (strpos($key, ' ') !== false) {
                            $key = '"'.$key.'"';
                        }
                        $filterList[$value][] = $locations['solr-field'].':'.str_replace('.', '', $key);
                        break;
                    }
                }
            }
            foreach ($locationList as $value => $data) {
                $locationList[$value]['filter'] = '(' . implode('+OR+', array_unique($filterList[$value])) . ')';
            }
        }
        uasort($locationList, function($a, $b) {return $b['count'] - $a['count'];});
        return $locationList;
    }

    public function getLibraryLinkData ($libraryCode) {
        return $this->$externalLinkData[$libraryCode];
    }
}
