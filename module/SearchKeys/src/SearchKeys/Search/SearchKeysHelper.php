<?php
/**
 * Helper class for performing Searchkeys
 *
**/

namespace SearchKeys\Search;

class SearchKeysHelper
{
    /**
     * SearchKeys Helper
     *
     * @var SearchKeysHelper
     */
    protected $booleans = ['OR' => ['OR', 'ODER'], 'AND' => ['AND', 'UND'], 'NOT' => ['NOT', 'NICHT']];

    /**
     * SearchKeys Helper
     *
     * @var SearchKeysHelper
     */
    protected $boolRegex = '';

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
        $keywords = $hiddenKeywords = $keyRegexList = [];
        $id = strtolower($searchClassId);
        if ($config->get('keys-' . $id)) {
            $keywords = $config->get('keys-' . $id)->toArray();
        }
        if ($config->get('hiddenKeys-' . $id)) {
            $hiddenKeywords = $config->get('hiddenKeys-' . $id)->toArray();
        }
        foreach (array_merge($keywords, $hiddenKeywords) as $keyword => $searchType) {
            $upperKey = strtoupper($keyword);
            $searchName = $options->getHumanReadableFieldName($searchType);
            $keyRegexList[$searchType] = '(('.$keyword.'\s)|('.$upperKey.'\s)|('.$searchType.':)|('.$searchName.':))';
        }
        $fullKeyRegex = implode('|', $keyRegexList);

        $boolRegexList = [];
        foreach ($this->booleans as $boolean) {
            $boolRegexList[] = implode('|', $boolean);
        }
        $this->boolRegex = implode('|', $boolRegexList);

        $originalLookfor = trim(preg_replace('/\s+/', ' ', $request->get('lookfor')));
        $originalLookfor = preg_replace('/""+/', '"', $originalLookfor);
        $orignalType = $request->get('type') ?? $options->getDefaultHandler();
        if (!preg_match('#' . $fullKeyRegex . '#', $originalLookfor)) {
           return $request;
        }

        $searchItems = [];
        $join = null;

        if (preg_match_all('/(\()?[^()]+(?(1)\))/', $originalLookfor, $lfMatches)) {
            $qg = 0;
            foreach ($lfMatches[0] as $lfMatch) {
                if (!isset($searchItems[$qg])) {
                    $searchItems[$qg] = [];
                }

                $lfMatch = trim($lfMatch);
                foreach ($this->booleans as $op => $opNames){
                    if (in_array($lfMatch, $opNames)) {
                        if ($op == 'NOT') {
                            $join = 'AND';
                            $searchItems[$qg]['bools'] = [$op];
                        } else {
                            $join = $op;
                        }
                        continue 2;
                    }
                }

                $countUpItems = false;
                $lookfors = array_filter(preg_split('#\s*(' . $this->boolRegex . ')\s*#', $lfMatch));
                if (preg_match('#(' . $this->boolRegex . ')#', $lfMatch, $opMatches)) {
                    foreach ($this->booleans as $op => $opNames){
                        if (in_array($opMatches[1], $opNames)) {
                            if ($op == 'NOT') {
                                if ($qg == 0) {
                                    //ein erster Operator NOT erzeugt einen fehlerhaften Suchterm
                                    $searchItems[1]['bools'] = [$op];
                                } else {
                                    $searchItems[$qg]['bools'] = [$op];
                                }
                                $join = 'AND';
                                $countUpItems = true;
                            } elseif (count($lookfors) == 1) {
                                //Term ist nicht geklammert und beinhaltet einen boolschen Operator
                                $join = $op;
                            } elseif (empty($searchItems[$qg]['bools'])) {
                                //bei $qg == 0 ist $searchItems[1]['bools'] nicht gesetzt
                                $searchItems[$qg]['bools'] = [$op];
                            }
                        }
                    }
                }

                foreach ($lookfors as $lookfor) {
                    $lookfor = trim($lookfor, " ()");
                    $limit = 10;
                    while (!empty($lookfor) && $limit-- > 0) {
                        $item = $type = $key = '';
                        foreach ($keyRegexList as $searchType => $keyRegex) {
                            if (preg_match('#^' . $keyRegex . '([^"\s]*|("[^"]*"))((?=\s)|(?=$))#', $lookfor, $matches)) {
                                $key = $matches[1];
                                $item = $matches[6];
                                $type = $searchType;
                                $pos = strpos($item, $key);//?? strpos($lookfor, $key)
                                $lookfor = trim(substr_replace($lookfor, '', $pos, strlen($key)));
                                break;
                            }
                        }
                        if (empty($item)) {
                            if (preg_match('/^([^"\s]+|("[^"]+"))((?=\s)|(?=$))/', $lookfor, $matches)) {
                                $item = trim($matches[1]);
                                $type = $type ?? $orignalType;
                            }
                        }
                        if (!empty($item)) {
                            $pos = strpos($lookfor, $item);
                            $lookfor = trim(substr_replace($lookfor, '', $pos, strlen($item)));
                            $searchItems[$qg]['lookfors'][] = $item;
                            $searchItems[$qg]['types'][] = $type;
                            if ($countUpItems) {
                                $qg++;
                            }
                        }
                    }
                }
                if (!$countUpItems) {
                    $qg++;
                }
            }
        }

        if (!empty($searchItems)) {
            $request->set('lookfor', null);
            $request->set('type', null);
            foreach ($searchItems as $qg => $searchItem) {
                $request->set('lookfor' . $qg, $searchItem['lookfors']);
                $request->set('type' . $qg, $searchItem['types']);
                $request->set('bool' . $qg, $searchItem['bools']);
            }
            if (!empty($join)) {
                $request->set('join', $join);
            }
        }
        return $request;
    }
}
