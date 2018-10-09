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
    public function processSearchKeys($request, $options, $config, $searchClassId)
    {
        $id = strtolower($searchClassId);
        $keywords = $config->get('keys-' . $id)->toArray();
        $phrasedKeywords = $config->get('phrasedKeys-' . $id)->toArray();
        $defaultType = $options->getDefaultHandler();

        $lookfor = $lookfor = preg_replace('/\s+/', ' ', $request->get('lookfor'));
        $lookfor = preg_replace('/""+/', '"', $lookfor);
        $type = $request->get('type');

        $searchItems = [];
        $searchBoolean = ['AND'];
        $limit = 10;

        $type = '';
        while (!empty($lookfor) && $limit-- > 0) {
            $item = $key = '';
            foreach (array_merge($keywords, $phrasedKeywords) as $keyword => $searchType) {
                $searchName = $options->getHumanReadableFieldName($searchtype);
                $keyRegex = '(('.$keyword.'\s)|('.$searchType.':)|('.$searchName.':))';
                if (preg_match('#^'.$keyRegex.'([^"\s]*|("[^"]*"))((?=\s)|(?=$))#', $lookfor, $matches)) {
                    $key = $matches[1];
                    $item = $matches[5];
                    $type = $searchType;
                    $pos = strpos($lookfor, $key);
                    $lookfor = trim(substr_replace($lookfor, '', $pos, strlen($key)));
                    break;
                }
            }
            if (empty($item)) {
                $type = (empty($type)) ? $defaultType : $type;
                if (preg_match('#^([^"\s]+|("[^"]+"))((?=\s)|(?=$))#', $lookfor, $matches)) {
                    $item = $matches[1];
                    $pos = strpos($lookfor, $item);
                    $lookfor = trim(substr_replace($lookfor, '', $pos, strlen($item)));
                    if ($item == 'OR') {
                        $searchBoolean = ['OR'];
                    }
                }
                if (!isset($searchItems[$type])) {
                    $searchItems[$type] = [];
                }
                $searchItems[$type][] = $item;
            }
        }

        $lookfors = $types = array();
        foreach ($searchItems as $type => $items) {
            $types[] = $type;
            $lookfor = implode(' ', $items);
            if (in_array($type, $phrasedKeywords)) {
                $lookfor = '"' . $lookfor . '"';
            }
            $lookfors[] = $lookfor;
        }

        if (count($lookfors) > 1) {
            $request->set('lookfor', null);
            $request->set('lookfor0', $lookfors);
            $request->set('type0', $types);
            if (empty($request->get('bool0'))) {
                $request->set('bool0', $searchBoolean);
            }
            if (empty($request->get('op0'))) {
                $request->set('op0', array('AND'));
            }
            if (empty($request->get('join'))) {
                $request->set('join', 'OR');
            }
        } elseif (count($lookfors) == 1) {
            $request->set('lookfor0', null);
            $request->set('lookfor', $lookfors[0]);
            $request->set('type', $types[0]);
        } else {
            $request->set('lookfor', null);
        }
        return $request;
    }
}
