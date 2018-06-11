<?php
/**
 * Helper class for performing Searchkeys
 *
**/

namespace SearchKeys\Search;

class SearchKeysHelper
{

    public function test()
    {
        return 'TeSt';
    }

    /**
     * Analyzing search keys within search request and adjusting request accordingly
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     * @param \VuFind\Config $config Configuration object
     *
     * @return \Zend\StdLib\Parameters $request
     */
    public function processSearchKeys($request, $options, $config)
    {
        $keywords = $config->get('keys-solr');
        $phrasedKeywords = $config->get('phrasedKeys-solr');
        $toTranslate = $config->get('translate-solr');
        $isAdvancedSearch = false;
        $lookfor = $request->get('lookfor');
        if (!empty($lookfor)) {
            $lookforArray = array($lookfor);
            //$typeArray = array($request->get('type'));
            $typeArray = array();
        } elseif (!empty($lookfor0)) {
            $lookforArray = $request->get('lookfor0');
            $typeArray = $request->get('type0');
            $isAdvancedSearch = true;
        } else {
            parent::initSearch($request);
        }

        $searchItems = array();
        $searchTypes = array();
        $searchBoolean = array('AND');
        $limit = 10;
        foreach ($lookforArray as $lookfor) {
            $type = strval(array_shift($typeArray));
            if (empty($lookfor) || $lookfor == '""') {
                continue;
            } elseif (!empty($type) && $type != $options->getDefaultHandler()) {
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
                    $searchname = $options->getHumanReadableFieldName($searchtype);
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
                    $searchname = $options->getHumanReadableFieldName($searchtype);
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
                            $searchTypes[] = $options->getDefaultHandler();
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
                    $request->set('join', 'OR');
                }
            }
        }
        return $request;
    }
}
