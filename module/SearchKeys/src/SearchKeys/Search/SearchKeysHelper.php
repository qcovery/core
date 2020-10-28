<?php
/**
 * Helper class for performing Searchkeys
 *
**/

namespace SearchKeys\Search;

class SearchKeysHelper
{
    /**
     * Analyzing search keys within search request and adjusting request accordingly
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     * @param \VuFind\Config $config Configuration object
     *
     * @return \Zend\StdLib\Parameters $request
     */
    public function processSearchKeys($request, $options, $config, $searchClassId)
    {
//return $request;
        $id = strtolower($searchClassId);
        $keywords = [];
        if ($config->get('keys-' . $id)) {
            $keywords = $config->get('keys-' . $id)->toArray();
        }
        $phrasedKeywords = [];
        if ($config->get('phrasedKeys-' . $id)) {
            $phrasedKeywords = $config->get('phrasedKeys-' . $id)->toArray();
        }
        $hiddenKeywords = [];
        if ($config->get('hiddenKeys-' . $id)) {
            $hiddenKeywords = $config->get('hiddenKeys-' . $id)->toArray();
        }

        $booleans = ['OR' => ['OR', 'ODER'], 'AND' => ['AND', 'UND'], 'NOT' => ['NOT', 'NICHT']];

        $defaultType = $options->getDefaultHandler();

        $lookfor = trim(preg_replace('/\s+/', ' ', $request->get('lookfor')));
        $lookfor = preg_replace('/""+/', '"', $lookfor);
        $type = $request->get('type');

        $searchItems = [];
        $searchBoolean = 'AND';
        $join = 'OR';
        $limit = 30;

        $newQG = false;
        $qg = 0;

        while (!empty($lookfor) && $limit-- > 0) {
            $item = $key = '';
            if ($newQG && preg_match('#^[^(): ]+\)#', $lookfor)) {
                $lookfor = preg_replace('#^([^()]+)\)+#', '$1', $lookfor);
                $newQG = false;
            }
            if (!$newQG && strpos($lookfor, '(') === 0) {
                $lookfor = preg_replace('#^\(+#', '', $lookfor);
                $newQG = true;
                $qg++;
            }
            foreach (array_merge($keywords, $phrasedKeywords, $hiddenKeywords) as $keyword => $searchType) {
                $upperKey = strtoupper($keyword);
                $searchName = $options->getHumanReadableFieldName($searchType);
                $keyRegex = '(('.$keyword.'\s)|('.$upperKey.'\s)|('.$searchType.':)|('.$searchName.':))';
                if (preg_match('#^'.$keyRegex.'([^"\s]*|("[^"]*"))((?=\s)|(?=$))#', $lookfor, $matches)) {
                    $key = $matches[1];
                    $item = $matches[6];
                    $type = $searchType;
                    $pos = strpos($lookfor, $key);
                    $lookfor = trim(substr_replace($lookfor, '', $pos, strlen($key)));
                    break;
                }
            }
            if (empty($item)) {
                $type = (empty($type)) ? $defaultType : $type;
                if (preg_match('#^([^"\s]+|("[^"]+"))((?=\s)|(?=$))#', $lookfor, $matches)) {
                    $item = trim($matches[1]);
                    $pos = strpos($lookfor, $item);
                    $lookfor = trim(substr_replace($lookfor, '', $pos, strlen($item)));
                    foreach ($booleans as $boolean => $boolNames) {
                        if (in_array($item, $boolNames)) {
                            if ($newQG || $qg == 0) {
                                $searchBoolean = $boolean;
                            } else {
                                $join = $boolean;
                            }
                            $item = '';
                        }
                    }
                }
                if (empty($searchBoolean)) {
                    $searchBoolean = 'AND';
                }
                if (!empty($item)) {
                    if (!isset($searchItems[$qg])) {
                        $searchItems[$qg] = ['types' => [], 'items' => []];
                    }
                    $searchItems[$qg]['types'][] = $type;
                    $searchItems[$qg]['items'][] = $item;
                    $searchItems[$qg]['bool'] = $searchBoolean;
                }
            }
        }
        if (!empty($searchItems)) {
            $request->set('lookfor', null);
            $request->set('type', null);
            $qg = 0;
            foreach ($searchItems as $searchItem) {
                $request->set('lookfor' . $qg, $searchItem['items']);
                $request->set('type' . $qg, $searchItem['types']);
                $request->set('bool' . $qg, [$searchItem['bool']]);
                $qg++;
            }
            if (true || empty($request->get('op0'))) {
                $request->set('op0', ['AND']);
            }
            $request->set('join', $join);
        }
        return $request;
    }
}
