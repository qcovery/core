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
//use DAIAplus\ILS\Driver\DAIA as PAIAbase;

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
        if (empty($session->paia_domain)) {
            $session->paia_domain  = 'PAIA';
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

        $pickupLocation = [];
        if (isset($array_response['doc'][0]['condition']['http://purl.org/ontology/paia#StorageCondition']['option'])) {
            if (is_array($array_response['doc'][0]['condition']['http://purl.org/ontology/paia#StorageCondition']['option'])) {
                foreach ($array_response['doc'][0]['condition']['http://purl.org/ontology/paia#StorageCondition']['option'] as $option) {
                    $pickupLocation[] = ['locationID' => $option['id'], 'locationDisplay' => $option['about']];
                }
            }
        }

        return $pickupLocation;
    }
}
