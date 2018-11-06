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
                    $solrMarcData[$solrMarcKey] = $templateData;
                } elseif ($viewMethod == 'default') {
                    foreach ($solrMarcData[$solrMarcKey] as $data) {
                        $templateData[] = $this->makeText($data);
                    }
                    $solrMarcData[$solrMarcKey] = $templateData;
		} else {
                    $solrMarcData[$solrMarcKey] = $solrMarcData[$solrMarcKey];
                }
            }
        }
        return $solrMarcData;
    }

    private function makeLink($data, $key) {
        $linkname = $data['linkname']['data'] ?? $data['link']['data'];
        $string = '<a href="' . $this->getLink($key, $data['link']['data']) . '" title="' . $linkname . '">' . $linkname . '</a>';
        $additionalData = [];
        foreach ($data as $item => $date) {
            if ($item != 'link' && $item != 'linkname' && $item != 'description') {
                $additionalData[] = $date['data'];
            }
        }
        if (!empty($additionalData)) {
            $string .= ' (' . implode(', ', $additionalData) . ')';
        }
        if (!empty($data['description']['data'])) {
            $string .= ' [' . $data['description']['data'] . ']';
        }
        return $string;
    }

    private function makeText($data) {
        $string = '';
        if (array_keys($data) == range(0, count($data)-1)) {
            foreach ($data as $date) {
                $string .= $date['data'] . ', ';
            }
            $string = substr_replace($string, '', -2);
        } else {
            foreach ($data as $item => $date) {
                $string .= '<strong>' . $item . ':</strong> ' . $date['data'] . '<br />';
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
