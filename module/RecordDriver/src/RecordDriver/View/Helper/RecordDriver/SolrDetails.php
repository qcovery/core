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
                $viewMethod = $solrMarcData[$solrMarcKey]['view-method'] ?? '';
                unset($solrMarcData[$solrMarcKey]['view-method']);
                $originalLetters = '';
                foreach ($solrMarcData[$solrMarcKey] as $data) {
                    foreach ($data as $date) {
                        if (isset($date['originalLetters'])) {
                            $originalLetters = $date['originalLetters'];
                            break 2;
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
                        if (!isset($resultList[$resultKey]) && isset($resultListArray['data'][0])) {
                            $resultList[$resultKey] = $resultListArray['data'][0];
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
            if (!empty($data['prefix'])) {
                $prefixes[$index] = implode(': ', $data['prefix']['data']);
                unset($data['prefix']);
            }
            if (!empty($data['link'])) {
                $links[$index] = implode($sep0, $data['link']['data']);
                unset($data['link']);
            }
            if (!empty($data['linkname'])) {
                $linknames[$index] = implode($sep1, $data['linkname']['data']);
                unset($data['linkname']);
            }
            if (!empty($data['description']['data'])) {
                $descriptions[$index] = implode($sep0, $data['description']['data']);
                unset($data['description']);
            }
            $additional = [];
            foreach ($data as $item => $date) {
                $additional[] = implode($sep0, $date['data']);
            }
            $additionals[$index] = $additional;
        }

        $strings = [];
        foreach ($links as $index => $link) {
            if (!empty($prefixes[$index]) > 0) {
                $string = $prefixes[$index] . ': ';
            } else {
                $string = '';
            }
            if (!empty($linknames[$index]) > 0) {
                $linkname = $linknames[$index];
            } else {
                $linkname = $link;
            }
            $string .= '<a href="' . $this->getLink($key, $link) . '" title="' . $linkname . '">' . $linkname . '</a>';
            if (!empty($additionals[$index]) > 0) {
                $additional = $additionals[$index];
                $string .= $sep2 . implode($sep0, $additional);
            }
            if (!empty($descriptions[$index]) > 0) {
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
        if (!empty($data['$linkname'])) {
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

    private function makeChain($dataList, $separator = ' / ') {
        $result = $items = [];
        foreach ($dataList as $data) {
            if (!empty($items) && isset($data['sequence']) && $data['sequence']['data'][0] == 0) {
                $result[] = implode($separator, $items);
                $items = [];
            }
            $link = $data['link']['data'][0];
            $item = '<a href="' . $this->getLink('subject', $link) . '" title="' . $link . '">' . $link . '</a>';
            if (!empty($data['description']['data'])) {
                $item .= ' [' . implode(', ', $data['description']['data']) . ']';
            }
            $items[] = $item;
        }
        $result[] = implode($separator, $items);
        return $result;
     }

    private function makeText($data, $separator = ', ') {
        $string = '';
        foreach ($data as $key => $date) {
            $translatedData = [];
            foreach ($date['data'] as $value) {
                $translatedData[] = $this->view->transEsc($value);
            }
            if (true || $key != 'description') {
                $string .= implode($separator, $translatedData) . $separator;
            } else {
                $string .= ' [' . implode($separator, $translatedData) . ']';
            }
        }
        $string = substr_replace($string, '', -1 * strlen($separator));
        return $this->view->transEsc($string);
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
}
