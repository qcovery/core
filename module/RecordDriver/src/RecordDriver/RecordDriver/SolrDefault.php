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
namespace RecordDriver\RecordDriver;

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
class SolrDefault extends \VuFind\RecordDriver\SolrDefault
{
    /**
     * These Solr fields should be used for snippets if available (listed in order
     * of preference).
     *
     * @var array
     */
    protected $preferredSnippetFields = [
        'contents', 'topic'
    ];

    /**
     * These Solr fields should NEVER be used for snippets.  (We exclude author
     * and title because they are already covered by displayed fields; we exclude
     * spelling because it contains lots of fields jammed together and may cause
     * glitchy output; we exclude ID because random numbers are not helpful).
     *
     * @var array
     */
    protected $forbiddenSnippetFields = [
        'author', 'title', 'title_short', 'title_full',
        'title_full_unstemmed', 'title_auth', 'title_sub', 'spelling', 'id',
        'ctrlnum', 'author_variant', 'author2_variant'
    ];

    /**
     * Get text that can be displayed to represent this record in
     * breadcrumbs. Here for compatibility reasons
     *
     * @return string Breadcrumb text to represent this record.
     */
    public function getBreadcrumb()
    {
        $breadCrumbs = $this->getShortTitle();
        return (is_array($breadCrumbs)) ? $breadCrumbs[0] : $breadCrumbs;
    }
	
    /**
     * Support method for getOpenUrl() -- pick the OpenURL format.
     *
     * @return string
     */
    protected function getOpenUrlFormat()
    {
        // If we have multiple formats, Book, Journal and Article are most
        // important...
        $formats = $this->getFormats();
        if (in_array('Book', $formats)) {
            return 'Book';
        } elseif (in_array('Article', $formats) || in_array('electronic Article', $formats)) {
            return 'Article';
        } elseif (in_array('Journal', $formats) || in_array('electronic Journal', $formats) || in_array('eJournal', $formats)) {
            return 'Journal';
        } elseif (isset($formats[0])) {
            return $formats[0];
        } elseif (strlen($this->getCleanISSN()) > 0) {
            return 'Journal';
        } elseif (strlen($this->getCleanISBN()) > 0) {
            return 'Book';
        }
        return 'UnknownFormat';
    }

	
    /**
     * Get the volume of the item that contains this record (i.e. MARC 773v of a
     * journal).
     *
     * @return string
     */
    public function getContainerVolume()
    {
		$volume = '';
		
		if(isset($this->fields['source'])) {
			
				if (strpos($this->fields['source'], 'Vol. ') == true) {
					$volume = substr($this->fields['source'],strpos($this->fields['source'], 'Vol.'));
					
					if (strpos($volume, 'No. ') == true) {
						$volume = strtok($volume,',');
					} else if (strpos($volume, ' (') == true) {
						$volume = strtok($volume,'(');
					} else if (strpos($volume, 'p.') == true) {
						$volume = strtok($volume,',');
					}
					
					$volume = trim(substr($volume, 5));
					
				}
		}
		
		return $volume;
        //return isset($this->fields['container_volume'])
        //    ? $this->fields['container_volume'] : '';
    }

    /**
     * Get the issue of the item that contains this record (i.e. MARC 773l of a
     * journal).
     *
     * @return string
     */
    public function getContainerIssue()
    {
		$issue = '';
		
		if(isset($this->fields['source'])) {
			
				if (strpos($this->fields['source'], 'No. ') == true) {
					$issue = substr($this->fields['source'],strpos($this->fields['source'], 'No.'));
					
					if (strpos($issue, ' (') == true) {
						$issue = strtok($issue,'(');
					} else if (strpos($issue, 'p.') == true) {
						$issue = strtok($issue,',');
					}
					
					$issue = trim(substr($issue, 4));
					
				}
		}
		
		return $issue;
	
		
        //return isset($this->fields['container_issue'])
        //    ? $this->fields['container_issue'] : '';
    }

    /**
     * Get the start page of the item that contains this record (i.e. MARC 773q of a
     * journal).
     *
     * @return string
     */
    public function getContainerStartPage()
    {
        $spage = '';
		
		if(isset($this->fields['source'])) {
			
				if (strpos($this->fields['source'], 'p. ') == true) {
					$spage = substr($this->fields['source'],strpos($this->fields['source'], 'p.'));
					
					if (strpos($spage, '-') == true) {
						$spage = strtok($spage,'-');
					}
					
					$spage = trim(substr($spage, 3));
					
				}
		}
		
		return $spage;
		
		//return isset($this->fields['container_start_page'])
        //    ? $this->fields['container_start_page'] : '';
    }
}
