<?php
/**
 * Default model for Solr records -- used when a more specific model based on
 * the recordtype field cannot be found.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace DependentWorks\RecordDriver;

/**
 * Default model for Solr records -- used when a more specific model based on
 * the recordtype field cannot be found.
 *
 * This should be used as the base class for all Solr-based record models.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class SolrDefault extends \RecordDriver\RecordDriver\SolrDefault
{
    /**
     * Get text that can be displayed to represent this record in
     * breadcrumbs.
     *
     * @return string Breadcrumb text to represent this record.
     */
    public function getDependentWorksData()
    {
        $id = $this->getUniqueID();
        $titles = $this->fields['title_short'];
        if (empty($titles)) {
            $titles = $this->fields['title'];
            if (empty($titles)) {
                $titles = $this->fields['series2'];
                if (empty($titles)) {
                    $titles = $this->fields['series'];
                    if (empty($titles)) {
                        $titles = $this->fields['journal'];
                    }
                }
            }
        }
        $title = (is_array($titles)) ? $titles[0] : $titles;
        $publishDates = $this->getPublicationDates();
        $publishDate = $publishDates[0];
        $formats = $this->getFormats();
        $format = $formats[0];
        return [
            'id' => $id,
            'title' => $title,
            'publishDate' => $publishDate,
            'format' => $format,
        ];
    }
}
