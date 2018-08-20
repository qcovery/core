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
//use Zend\ServiceManager\ServiceManager;
use VuFind\Search\Memory;
use Zend\Session\Container as SessionContainer;
use Zend\Config\Config;

class Libraries
{

    protected $includedLibraries;
    protected $excludedLibraries;
    protected $defaultLibraries;
    protected $selectedLibrary;
    protected $locations;
    protected $searchMemory;

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

            }
        }
        uasort($this->includedLibraries, function($a, $b) {return strcmp($a['sort'], $b['sort']);});
        $this->selectLibrary();
    }

    private function checkLibrary($libraryCode) {
        return (!empty($libraryCode)
                && (in_array($libraryCode, array_keys($this->includedLibraries))
                    || in_array($libraryCode, array_keys($this->defaultLibraries)))
                );
    }

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

    public function getDefaultLibraryCode($searchClassId = null) {
        $codes = [];
        foreach ($this->defaultLibraries as $library => $data) {
            if (empty($searchClassId) || isset($data[$searchClassId])){
                $codes[] = $data['code'];
            }
        }
        return array_shift($codes);
    }

    public function getLibraryCodes($searchClassId = null) {
        $searchClassId = strtolower($searchClassId);
        $codes = [];
        foreach ($this->includedLibraries as $library => $data) {
            if (empty($searchClassId) || isset($data[$searchClassId])){
                $codes[] = $data['code'];
            }
        }
        return $codes;
    }

    public function getLibraryFacetFields($searchClassId) {
        $facets = [];
        $searchClassId = strtolower($searchClassId);
         foreach ($this->includedLibraries as $library) {
            if (isset($library[$searchClassId.'-field'])) {
                $facets[] = $library[$searchClassId.'-field'];
            }
        }
        return array_unique($facets);
    }

    public function getLibraryFacetValues($searchClassId) {
        $codes = [];
        $searchClassId = strtolower($searchClassId);
         foreach ($this->includedLibraries as $library => $data) {
            if (isset($data[$searchClassId])) {
                $codes[$library] = $data[$searchClassId];
            }
        }
        return $codes;
    }

    public function getLibraryFilters($libraryCode, $searchClassId, $included = true, $getDefaultInsteadOfAll = false) {
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
                if (!empty($data[$searchClassId.'-field'])) {
                    $libraryFilters[] = $data[$searchClassId.'-field'].':'.$data[$searchClassId];
                } else {
                    $libraryFilters[] = $data[$searchClassId];
                }
            }
        } else {
            foreach ($libraries as $library => $data) {
                if (!empty($data[$searchClassId])) {
                    if (!empty($data[$searchClassId.'-field'])) {
                        $libraryFilters[] = $data[$searchClassId.'-field'].':'.$data[$searchClassId];
                    } else {
                        $libraryFilters[] = $data[$searchClassId];
                    }
                }
            }
        }
        return $libraryFilters;
    }

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

    public function getLocationValue($filter) {
        if (isset($this->locations[$this->selectedLibrary['code']])) {
            $locations = $this->locations[$this->selectedLibrary['code']];
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
        return null;
    }

    public function getLocationList($locationFacets) {
        $locationList = array();
        if (isset($this->locations[$this->selectedLibrary['code']])) {
            $locations = $this->locations[$this->selectedLibrary['code']];
            foreach ($locationFacets as $facetKey => $facetValue) {
                foreach ($locations as $key => $value) {
                    if (in_array($key, array('code', 'solr-field', 'filter-prefix'))) {
                        continue;
                    }
                    if (!empty($locations['filter-prefix'])) {
                        $key = $locations['filter-prefix'].$key;
                    }
                    if (preg_match('#^'.$key.'$#', $facetKey)) {
                        if (!isset($locationList[$value])) {
                            $filterList[$value] = array();
                            $locationList[$value] = array('count' => 0);
                        }
                        $locationList[$value]['count'] += intval($facetValue);
                        if (strpos($key, ' ') !== false) {
                            $key = '"'.$key.'"';
                        }
                        $filterList[$value][] = $locations['solr-field'].':'.str_replace(array('.', '/', ':'), array('', '\/', '\:'), $key);
                        break;
                    }
                }
            }
            foreach ($locationList as $value => $data) {
                $locationList[$value]['filter'] = implode('+OR+', array_unique($filterList[$value]));
            }
        }
        uasort($locationList, function($a, $b) {return $b['count'] - $a['count'];});
        return $locationList;
    }
}
