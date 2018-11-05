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
        if (!empty($categories)) {
            foreach ($categories as $category) {
                foreach ($driver->getSolrMarcKeys($category) as $solrMarcKey) {
                    $solrMarcData[$solrMarcKey] = $driver->getMarcData($solrMarcKey);
                    $viewMethod = $solrMarcData[$solrMarcKey]['view-method'];
                    unset($solrMarcData[$solrMarcKey]['view-method']);
                    $templateData = [];
                    if ($viewMethod == 'description-link') {
                        foreach ($solrMarcData[$solrMarcKey] as $data) {
                            $templateData[] = $this->makeDescriptionLink($data, $category);
                        }
                        $solrMarcData[$solrMarcKey] = $templateData;
                    } elseif ($viewMethod == 'ppn-link') {
                        foreach ($solrMarcData[$solrMarcKey] as $data) {
                            $templateData[] = $this->makePpnLink($data);
                        }
                        $solrMarcData[$solrMarcKey] = $templateData;
		    } elseif ($viewMethod == 'plain') {
                        $collectedData = [];
                        print_r($solrMarcData[$solrMarcKey]);
                        foreach ($solrMarcData[$solrMarcKey] as $key => $value) {
                            if ($key != 'view-method') {
                                $collectedData[] = $value;
                            }
                        }
			//$solrMarcData[$solrMarcKey] = [implode(', ', $collectedData)];
			$solrMarcData[$solrMarcKey] = [$solrMarcData[$solrMarcKey][0]['ppn']['data']];
                        //print_r($solrMarcData[$solrMarcKey]);
		    } else {
                        $solrMarcData[$solrMarcKey] = $solrMarcData[$solrMarcKey];
                    }
                }
            }
        } else {
            foreach ($driver->getSolrMarcKeys([], false) as $solrMarcKey) {
                $solrMarcData[$solrMarcKey] = $driver->getMarcData($solrMarcKey);
            }
        }
        return $solrMarcData;
    }

    private function makeDescriptionLink($data, $key) {
        $key = strtolower($key);
        $string = '<a href="' . $this->getLink($key, $data['name']['data']) . '" title="' . $data['name']['data'] . '">' . $data['name']['data'] . '</a>';
        $additionalData = [];
        foreach ($data as $item => $date) {
            if ($item != 'view-method' && $item != 'name' && $item != 'description') {
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

    private function makePpnLink($data) {
        $collectedData = [];
        foreach ($data as $item => $date) {
            if ($item != 'view-method' && $item != 'ppn') {
                $collectedData[] = $date['data'];
            }
        }
	$dateString = implode(', ', $collectedData);
        if (empty($dateString)) {
            $dateString = $data['ppn']['data'];
        }
        if (!empty($data['ppn']['data'])) {
            $string = '<a href="' . $this->getLink('ppn', $data['ppn']['data']) . '" title="' . $dateString . '">' . $dateString . '</a>';
        } else {
            $string = $dateString;
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
/*
        $link .= $this->getView()->plugin('searchTabs')
            ->getCurrentHiddenFilterParams($this->driver->getSourceIdentifier());
*/
        return $link;
    }

}
