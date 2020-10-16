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
 * @package  View_Helpers
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/beluga-core
 */
namespace RecordDriver\View\Helper\RecordDriver;

use VuFind\View\Helper\Root\AbstractClassBasedTemplateRenderer;
use RecordDriver\RecordDriver\SolrMarc as RecordDriver;

/**
 * SolrDetails helpers
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class SolrDetails extends AbstractClassBasedTemplateRenderer
{


    protected $driver;

    protected $separatorSet = [
                  [', ', ' / ', ' - '],
                  [', ', ' ', ' ']
              ];

    /**
     * Get the core field entries
     *
     * @param RecordDriver  $driever               RecordDriver to use
     * @param array         $categories            Categories to read (see config)
     *
     * @return array
     */
    public function getCoreFields(RecordDriver $driver, $categories = [])
    {
        $this->driver = $driver;
        $solrMarcData = [];
        if (empty($categories)) {
            $categories = [[]];
        }
        foreach ($categories as $category) {
            foreach ($driver->getSolrMarcKeys($category) as $solrMarcKey) {
                $solrMarcData[$solrMarcKey] = $driver->getMarcData($solrMarcKey);
                if (empty($solrMarcData[$solrMarcKey])) {
                    continue;
                }
                $viewMethod = $solrMarcData[$solrMarcKey]['view-method'] ?? '';
                unset($solrMarcData[$solrMarcKey]['view-method']);
                $matchKey = $solrMarcData[$solrMarcKey]['match-key'] ?? '';
                unset($solrMarcData[$solrMarcKey]['match-key']);
                if (!empty($matchKey)) {
                    foreach ($solrMarcData[$solrMarcKey] as $index => $data) {
                        if (!empty($data[$matchKey]['data'][0])) {
                            foreach ($solrMarcData[$solrMarcKey] as $index2 => $data2) {
                                if ($index2 > $index && !empty($data2[$matchKey]['data'][0]) && $data2[$matchKey]['data'][0] == $data[$matchKey]['data'][0]) {
                                    foreach ($solrMarcData[$solrMarcKey][$index2] as $name => $entry) {
                                        $solrMarcData[$solrMarcKey][$index][$name] = $entry;
                                    }
                                    unset($solrMarcData[$solrMarcKey][$index2]);
                                }
                            }
                        }
                    }
                }
                $originalLetters = '';
                foreach ($solrMarcData[$solrMarcKey] as $data) {
                    foreach ($data as $date) {
                        if (isset($date['originalLetters'])) {
                            $originalLetters .= ' ' . $date['originalLetters'];
                        }
                    }
                }
                $templateData = [];
                if (strpos($viewMethod, '-link') > 0) {
                    list($key, , $separators) = explode('-', $viewMethod);
                    if (empty($separators)) {
                        $separators = 0;
                    }
                    $templateData = $this->makeLink($solrMarcData[$solrMarcKey], $key, $separators);
                } elseif ($viewMethod == 'directlink') {
                    foreach ($solrMarcData[$solrMarcKey] as $data) {
                        $templateData[] = $this->makeDirectLink($data);
                    }
                } elseif ($viewMethod == 'chain') {
                    $templateData = $this->makeChain($solrMarcData[$solrMarcKey]);
                } elseif (strpos($viewMethod, '-template') > 0) {
                    list($key, , $separators) = explode('-', $viewMethod);
                    if (empty($separators)) {
                        $separators = 0;
                    }
                    $templateData[] = $this->makeTemplate($solrMarcData[$solrMarcKey], $key, $separators);
                } else {
                    foreach ($solrMarcData[$solrMarcKey] as $data) {
                        $templateData[] = $this->makeText($data);
                    }
                }
                $solrMarcData[$solrMarcKey] = array_unique($templateData);
                if (!empty($originalLetters)) {
                    $solrMarcData[$solrMarcKey]['originalLetters'] = $originalLetters;
                }
            }
        }
        return $solrMarcData;
    }

    public function getResultListLine(RecordDriver $driver) {
        $resultList = [];
        $resultListData = $driver->getMarcData('ResultList');
        if (is_array($resultListData)) {
            foreach ($resultListData as $resultListDate) {
                if (is_array($resultListDate)) {
                    foreach ($resultListDate as $resultKey => $resultListArray) {
                        if(strpos($resultKey,"_array") !== false) {
                            if (isset($resultListArray['data'][0])) {
                                $resultList[$resultKey][] = $resultListArray['data'][0];
                            }
                        } else {
                            if (!isset($resultList[$resultKey]) && isset($resultListArray['data'][0])) {
                                $resultList[$resultKey] = $resultListArray['data'][0];
                            }
                        }
                    }
                }
            }
        }
        if (empty($resultList['title'])) {
            $resultList['title'] = $this->view->transEsc('no title');
        }
        return $resultList;
    }

    private function makeLink($solrMarcData, $key, $separators = 0) {
        $prefixes = $links = $linknames = $descriptions = $additionals = [];
        list($sep0, $sep1, $sep2) = $this->separatorSet[$separators];
        foreach ($solrMarcData as $index => $data) {
            if (!empty($data['link'])) {
                if (!empty($data['prefix'])) {
                    $prefixes[$index] = implode(' ', $data['prefix']['data']);
                    unset($data['prefix']);
                }
                $links[$index] = implode($sep0, $data['link']['data']);
                unset($data['link']);
            }
            if (!empty($data['prefix'])) {
                unset($data['prefix']);
            }
            if (!empty($data['linkname'])) {
                $linknames[$index] = implode($sep1, $data['linkname']['data']);
                unset($data['linkname']);
            }
            if (!isset($links[$index]) && $index > 0) {
                $index--;
            }
            if (!empty($data['description'])) {
                $descriptions[$index] = implode($sep0, $data['description']['data']);
                unset($data['description']);
            }
            $additional = [];
            foreach ($data as $item => $date) {
                $additional[] = implode($sep0, $date['data']);
            }
            if (!empty($additional) && empty($additionals[$index])) {
                $additionals[$index] = $additional;
            }
        }
        if (count($links) == 1) {
            $prefixes = array_values($prefixes);
            $links = array_values($links);
            $linknames = array_values($linknames);
            $descriptions = array_values($descriptions);
            $additionals = array_values($additionals);
        }
        $strings = [];
        foreach ($links as $index => $link) {
            if (!empty($prefixes[$index])) {
                $string = $prefixes[$index] . ': ';
            } else {
                $string = '';
            }
            if (!empty($linknames[$index])) {
                $linkname = $linknames[$index];
            } else {
                $linkname = $link;
            }
            $string .= '<a href="' . $this->getLink($key, $link) . '" title="' . $linkname . '">' . $linkname . '</a>';
            if (!empty($additionals[$index])) {
                $additional = $additionals[$index];
                $string .= $sep2 . implode($sep0, $additional);
            }
            if (!empty($descriptions[$index])) {
                $string .= ' [' . $descriptions[$index] . ']';
            }
            $strings[] = $string;
        }
        return $strings;
    }

    private function makeDirectLink($data, $separator = ', ') {
        if (empty($data['link'])) {
            return '';
        }
        $link = array_shift($data['link']['data']);
        if (!empty($data['linkname'])) {
            $linkname = array_shift($data['linkname']['data']);
            } else {
            $linkname = $link;
        }
        $string = '<a href="' . $link . '" title="' . $link . '">' . $linkname . '</a>';
        $additionalData = [];
        foreach ($data as $item => $date) {
            if ($item != 'link' && $item != 'linkname' && $item != 'description') {
                $additionalData[] = implode($separator, $date['data']);
            }
        }
        if (!empty($additionalData)) {
            $string .= ' (' . implode($separator, $additionalData) . ')';
        }
        if (!empty($data['description']['data'])) {
            $string .= ' [' . implode($separator, $data['description']['data']) . ']';
        }
        return $string;
    }

    private function makeChain($solrMarcData, $separator = ' / ') {
        $result = $items = $links = $sequences = $descriptions = [];
        foreach ($solrMarcData as $index => $data) {
            $links[$index] = implode(', ', $data['link']['data']);
            $sequences[$index] = intval($data['sequence']['data'][0]);
            if (!empty($data['description']['data'])) {
                $descriptions[$index] = implode(', ', $data['description']['data']);
            }
        }
        foreach ($links as $index => $link) {
            if (!empty($items) && $sequences[$index] == 0) {
                $result[] = implode($separator, $items);
                $items = [];
            }
            $item = '<a href="' . $this->getLink('subject', $link) . '" title="' . $link . '">' . $link . '</a>';
            if (!empty($descriptions[$index])) {
                if (is_array($descriptions[$index])) {
                    $item .= ' [' . implode(', ', $descriptions[$index]) . ']';
                } else {
                    $item .= ' [' . $descriptions[$index] . ']';
                }
            }
            $items[] = $item;
        }
        $result[] = implode($separator, $items);
        return $result;
     }

    private function makeText($data, $separator = ' ; ') {
        $string = '';
        foreach ($data as $key => $date) {
            $translatedData = [];
            foreach ($date['data'] as $value) {
                $translatedData[] = $this->view->transEsc($value);
            }
            if ($key === 'description') {
                $string .= ' [' . implode($separator, $translatedData) . ']';
            } elseif ($key === 'prefix') {
                $string = implode(' ', $translatedData) . ': ' . $string;
            } else {
                $string .= implode($separator, $translatedData) . $separator;
            }
        }
        return preg_replace('/(' . $separator . '|: )$/', '', $string);
     }

    /**
     * Render the link of the specified type.
     *
     * @param string $type    Link type
     * @param string $lookfor String to search for at link
     *
     * @return string
     */
    private function getLink($type, $lookfor)
    {
        $template = 'RecordDriver/%s/' . 'link-' . $type . '.phtml';
        $className = get_class($this->driver);
        $link = $this->renderClassTemplate(
            $template, $className, ['driver' => $this->driver, 'lookfor' => $lookfor]
        );
        return $link;
    }

    private function makeTemplate($solrMarcData, $key)
    {
        $template = 'RecordDriver/%s/' . 'template-' . $key . '.phtml';
        $className = get_class($this->driver);
        return $this->renderClassTemplate(
            $template, $className, ['solrMarcData' => $solrMarcData]
        );
    }
}
