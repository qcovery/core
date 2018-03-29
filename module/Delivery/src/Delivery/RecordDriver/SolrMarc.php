<?php
/**
 * Model for MARC records in Solr.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Delivery\RecordDriver;

/**
 * Model for MARC records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrMarc extends \Libraries\RecordDriver\SolrMarc
{

    public function getSignatureData()
    {
        return $this->getMarcData('SignatureData');
    }

    public function getLicenceData()
    {
        return $this->getMarcData('LicenceData');
    }

    public function getContainingWork()
    {
        return $this->getMarcData('ContainingWork');
    }

    public function getTitleSection()
    {
        return $this->getMarcData('TitleSection');
    }

    public function getVolumeTitle()
    {
        return $this->getMarcData('VolumeTitle');
    }

    public function getPublicationDetailsFromMarc()
    {
        return $this->getMarcData('PublicationDetailsFromMarc');
    }

    public function getTitleStatement()
    {
        return $this->getMarcData('TitleStatement');
    }

    public function getUniversityNotes()
    {
        return $this->getMarcData('UniversityNotes');
    }
	
    public function getCollectionDetails()
    {
		$collection_details = $this->getMarcData('CollectionDetails');
		foreach ($collection_details AS $key => $collection) {
			$collection_details[$key] = $collection[0];
		}
        return $collection_details;
    }

    /**
     * Attach an ILS connection and related logic to the driver
     *
     * @param \VuFind\ILS\Connection       $ils            ILS connection
     * @param \VuFind\ILS\Logic\Holds      $holdLogic      Hold logic handler
     * @param \VuFind\ILS\Logic\TitleHolds $titleHoldLogic Title hold logic handler
     *
     * @return void
     */
    public function attachILSPAIA(\VuFind\ILS\Connection $ils,
                                  \PAIA\ILS\Logic\Holds $holdLogic,
                                  \PAIA\ILS\Logic\TitleHolds $titleHoldLogic
    ) {
        $this->ils = $ils;
        $this->holdLogic = $holdLogic;
        $this->titleHoldLogic = $titleHoldLogic;
    }
}
