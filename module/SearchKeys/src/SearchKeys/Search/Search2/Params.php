<?php

/**
 * Search Params for second Solr index
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category SearchKeys
 * @package  Search_Search2
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace SearchKeys\Search\Search2;

use VuFind\Search\QueryAdapter;

/**
 * Search Params for second Solr index
 *
 * @category VuFind
 * @package  Search_Search2
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Params extends \VuFind\Search\Search2\Params
{
    /**
     * Initialize the object's search settings from a request object.
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    protected function initSearch($request)
    {
        if (empty($request->get('overrideIds', null))) {
            $config = $this->configLoader->get('searchkeys');
            $keywords = $config->get('keys-solr');
            $phrasedKeywords = $config->get('phrasedKeys-solr');
            $toTranslate = $config->get('translate-solr');
            $isAdvancedSearch = false;
            $lookfor = $request->get('lookfor');

            $lookforArray = [];
            if (!empty($lookfor)) {
                $lookforArray = array($lookfor);
                $typeArray = array($request->get('type'));
            } elseif (!empty($lookfor0)) {
                $lookforArray = $request->get('lookfor0');
                $typeArray = $request->get('type0');
                $isAdvancedSearch = true;
            } else {
                parent::initSearch($request);
            }

            $searchItems = array();
            $searchTypes = array();
            $searchBooleans = array('AND');
            $limit = 10;
            foreach ($lookforArray as $lookfor) {
                $type = strval(array_shift($typeArray));
                if (empty($lookfor) || $lookfor == '""') {
                    continue;
                } elseif (!empty($type) && $type != $this->getOptions()->getDefaultHandler()) {
                    $searchItems[] = $lookfor;
                    if (isset($keywords[$type])) {
                        $searchTypes[] = $keywords[$type];
                    } else {
                        $searchTypes[] = $type;
                    }
                    continue;
                }
                $lookfor = preg_replace('/\s+/', ' ', $lookfor);
                if (is_array($toTranslate)) {
                    foreach($toTranslate as $translateTo => $translateFrom) {
                        $lookfor = preg_replace('/\\'.$translateFrom.'/', '{'.$translateTo.'}', $lookfor);
                    }
                }
                while (!empty($lookfor) && $limit-- > 0) {
                    $itemFound = false;
                    foreach ($phrasedKeywords as $keyword => $searchtype) {
                        $searchname = $this->getOptions()->getHumanReadableFieldName($searchtype);
                        $keyRegex = '(('.$keyword.'\s)|('.$searchtype.':)|('.$searchname.':))';
                        if (preg_match('#^'.$keyRegex.'([^"]+|("[^"]+"))((?=$))#', $lookfor, $matches)) {
                            $foundKey = $matches[1];
                            $newLookfor = trim(str_replace($foundKey, '', $lookfor));
                            $lookfor = '';
                            $searchItems[] = '"'.str_replace('"', '', $newLookfor).'"';
                            $searchTypes[] = $searchtype;
                            $itemFound = true;
                            $isAdvancedSearch = true;
                            break;
                        }
                    }
                    foreach ($keywords as $keyword => $searchtype) {
                        $searchname = $this->getOptions()->getHumanReadableFieldName($searchtype);
                        $keyRegex = '(('.$keyword.'\s)|('.$searchtype.':)|('.$searchname.':))';
                        if (preg_match('#^'.$keyRegex.'([^"\s]+|("[^"]+"))((?=\s)|(?=$))#', $lookfor, $matches)) {
                            $newLookfor = $matches[5];
                            $foundKey = $matches[1];
                            $lookfor = trim(str_replace($foundKey.$newLookfor, '', $lookfor));
                            $searchItems[] = $newLookfor;
                            $searchTypes[] = $searchtype;
                            $itemFound = true;
                            $isAdvancedSearch = true;
                            break;
                        }
                    }
                    if (!empty($lookfor) && !$itemFound) {
                       if (preg_match('#^([^"\s]+|("[^"]+"))((?=\s)|(?=$))#', $lookfor, $matches)) {
                            $newLookfor = $matches[1];
                            $lookfor = trim(preg_replace('#^'.$newLookfor.'#', '', $lookfor));
                            if ($newLookfor == 'OR') {
                                $searchBoolean = array($newLookfor);
                            } else {
                                $searchItems[] = $newLookfor;
                                $searchTypes[] = $this->getOptions()->getDefaultHandler();
                                $itemFound = true;
                            }
                        }
                    }
                }
                if (!$itemFound) {
                    $searchItems[] = $lookfor;
                    $searchTypes[] = $type;
                }
            }
            if ($isAdvancedSearch) {
                $request->set('lookfor0', null);
                $request->set('lookfor', null);
                if (count($searchItems) == count($searchTypes) && !empty($searchItems)) {
                    $request->set('lookfor0', $searchItems);
                    $request->set('type0', $searchTypes);
                    if (empty($request->get('bool0'))) {
                        $request->set('bool0', $searchBoolean);
                    }
                    if (empty($request->get('op0'))) {
                        $request->set('op0', array('AND'));
                    }
                    if (empty($request->get('join'))) {
                        $request->set('join', 'AND');
                    }
                }
            }
        }
        parent::initSearch($request);
    }

    /**
     * Build a string for onscreen display showing the
     *   query used in the search (not the filters).
     *
     * @return string user friendly version of 'query'
     */
    public function getDisplayQuery()
    {
        return $this->getRawQuery();
    }

    /**
     * Build a string for onscreen display showing the
     *   query used in the search (not the filters).
     *
     * @return string raw version of 'query'
     */
    public function getRawQuery()
    {
        // Build display query:
        $query = QueryAdapter::display($this->getQuery(), NULL, array($this, 'returnIdentic'));
        if (isset($translate)) {
            foreach($translate as $translateTo => $translateFrom) {
                $query = preg_replace('/{'.$translateTo.'}/', $translateFrom, $query);
            }
        }
        return str_replace(['(',')'], '', $query);
    }

    public function returnIdentic($item) {
        return $item;
    }

}
