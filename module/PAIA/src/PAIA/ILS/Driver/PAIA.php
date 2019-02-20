<?php
/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * Based on the proof-of-concept-driver by Till Kinstler, GBV.
 *
 * PHP version 5
 *
 * Copyright (C) Oliver Goldschmidt 2010.
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
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
namespace PAIA\ILS\Driver;
use DOMDocument, VuFind\Exception\ILS as ILSException;
use VuFind\ILS\Driver\AbstractBase;
use PAIA\PAIAConnector;
use Zend\Session\Container;
use PAIA\Config\PAIAConfigService;

/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * Based on the proof-of-concept-driver by Till Kinstler, GBV.
 *
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
class PAIA extends AbstractBase
{
    /**
     * Base URL
     *
     * @var string
     */
    protected $baseURL;
    protected $paiaConnector;
    protected $paiaConfig;
    protected $session;
    protected $sessionFactory;
    private $paiaConfigService;

    // $sm->getServiceLocator()->get('VuFind\SessionManager');

    public function __construct()
    {
       $this->paiaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/PAIA.ini'), true);
    }

    public function setSessionFactory ($sessionFactory) {
        $this->sessionFactory = $sessionFactory;
        $this->session = $this->getSession();
    }

    public function setPaiaConfigService ($paiaConfigService) {
        $this->paiaConfigService = $paiaConfigService;
        $this->paiaConnector = new PAIAConnector($this->paiaConfigService);
    }

    /**
     * Get the session container (constructing it on demand if not already present)
     *
     * @return SessionContainer
     */
    protected function getSession()
    {
        // SessionContainer not defined yet? Build it now:
            if (null === $this->session) {
                $factory = $this->sessionFactory;
                $this->session = $factory();
            }
            return $this->session;
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
        if (!isset($this->config[$this->paiaConfigService->getPaiaGlobalKey()]['baseUrl'])) {
            throw new ILSException('Global/baseUrl configuration needs to be set.');
        }

        $this->baseURL = $this->config[$this->paiaConfigService->getPaiaGlobalKey()]['baseUrl'];
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @throws ILSException
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        $holding = $this->daiaToHolding($id);
        return $holding;
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @throws ILSException
     * @return array     An array of getStatus() return values on success.
     */
    public function getStatuses($ids)
    {
        $items = array();
        foreach ($ids as $id) {
            $items[] = $this->getShortStatus($id);
        }
        return $items;
    }

    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param string $id     The record id to retrieve the holdings for
     * @param array  $patron Patron data
     *
     * @throws \VuFind\Exception\Date
     * @throws ILSException
     * @return array         On success, an associative array with the following
     * keys: id, availability (boolean), status, location, reserve, callnumber,
     * duedate, number, barcode.
     */
    public function getHolding($id, array $patron = null)
    {
        return $this->getStatus($id);
    }

    /**
     * Get Purchase History
     *
     * This is responsible for retrieving the acquisitions history data for the
     * specific record (usually recently received issues of a serial).
     *
     * @param string $id The record id to retrieve the info for
     *
     * @throws ILSException
     * @return array     An array with the acquisitions data on success.
     */
    public function getPurchaseHistory($id)
    {
        return array();
    }
    
   /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $barcode  The patron barcode
     * @param string $password The patron password
     *
     * @throws ILSException
     * @return mixed           Associative array of patron info on successful login,
     * null on unsuccessful login.
     */
    public function patronLogin($barcode, $password, $isil = null)
    {

        if (!$isil) {
            $isil = $this->session->offsetGet('PAIAisil', $isil);
            if (!$isil) {
                $paiaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/PAIA.ini'), true);
                $isil = $paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['isil'];
            }
        }
        $this->session->offsetSet('PAIAisil', $isil);

        $user = array();
        $this->paiaConnector->setIsil($isil);
        $json = $this->paiaConnector->login($barcode, $password);
        $json_array = json_decode($json, true);
        if (!isset($json_array['error'])) {
           $user['id']           = trim($barcode);
           $user['access_token'] = $json_array['access_token'];
           $user['token_type']   = $json_array['token_type'];
           $user['scope']        = $json_array['scope'];
           $user['expires_in']   = $json_array['expires_in'];
           $user['cat_username'] = trim($barcode);
           $user['cat_password'] = trim($password);
           $user['cat_isil']     = $isil;
           $user['major']        = null;
           $user['college']      = null;
           
           $profile = $this->getMyProfile($user);
           $user['firstname']    = $profile['firstname'];
           $user['lastname']     = $profile['lastname'];
           $user['email']        = $profile['email'];
        }
        return $user;
    }
    	
   /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's transactions on success.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMyTransactions($patron)
    {
        $this->paiaConnector->setIsil($patron['cat_isil']);
        $json = $this->paiaConnector->items($patron['id'], $patron['access_token']);
        $json_array = json_decode($json, true);
        $dueDate = new \DateTime($doc['endtime']);
		$transList = array();
		if (isset($json_array['doc'])) {
		   if (!empty($json_array['doc'])) {
			  foreach ($json_array['doc'] as $doc) {
				 if ($doc['status'] == 3) {
					$transList[] = array(
                          'duedate' => $doc['endtime'],
                          'dueStatus' => '',
                          'barcode' => $doc['label'],
                          'queue' => $doc['queue'],
                          'reminder' => $doc['reminder'],
                          'renew' => $doc['renewals'],
                          'renewLimit' => $this->paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['renewLimit'],
                          'request' => '',
                          'id' => $doc['item'],
                          'item_id' => '',
                          'renewable' => $doc['canrenew'],
                          'title' => $doc['about'],
                          'status' => $doc['status'],
                          'institution_id' => '',
                          'institution_name' => '',
                          'institution_dbkey' => ''
					);
				 }
			  }
		   }
		}
		foreach ($transList as $key => $row) {
			$duedate[$key]  = $row['duedate'];
		}
		if (is_array($duedate)) {
            array_multisort($duedate, SORT_ASC, $transList);
        }
		$this->session->transactions = $transList;
        return $transList;
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's holds on success.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMyHolds($patron, $status = array(1, 2, 3, 4, 5))
    {
        $this->paiaConnector->setIsil($patron['cat_isil']);
        $json = $this->paiaConnector->items($patron['id'], $patron['access_token']);
        $json_array = json_decode($json, true);
               
        $createDate = new \DateTime($doc['starttime']);
        $expireDate = new \DateTime($doc['endtime']);
        
		$holdsList = array();
		if (isset($json_array['doc'])) {
		   if (!empty($json_array['doc'])) {
			  $counter = 0;
			  foreach ($json_array['doc'] as $doc) {
				 if (in_array($doc['status'],$status) && isset($doc['item']) && !empty($doc['item'])) {
					$holdsList[] = array(
                          'location' => $doc['storage'],
                          'create' => $doc['starttime'],
                          'expire' => $doc['endtime'],
                          'item_id' => $doc['item'],
                          'id' => $doc['item'],
                          'title' => $doc['about'],
                          'reqnum' => $doc['item'],
                          'barcode' => $doc['label'],
                          'queue' => $doc['queue'],
                          'status' => $doc['status']
					);
				 }
			  }
		   }
		}
		$this->session->holds = $holdsList;
        return $this->session->holds;
    }


    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's fines on success.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMyFines($patron)
    {
        $this->paiaConnector->setIsil($patron['cat_isil']);
        $json = $this->paiaConnector->fees($patron['id'], $patron['access_token']);
        $json_array = json_decode($json, true);
        
        if (!isset($this->session->fines)) {
            $finesList = array();
            if (isset($json_array['fee'])) {
               if (!empty($json_array['fee'])) {
                  $counter = 0;
                  foreach ($json_array['fee'] as $fee) {
                     $finesList[] = array(
                       'amount' => $fee['amount'],
                       'checkout' => '',
                       'fine' => '',
                       'balance' => '',
                       'duedate' => $fee['date'],
                       'title' => $fee['about'],
                       'feetype' => $fee['feetype']
                     );
                     $counter++;
                  }
                  $finesList['amount'] = $json_array['amount'];
               }
            }
          $this->session->fines = $finesList;
        }
        return $this->session->fines;    
    }


    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @return array        Array of the patron's profile data on success.
     */
    public function getMyProfile($patron)
    {
        $this->paiaConnector->setIsil($patron['cat_isil']);
        $json = $this->paiaConnector->patron($patron['id'], $patron['access_token']);
        $json_array = json_decode($json, true);
        $name_array = explode(', ', $json_array['name']);
        $expiresDate = new \DateTime($json_array['expires']);
        $patron = array(
            'firstname' => $name_array[1],
            'lastname' => $name_array[0],
            'email' => $json_array['email'],
            'expires' => $expiresDate->format('d.m.Y'),
            'status' => $json_array['status'],
            'address' => $json_array['address'],
            'type' => $json_array['type'][0],
        );
		
		
		if ($this->paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['show_profile_note'] == 1) {
			$patron += array(
				'note' => $json_array['note'],
			);
		}
		
        return $patron;
    }


    /**
     * Public Function which specifies renew, hold and cancel settings.
     *
     * @param string $function The name of the feature to be checked
     *
     * @return array An array with key-value pairs.
     */
    public function getConfig($function)
    {
        if ($function == 'Holds') {
            return array(
                'HMACKeys' => 'id:item_id:level',
                'extraHoldFields' => 'comments:requestGroup:pickUpLocation:requiredByDate',
                'defaultRequiredDate' => 'driver:0:2:0',
            );
        }
        if ($function == 'StorageRetrievalRequests' && $this->storageRetrievalRequests) {
            return array(
                'HMACKeys' => 'id',
                'extraFields' => 'comments:pickUpLocation:requiredByDate:item-issue',
                'helpText' => 'This is a storage retrieval request help text with some <span style="color: red">styling</span>.'
            );
        }
        if ($function == 'ILLRequests' && $this->ILLRequests) {
            return array(
                'enabled' => true,
                'HMACKeys' => 'number',
                'extraFields' => 'comments:pickUpLibrary:pickUpLibraryLocation:requiredByDate',
                'defaultRequiredDate' => '0:1:0',
                'helpText' => 'This is an ILL request help text with some <span style="color: red">styling</span>.'
            );
        }
        if ($function == 'Renewals') {
            return array();
        }
        return array();
    }
    
    
    /**
     * Renew My Items
     *
     * Function for attempting to renew a patron's items.  The data in
     * $renewDetails['details'] is determined by getRenewDetails().
     *
     * @param array $renewDetails An array of data required for renewing items
     * including the Patron ID and an array of renewal IDS
     *
     * @return array              An array of renewal information keyed by item ID
     */
    public function renewMyItems($renewDetails)
    {
        $renew_array = array();
        foreach ($renewDetails['details'] as $detail) {
           $renew_array['doc'][]['item'] = $detail; 
        }
        $renew_json = json_encode($renew_array);
        
        $this->paiaConnector->setIsil($renewDetails['patron']['cat_isil']);
        $json = $this->paiaConnector->renew($renewDetails['patron']['id'], $renew_json, $renewDetails['patron']['access_token']);
        $json_array = json_decode($json, true);
        
        $retVal = array('count' => 0, 'items' => array());
        
        if (!isset($this->session->holds)) {
           $json_holds = $this->paiaConnector->items($cancelDetails['patron']['id'], $cancelDetails['patron']['access_token']);
           $json_array_holds = json_decode($json_holds, true);
           
           $holdsList = array();
            if (isset($json_array_holds['doc'])) {
               if (!empty($json_array_holds['doc'])) {
                  $counter = 0;
                  foreach ($json_array_holds['doc'] as $doc) {
                     if ($doc['status'] == 2) {
                        $holdsList[] = array('location' => $doc['storage'],
                          'create' => $doc['starttime'],
                          'item_id' => $doc['item'],
                          'id' => $doc['item'],
                          'title' => $doc['about'],
                          'reqnum' => $doc['item'],
                          'barcode' => $doc['label'],
                          'status' => $doc['status'],
                        );
                     }
                  }
               }
            }
            $this->session->holds = $holdsList;
        }
        
        return $retVal;
    }


    /**
     * Get Renew Details
     *
     * In order to renew an item, Voyager requires the patron details and an item
     * id. This function returns the item id as a string which is then used
     * as submitted form data in checkedOut.php. This value is then extracted by
     * the RenewMyItems function.
     *
     * @param array $checkOutDetails An array of item data
     *
     * @return string Data for use in a form field
     */
    public function getRenewDetails($checkOutDetails)
    {
        return $checkOutDetails['id'];
    }
    
    
    /**
     * Cancel Holds
     *
     * Attempts to Cancel a hold or recall on a particular item. The
     * data in $cancelDetails['details'] is determined by getCancelHoldDetails().
     *
     * @param array $cancelDetails An array of item and patron data
     *
     * @return array               An array of data on each request including
     * whether or not it was successful and a system message (if available)
     */
    public function cancelHolds($cancelDetails)
    {
        $cancel_array = array();
        foreach ($cancelDetails['details'] as $detail) {
           $cancel_array['doc'][]['item'] = $detail; 
        }
        $cancel_json = json_encode($cancel_array);
        
        $this->paiaConnector->setIsil($cancelDetails['patron']['cat_isil']);
        $json = $this->paiaConnector->cancel($cancelDetails['patron']['id'], $cancel_json, $cancelDetails['patron']['access_token']);
        $json_array = json_decode($json, true);
        
        $retVal = array('count' => 0, 'items' => array());
        
        if (!isset($this->session->holds)) {
           $json_holds = $this->paiaConnector->items($cancelDetails['patron']['id'], $cancelDetails['patron']['access_token']);
           $json_array_holds = json_decode($json_holds, true);
           
           $holdsList = array();
            if (isset($json_array_holds['doc'])) {
               if (!empty($json_array_holds['doc'])) {
                  $counter = 0;
                  foreach ($json_array_holds['doc'] as $doc) {
                     if ($doc['status'] == 2) {
                        $holdsList[] = array(
                          'location' => $doc['storage'],
                          'create' => $doc['starttime'],
                          'item_id' => $doc['item'],
                          'id' => $doc['item'],
                          'title' => $doc['about'],
                          'reqnum' => $doc['item'],
                        );
                     }
                  }
               }
            }
            $this->session->holds = $holdsList;
        }
        
        $canceledItems = array();
        foreach ($cancelDetails['details'] as $detail) {
           $canceled = true;
           foreach ($this->session->holds as $hold) {
               if ($detail == $hold['item']) {
                   $canceled = false;
               }
           }
           if ($canceled) {
               $canceledItems[] = $detail;
           }
        }
        
        $retVal['canceledItems'] = $canceledItems;
        
        return $retVal;
    }
    
    
    /**
     * Get Cancel Hold Details
     *
     * In order to cancel a hold, Voyager requires the patron details an item ID
     * and a recall ID. This function returns the item id and recall id as a string
     * separated by a pipe, which is then submitted as form data in Hold.php. This
     * value is then extracted by the CancelHolds function.
     *
     * @param array $holdDetails An array of item data
     *
     * @return string Data for use in a form field
     */
    public function getCancelHoldDetails($holdDetails)
    {
        return $holdDetails['reqnum'];
    }
    
    
    /**
     * Get Short Status
     *
     * Used on overview page
     *
     * @param String $id The id of the item
     *
     * @return array Data
     */
    public function getShortStatus(string $id) {
       return array();
    }

    public function order($documentId, $itemId, $patron){
        // string -> json_encode does not return the correct structure!
        $doc = '{"doc":[{"edition":"'.$documentId.'","item":"'.$itemId.'","confirm":{ "http://purl.org/ontology/paia#FeeCondition": ["http://purl.org/ontology/dso#Reservation"] }}]}';
        return $this->paiaConnector->request($patron['id'], $doc, $patron['access_token']);
    }


    /**
     * Public Function which changes the password in the library system
     * (not supported prior to VuFind 2.4)
     *
     * @param array $details Array with patron information, newPassword and
     *                       oldPassword.
     *
     * @return array An array with patron information.
     */
    public function changePassword($details) {
        $json_password = $this->paiaConnector->change($details['patron']['id'], $details['patron']['access_token'], $details['patron']['cat_username'], $details['oldPassword'], $details['newPassword']);
        $json_password_array = json_decode($json_password, true);

        if (isset($json_password_array['error'])) {
            // on error
            $details = [
                'success'    => false,
                'status'     => $json_password_array['error_description'],
                'sysMessage' =>
                    isset($json_password_array['error'])
                        ? $json_password_array['error'] : ' ' .
                    isset($json_password_array['error_description'])
                        ? $json_password_array['error_description'] : ' '
            ];
        } elseif (isset($json_password_array['patron'])
            && $json_password_array['patron'] === $details['patron']['cat_username']
        ) {
            // on success patron_id is returned
            $details = [
                'success' => true,
                'status' => 'Successfully changed'
            ];
        } else {
            $details = [
                'success' => false,
                'status' => 'Failure changing password',
                'sysMessage' => serialize($json_password_array)
            ];
        }
        return $details;
    }
}
