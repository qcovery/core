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
                $viewMethod = $solrMarcData[$solrMarcKey]['view-method'];
                unset($solrMarcData[$solrMarcKey]['view-method']);
                $templateData = [];
                if (strpos($viewMethod, '-link') > 0) {
                    list($key, ) = explode('-', $viewMethod);
                    foreach ($solrMarcData[$solrMarcKey] as $data) {
                        $templateData[] = $this->makeLink($data, $key);
                    }
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
            }
        }
        return $solrMarcData;
    }

    private function makeLink($data, $key, $separator = ', ') {
        if (empty($data['link'])) {
            return '';
        }
        $link = $linkname = implode($separator, $data['link']['data']);
        if (!empty($data['linkname'])) {
            $linkname = implode($separator, $data['linkname']['data']);
        }

        $string = '<a href="' . $this->getLink($key, $link) . '" title="' . $linkname . '">' . $linkname . '</a>';
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

    private function makeDirectLink($data) {
        if (empty($data['link'])) {
            return '';
        }
        $link = implode($separator, $data['link']['data']);

        $string = '<a href="' . $link . '" title="' . $link . '">' . $link . '</a>';
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
            $link = $data['link']['data'][0];
            $items[] = '<a href="' . $this->getLink('subject', $link) . '" title="' . $link . '">' . $link . '</a>';
            if (isset($data['sequence']) && $data['sequence']['data'][0] === 0) {
                $result[] = implode($separator, $items);
                $items = [];
            }
        }
        $result[] = implode($separator, $items);
        return $result;
     }

    private function makeText($data, $separator = ', ') {
        $string = '';
        if (array_keys($data) === array_filter(array_keys($data), 'is_int')) {
            foreach ($data as $date) {
                $string .= implode($separator, $date['data']) . $separator;
            }
            $string = substr_replace($string, '', -1 * strlen($separator));
        } else {
            foreach ($data as $item => $date) {
                $string .= '<strong>' . $item . ':</strong> ' . implode($separator, $date['data']) . '<br />';
            }
            $string = substr_replace($string, '', -6);
        }
        return $string;
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
