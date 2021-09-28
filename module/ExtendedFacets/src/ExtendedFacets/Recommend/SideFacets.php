<?php
/**
 * SideFacets Recommendations Module
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
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace ExtendedFacets\Recommend;

/**
 * SideFacets Recommendations Module
 *
 * This class provides recommendations displaying facets beside search results
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class SideFacets extends \VuFind\Recommend\SideFacets
{
    /**
     * getYearFacets
     *
     * Return year facet information in a format processed for use in the view.
     *
     * @param array $oldFacetList list of facets, $label filterlabel.
     *
     * @return array list of facets
     */
    public function getYearFacets($oldFacetList, $label)
    {
        array_multisort($oldFacetList, SORT_DESC);
        $minYear = $oldFacetList[count($oldFacetList)-1]['value'];
        $maxYear = $oldFacetList[0]['value'];
        $facetListAssoc = array();
        foreach ($oldFacetList as $oldFacetListItem) {
            $facetListAssoc[$oldFacetListItem['value']] = $oldFacetListItem['count'];
        }
        $newFacetList = array();

        $filters = $this->results->getParams()->getFilterList();
        if (isset($filters[$label])) {
            $lastYearFilter = array_pop($filters[$label]);
            list($filteredMinYear,$filteredMaxYear) = explode(' TO ',str_replace(array('[', ']'), '', $lastYearFilter['value']));
            $displayText = ($filteredMaxYear <= date('Y')) ? $filteredMinYear.'-'.$filteredMaxYear : $filteredMinYear.'-';
            $filteredYearFacet = array('value' => '['.$filteredMinYear.' TO '.$filteredMaxYear.']', 'displayText' => $displayText, 'count' => 1, 'operator' => 'AND', 'isApplied' => true);
            if ($minYear < $filteredMinYear) {
                $minYear = $filteredMinYear;
            }
            if ($maxYear > $filteredMaxYear) {
                $maxYear = $filteredMaxYear;
            }
        }

        foreach (array(100, 10, 1) as $scale) {
            if (floor($minYear/$scale) != floor($maxYear/$scale)) {
                for ($year = $scale*floor($minYear/$scale); $year <= $scale*floor($maxYear/$scale); $year += $scale) {
                    $newCount = 0;
                    for ($y=$year; $y < $year + $scale; $y++) {
                        if (isset($facetListAssoc[$y])) {
                            $newCount += $facetListAssoc[$y];
                        }
                    }
                    if ($newCount > 0) {
                        if ($scale == 1) {
                            $displayText = $year;
                        } else {
                            $displayText = ($year + $scale - 1 <= date('Y')) ? $year.'-'.($year + $scale - 1) : $year.'-';
                        }
                        $newFacetList[] = array('value' => '['.$year.' TO '.($year + $scale - 1).']', 'displayText' => $displayText, 'count' => $newCount, 'operator' => 'AND', 'isApplied' => false);
                    }
                }
                krsort($newFacetList);
                $newFacetList = array_values($newFacetList);
                if (isset($filteredYearFacet)) {
                    array_unshift($newFacetList, $filteredYearFacet);
                }
                return $newFacetList;
            }
        }
        if (isset($filteredYearFacet)) {
            array_unshift($newFacetList, $filteredYearFacet);
        }
        return $newFacetList;
    }

    protected function getFormatFacets($oldFacetList)
    {
        $facetList = $tmpFacetList = [];
        foreach ($oldFacetList as $index => $facet) {
            $i = 10 * $index;
            if (strpos($facet['value'], ' ') > 0) {
                list($fType, $fName) = explode(' ', $facet['value']);
                if (in_array($fName,array_keys($tmpFacetList))) {
                    $i = ++$tmpFacetList[$fName];
                    $facet['indent'] = 'y';
                }
                $facetList[$i] = $facet;
            } else {
                $tmpFacetList[$facet['value']] = $i;
                $facetList[$i] = $facet;
            }
        }
        ksort($facetList,SORT_NUMERIC);
        return array_values($facetList);
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        parent::setConfig($settings);
        $config = $this->configLoader->get('facets');
        if (isset($config->SpecialFacets->indentedFacets)) {
            $this->indentedFacets = $config->SpecialFacets->indentedFacets->toArray();
        }
        if (isset($config->SpecialFacets->numberDrillFacets)) {
            $this->numberDrillFacets = $config->SpecialFacets->numberDrillFacets->toArray();
        }
    }

     /**
     * Get facet information from the search results.
     *
     * @return array
     */
    public function getFacetSet()
    {
        $facetSet = parent::getFacetSet();
        foreach ($this->numberDrillFacets as $facetName) {
            if (isset($facetSet[$facetName])) {
                $facetSet[$facetName]['list'] = $this->getYearFacets($facetSet[$facetName]['list'], $facetSet[$facetName]['label']);
            }
        }
        foreach ($this->indentedFacets as $facetName) {
            if (isset($facetSet[$facetName])) {
                $facetSet[$facetName]['list'] = $this->getFormatFacets($facetSet[$facetName]['list']);
            }
        }
        return $facetSet;
    }
}
