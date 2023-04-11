<?php
/**
 * PAIA ILS Driver for VuFind to get patron information
 *
 * PHP version 7
 *
 * Copyright (C) Oliver Goldschmidt, Magda Roos, Till Kinstler, André Lahmann 2013,
 * 2014, 2015.
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
 * @package  ILS_Drivers
 * @author   Oliver Goldschmidt <o.goldschmidt@tuhh.de>
 * @author   Magdalena Roos <roos@gbv.de>
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace PAIAplus\ILS\Driver;

use VuFind\Exception\ILS as ILSException;
use VuFind\ILS\Driver\PAIA as PAIAbase;

/**
 * PAIA ILS Driver for VuFind to get patron information
 *
 * Holding information is obtained by DAIA, so it's not necessary to implement those
 * functions here; we just need to extend the DAIA driver.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Oliver Goldschmidt <o.goldschmidt@tuhh.de>
 * @author   Magdalena Roos <roos@gbv.de>
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class PAIA extends PAIAbase
{
    /**
     * URL of PAIA service
     *
     * @var
     */
    protected $locationMap;

    /**
     * PAIA constructor.
     *
     * @param \VuFind\Date\Converter       $converter      Date converter
     * @param \Zend\Session\SessionManager $sessionManager Session Manager
     */
    public function __construct(\VuFind\Date\Converter $converter,
        \Zend\Session\SessionManager $sessionManager, $locationMap
    ) {
        parent::__construct($converter, $sessionManager);
        $this->locationMap = $locationMap->toArray();
    }

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init()
    {
        parent::init();
        $domain = $this->getPAIADomain();

        if (!(isset($this->config[$domain]['baseUrl']))) {
            throw new ILSException('PAIA/baseUrl configuration needs to be set.');
        }
        $this->paiaURL = $this->config[$domain]['baseUrl'];

        // use PAIA specific timeout setting for http requests if configured
        if ((isset($this->config[$domain]['timeout']))) {
            $this->paiaTimeout = $this->config[$domain]['timeout'];
        }

        // do we have caching enabled for PAIA
        if (isset($this->config[$domain]['paiaCache'])) {
            $this->paiaCacheEnabled = $this->config[$domain]['paiaCache'];
        } else {
            $this->debug('Caching not enabled, disabling it by default.');
        }
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed Array of the patron's holds on success.
     */
    public function getMyHolds($patron)
    {
        if (isset($this->config['Holds']['status'])) {
            $filter = ['status' => explode(':', $this->config['Holds']['status'])];
            $items = $this->paiaGetItems($patron, $filter);
            return $this->mapPaiaItems($items, 'myHoldsMapping');
        } else {
            return parent::getMyHolds($patron);
        }
    }

     /**
     * Mapping the location signet to a proper location name
     *
     * @param string $signet
     *
     * @return string
     */
    protected function mapLocation($signet) {
        foreach ($this->locationMap as $signetExpression => $locationName) {
            if (preg_match('#^' . $signetExpression . '$#', $signet)) {
                return $locationName;
            }
        }
        return '';
    }
   
    /**
     * This PAIA helper function allows custom overrides for mapping of PAIA response
     * to getMyHolds data structure.
     *
     * @param array $items Array of PAIA items to be mapped.
     *
     * @return array
     */
    protected function myHoldsMapping($items)
    {
        $results = [];

        foreach ($items as $doc) {
            $result = $this->getBasicDetails($doc);

            if ($doc['status'] == '4') {
                $result['expire'] = (isset($doc['endtime'])
                    ? $this->convertDatetime($doc['endtime']) : '');
            } else {
                $result['duedate'] = (isset($doc['endtime'])
                    ? $this->convertDatetime($doc['endtime']) : '');
            }

            // status: provided (the document is ready to be used by the patron)
            $result['available'] = $doc['status'] == 4 ? true : false;

            list($signet, ) = explode(':', $doc['label']);
            $result['institution_name'] = $this->mapLocation($signet);

            $results[] = $result;
        }
        return $results;
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return array Array of the patron's transactions on success,
     */
    public function getMyTransactions($patron)
    {
        if (isset($this->config['Transactions']['status'])) {
            $filter = ['status' => explode(':', $this->config['Transactions']['status'])];
            $items = $this->paiaGetItems($patron, $filter);
            return $this->mapPaiaItems($items, 'myTransactionsMapping');
        } else {
            return parent::getMyTransactions($patron);
        }
    }



    /**
     * This PAIA helper function allows custom overrides for mapping of PAIA response
     * to getMyTransactions data structure.
     *
     * @param array $items Array of PAIA items to be mapped.
     *
     * @return array
     */
    protected function myTransactionsMapping($items)
    {
        $results = [];
        $index = 0;

        foreach ($items as $doc) {
            $result = $this->getBasicDetails($doc);

            // canrenew (0..1) whether a document can be renewed (bool)
            $result['renewable'] = ($doc['canrenew'] ?? false);

            $result['renew_details']
                = (isset($doc['canrenew']) && $doc['canrenew'])
                ? $result['item_id'] : '';

            $result['renew_link']
                = (isset($doc['canrenew']) && $doc['canrenew'])
                ? $result['item_id'] : '';

            // queue (0..1) number of waiting requests for the document or item
            $result['request'] = ($doc['queue'] ?? null);

            // renewals (0..1) number of times the document has been renewed
            $result['renew'] = ($doc['renewals'] ?? null);

            // reminder (0..1) number of times the patron has been reminded
            $result['reminder'] = (
                $doc['reminder'] ?? null
            );

            // custom PAIA field
            // starttime (0..1) date and time when the status began
            $result['startTime'] = (isset($doc['starttime'])
                ? $this->convertDatetime($doc['starttime']) : '');

            // endtime (0..1) date and time when the status will expire
            $result['dueTime'] = (isset($doc['endtime'])
                ? $this->convertDatetime($doc['endtime']) : '');

            // duedate (0..1) date when the current status will expire (deprecated)
            $result['duedate'] = (isset($doc['duedate'])
                ? $this->convertDate($doc['duedate']) : '');

            // cancancel (0..1) whether an ordered or provided document can be
            // canceled

            // error (0..1) error message, for instance if a request was rejected
            $result['message'] = ($doc['error'] ?? '');

            // storage (0..1) textual description of location of the document
            $result['borrowingLocation'] = ($doc['storage'] ?? '');

            // storageid (0..1) location URI

            // PAIA custom field
            // label (0..1) call number, shelf mark or similar item label
            $result['callnumber'] = $this->getCallNumber($doc);

            list($signet, ) = explode(':', $doc['label']);
            $result['institution_name'] = $this->mapLocation($signet);

            // Optional VuFind fields
            /*
            $result['barcode'] = null;
            $result['dueStatus'] = null;
            $result['renewLimit'] = "1";
            $result['volume'] = null;
            $result['publication_year'] = null;
            $result['isbn'] = null;
            $result['issn'] = null;
            $result['oclc'] = null;
            $result['upc'] = null;
            $result['institution_name'] = null;
            */
            if (isset($this->config['Global']['renewLimit'])) {
                $result['renewLimit'] = $this->config['Global']['renewLimit'];
            }

            list($m, $d, $y) = explode('-', $result['dueTime']);
            $i = ($index < 10) ? '0' . $index : $index;
            $sort = $y.$m.$d.$i;
            $results[$sort] = $result;
            $index++;
        }
        ksort($results, SORT_NUMERIC);
        return array_values($results);
    }
        
    /**
     * Get Patron StorageRetrievalRequests
     *
     * This is responsible for retrieving all storage retrieval requests
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return array Array of the patron's storage retrieval requests on success,
     */
    public function getMyStorageRetrievalRequests($patron)
    {
        if (isset($this->config['StorageRetrievalRequests']['status'])) {
            $filter = ['status' => explode(':', $this->config['StorageRetrievalRequests']['status'])];
            $items = $this->paiaGetItems($patron, $filter);
            return $this->mapPaiaItems($items, 'myStorageRetrievalRequestsMapping');
        } else {
            return parent::getMyStorageRetrievalRequests($patron);
        }
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @return array Array of the patron's profile data on success,
     */
    public function getMyProfile($patron)
    {
        $profile = parent::getMyProfile($patron);
        if (!empty($profile)) {
            $profile['email'] = $patron['email'];
            $profile['name'] = $patron['name'];
            $profile['address1'] = $patron['address'];
            $profile['username'] = $patron['cat_username'];
            if (!empty($patron['note'])) {
                $profile['note'] = $patron['note'];
            }
        }
        return $profile;
    }

    public function setILSDomain($paiaDomain, $daiaDomain = '')
    {
        return $this->setPAIADomain($paiaDomain, $daiaDomain);
    }

    public function setPAIADomain($paiaDomain, $daiaDomain = '')
    {
        $session = $this->getSession();
        if (
               empty($this->config[$paiaDomain])
            || !is_array($this->config[$paiaDomain])
            || empty($this->config[$paiaDomain]['baseUrl'])
        ) {
            $paiaDomain = 'PAIA';
        }
        $session->paia_domain = $paiaDomain;

        if (
               !empty($daiaDomain) && (
                   empty($this->config[$daiaDomain])
                || !is_array($this->config[$daiaDomain])
                || empty($this->config[$daiaDomain]['baseUrl'])
                )
        ) {
            $daiaDomain = 'DAIA';
        }
        $session->daia_domain = $daiaDomain;
    }
        
    protected function getPAIADomain()
    {
        $session = $this->getSession();
        $paiaDomain = 'PAIA';
        if (isset($_POST['paia-select'])) {
            $paiaDomain = $_POST['paia-select'];
        }
        if (empty($session->paia_domain)) {
            $session->paia_domain  = $paiaDomain;
        } else {
            if ($session->paia_domain != $paiaDomain) {
                $session->paia_domain = $paiaDomain;
            }
        }
        return $session->paia_domain;
    }

    /**
     * Get Pick Up Locations
     *
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array $patron      Patron information returned by the patronLogin
     *                           method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the pickup options
     * or may be ignored.  The driver must not add new options to the return array
     * based on this data or other areas of VuFind may behave incorrectly.
     *
     * @return array        An array of associative arrays with locationID and
     * locationDisplay keys
     */
    public function getPickUpLocations($patron = null, $holdDetails = null)
    {
        $pickupLocation = [];

        $item = $holdDetails['item_id'];

        $doc = [];
        $doc['item'] = stripslashes($item);
        $post_data['doc'][] = $doc;

        try {
            $array_response = $this->paiaPostAsArray(
                'core/' . $patron['cat_username'] . '/request', $post_data
            );
        } catch (ILSException $e) {
            $this->debug($e->getMessage());
            return [
                'success' => false,
                'sysMessage' => $e->getMessage(),
            ];
        }

        if ($holdDetails['type'] == 'order') {
            if (isset($array_response['doc'][0]['condition']['http://purl.org/ontology/paia#StorageCondition']['option'])) {
                if (is_array($array_response['doc'][0]['condition']['http://purl.org/ontology/paia#StorageCondition']['option'])) {
                    foreach ($array_response['doc'][0]['condition']['http://purl.org/ontology/paia#StorageCondition']['option'] as $option) {
                        $pickupLocation[] = ['locationID' => $option['id'], 'locationDisplay' => $option['about']];
                    }
                }
            }
        } else if ($holdDetails['type'] == 'recall') {
            if (isset($this->config['pickUpLocations'])) {
                foreach ($this->config['pickUpLocations'] as $pickUpLocationData) {
                    if ($pickUpLocationData[0] == $holdDetails['storage_id'] || $pickUpLocationData[0] == urldecode($holdDetails['storage_id'])) {
                        $pickupLocation[] = [
                            'locationID' => $pickUpLocationData[1],
                            'locationDisplay' => $pickUpLocationData[2],
                        ];
                    }
                }
            }
        }

        return $pickupLocation;
    }

    /**
     * Map a PAIA document to an array for use in generating a VuFind request
     * (holds, storage retrieval, etc).
     *
     * @param array $doc Array of PAIA document to be mapped.
     *
     * @return array
     */
    protected function getBasicDetails($doc)
    {
        $result = parent::getBasicDetails($doc);

        if (!isset($result['status']) && isset($doc['status'])) {
            $result['status'] = $doc['status'];
        }

        if (!isset($result['queue']) && isset($doc['queue'])) {
            $result['queue'] = $doc['queue'];
        }

        return $result;
    }

    public function getCancelStorageRetrievalRequestDetails($details)
    {
        return $details['item_id'];
    }

    public function cancelStorageRetrievalRequests($cancelDetails) {
        return $this->cancelHolds($cancelDetails);
    }
}
