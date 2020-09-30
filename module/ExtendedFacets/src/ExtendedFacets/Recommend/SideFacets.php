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

use VuFind\Search\Solr\HierarchicalFacetHelper;

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
     * Translator
     *
     * @var \Zend\I18n\Translator\Translator
     */
    protected $translator = null;

    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager $configLoader Configuration loader
     * @param HierarchicalFacetHelper      $facetHelper  Helper for handling
     * @param \Zend\Mvc\I18n\Translator    $translator   Translator
     * hierarchical facets
     */
    public function __construct(
      \VuFind\Config\PluginManager $configLoader,
      HierarchicalFacetHelper $facetHelper = null,
      \Zend\Mvc\I18n\Translator $translator
    ) {
      parent::__construct($configLoader);
      $this->hierarchicalFacetHelper = $facetHelper;
      $this->translator = $translator;
    }

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

    /**
     * Process show facet value
     *
     * @return array
     */
    protected function showFacetValue($facetSet)
    {
        $facetSettings = $this->configLoader->get('facets');

        if ($facetSettings->ShowFacetValue && is_array($facetSettings->ShowFacetValue->toArray())) {
            foreach ($facetSettings->ShowFacetValue as $showFacet => $showFacetValues) {
                if (isset($facetSet[$showFacet]['list'])) {
                    foreach ($facetSet[$showFacet]['list'] as $facet => $value) {
                        if (!in_array($value['value'], $showFacetValues->toArray())) {
                            unset($facetSet[$showFacet]['list'][$facet]);
                        }
                    }
                }
            }
        }

        return $facetSet;
    }

    /**
     * getFacetHierarchies
     *
     * Return dependency informations on facets.
     *
     * @param array $oldFacetList list of facets, $label filterlabel.
     *
     * @return array list of facets
     */
    public function getFacetHierarchies($oldFacetList, $label)
    {
        $facetLength = count($oldFacetList);
        $newFacetList = array();
        for ($i = 0; $i < $facetLength; $i++) {
            if (isset($oldFacetList[$i])) {
                $newFacetList[] = $oldFacetList[$i];
            }
            $value = $oldFacetList[$i]['value'];
            for ($j = $i+1; $j < $facetLength; $j++) {
                if (strpos($oldFacetList[$j]['value'], $value) > 0) {
                    $oldFacetList[$j]['parent'] = $value;
                    $newFacetList[] = $oldFacetList[$j];
                    unset($oldFacetList[$j]);
                }
            }
        }
        return $newFacetList;
    }

    /**
     * getLocationFacets
     *
     * Return location facet information in a format processed for use in the view.
     *
     * @param array $oldFacetList list of facets, $label filterlabel.
     *
     * @return array list of facets
     */
    public function getLocationFacets($sigelFacetList, $sigelLabel) {
        $filters = $this->results->getParams()->getFilterList();

        $tmpFacetList = array();
        $filterList = array();
        $isAppliedGlobal = false;
        foreach ($sigelFacetList as $sigelFacetItem) {
            $displayText = $this->translator->translate($sigelFacetItem['displayText']);

            $sigelFacetItemCropped = preg_replace('/-[A-z]+$/', '', $sigelFacetItem['displayText']);
            if ($displayText == $sigelFacetItem['displayText']) {
                $displayText = $this->translator->translate($sigelFacetItemCropped);
            }
            if ($displayText != $sigelFacetItem['displayText'] && $displayText != $sigelFacetItemCropped) {
                if (isset($tmpFacetList[$displayText])) {
                    $tmpFacetList[$displayText]['sort'] += $sigelFacetItem['count'];
                    $tmpFacetList[$displayText]['count'] += $sigelFacetItem['count'];
                } else {
                    $isApplied = (strpos($filters[$sigelLabel][0]['value'], $sigelFacetItem['value']) !== false);
                    $tmpFacetList[$displayText] = array('sort' => $sigelFacetItem['count'], 'value' => $sigelFacetItem['value'], 'displayText' => $displayText, 'count' => $sigelFacetItem['count'], 'operator' => 'AND', 'isApplied' => $isApplied);
                    if ($isApplied) {
                        $isAppliedGlobal = true;
                    }
                }
                if (isset($filterList[$displayText])) {
                    $filterList[$displayText]['value'] .= ' OR standort_iln_str_mv:"'.$sigelFacetItem['value'].'"';
                } else {
                    $filterList[$displayText]['value'] = 'complex:standort_iln_str_mv:"'.$sigelFacetItem['value'].'"';
                }
            }
        }
        foreach ($tmpFacetList as $name => $data) {
            if (isset($filterList[$name]['value'])) {
                $tmpFacetList[$name]['value'] = $filterList[$name]['value'];
            }
        }
        array_multisort($tmpFacetList, SORT_DESC);
        $newFacetList = array();
        foreach ($tmpFacetList as $tmpFacetItem) {
            if (!$isAppliedGlobal || $tmpFacetItem['isApplied']) {
                $newFacetList[] = $tmpFacetItem;
            }
        }
        return $newFacetList;
    }

    /**
     * Get facet information from the search results.
     *
     * @return array
     */
    public function getFacetSet()
    {
        $config = $this->configLoader->get('facets');

        $facetSet = \VuFind\Recommend\SideFacets::getFacetSet();
        if (isset($facetSet['publishDate'])) {
            $facetSet['publishDate']['list'] = $this->getYearFacets($facetSet['publishDate']['list'], $facetSet['publishDate']['label']);
        }
        
        if ($config->SideFacetsExtras->format_facet) {
            if (isset($facetSet['format_facet'])) {
              $facetSet['format_facet']['list'] = $this->getFacetHierarchies($facetSet['format_facet']['list'], $facetSet['format_facet']['label']);
            }
        }

        if ($config->SideFacetsExtras->standort_iln_str_mv) {
            if (isset($facetSet['standort_iln_str_mv'])) {
              $facetSet['standort_iln_str_mv']['list'] = $this->getLocationFacets($facetSet['standort_iln_str_mv']['list'], $facetSet['standort_iln_str_mv']['label']);
            }
        }

        $facetSet = $this->showFacetValue($facetSet);

        return $facetSet;
    }
}
