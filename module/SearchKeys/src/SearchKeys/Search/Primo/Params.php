<?php
/**
 * Primo aspect of the Search Multi-class (Params)

**/

namespace SearchKeys\Search\Primo;

use VuFind\Search\QueryAdapter;

class Params extends \VuFind\Search\Primo\Params
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
            $keywords = $config->get('keys-primo');
            $phrasedKeywords = $config->get('phrasedKeys-primo');
            $toTranslate = $config->get('translate-primo');
            $isAdvancedSearch = false;

            $lookfor = $request->get('lookfor');
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
            $limit = 10;
            foreach ($lookforArray as $lookfor) {
                $type = strval(array_shift($typeArray));
                if (empty($lookfor) || $lookfor == '""') {
                    continue;
                } elseif (!empty($type) && $type != $this->getOptions()->getDefaultHandler()) {
                    $searchItems[] = $lookfor;
                    $searchTypes[] = $type;
                    continue;
                }
                $lookfor = preg_replace('/\s+/', ' ', $lookfor);
                foreach($toTranslate as $translateTo => $translateFrom) {
                    $lookfor = preg_replace('/\\'.$translateFrom.'/', '{'.$translateTo.'}', $lookfor);
                }
                while (!empty($lookfor) && $limit-- > 0) {
                    $itemFound = false;
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
                            $searchItems[] = $newLookfor;
                            $searchTypes[] = $this->getOptions()->getDefaultHandler();
                            $itemFound = true;
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
                    foreach ($phrasedKeywords as $keyword => $searchtype) {
                        for ($i = 0; $i < count($searchItems); $i++) {
                            if ($searchTypes[$i] == $searchtype) {
                                $searchItems[$i] = '"'.str_replace('"', '', $searchItems[$i]).'"';
                            }
                        }
                    }
                    $request->set('lookfor0', $searchItems);
                    $request->set('type0', $searchTypes);
                    if (empty($request->get('bool0'))) {
                        $request->set('bool0', array('AND'));
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
        $config = $this->configLoader->get('searchkeys');
        $translate = $config->get('translate-primo');
        $query = QueryAdapter::display($this->getQuery(), NULL, array($this, 'returnIdentic'));
        foreach($toTranslate as $translateTo => $translateFrom) {
            $query = preg_replace('/{'.$translateTo.'}/', $translateFrom, $query);
        }
        return str_replace(['(',')'], '', $query);
    }

    public function returnIdentic($item) {
        return $item;
    }

}

