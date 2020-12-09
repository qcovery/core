<?php
/**
 * "Get Item Status" AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
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
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace DAIAplus\AjaxHandler;

use VuFind\Exception\ILS as ILSException;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\ILS\Connection;
use VuFind\ILS\Logic\Holds;
use VuFind\Session\Settings as SessionSettings;
use VuFind\Crypt\HMAC;
use Zend\Config\Config;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\View\Renderer\RendererInterface;

/**
 * "Get Item Status" AJAX handler
 *
 * This is responsible for printing the holdings information for a
 * collection of records in JSON format.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetItemStatuses extends \VuFind\AjaxHandler\GetItemStatuses
{

    protected $hmac;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss        Session settings
     * @param Config            $config    Top-level configuration
     * @param Connection        $ils       ILS connection
     * @param RendererInterface $renderer  View renderer
     * @param Holds             $holdLogic Holds logic
     */
    public function __construct(SessionSettings $ss, Config $config, Connection $ils,
                                RendererInterface $renderer, Holds $holdLogic, HMAC $hmac
    ) {
        $this->hmac = $hmac;
        parent::__construct($ss, $config, $ils, $renderer, $holdLogic);
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites();  // avoid session write timing bug
        $ids = $params->fromPost('id', $params->fromQuery('id', []));
//neu V
        $list = $params->fromPost('list', $params->fromQuery('list', []));
        $mediatype = $params->fromPost('mediatype', $params->fromQuery('mediatype', []));
        $hideLink = $params->fromPost('hideLink', $params->fromQuery('hideLink', []));
        $hmacKeys = explode(':', $this->config['StorageRetrievalRequests']['HMACKeys']);

        $mediatype = $params->fromPost('list', $params->fromQuery('mediatype', []));
//neu A
        try {
            if (method_exists($this->ils, 'getDriver')) {
                $ilsDriver = $this->ils->getDriver();
                if (method_exists($ilsDriver, 'setLanguage')) {
                    $ilsDriver->setLanguage(
                        $this->translator->getLocale()
                    );
                }
            }
//neu V
            $this->ils->setList($list);
//neu A
            $results = $this->ils->getStatuses($ids);
        } catch (ILSException $e) {
            // If the ILS fails, send an error response instead of a fatal
            // error; we don't want to confuse the end user unnecessarily.
            error_log($e->getMessage());
            foreach ($ids as $id) {
                $results[] = [
                    [
                        'id' => $id,
                        'error' => 'An error has occurred'
                    ]
                ];
            }
        }

        if (!is_array($results)) {
            // If getStatuses returned garbage, let's turn it into an empty array
            // to avoid triggering a notice in the foreach loop below.
            $results = [];
        }

        // In order to detect IDs missing from the status response, create an
        // array with a key for every requested ID.  We will clear keys as we
        // encounter IDs in the response -- anything left will be problems that
        // need special handling.
        $missingIds = array_flip($ids);

        // Load messages for response:
        $messages = [
            'available' => $this->renderer->render('ajax/status-available.phtml'),
            'unavailable' =>
                $this->renderer->render('ajax/status-unavailable.phtml'),
            'unknown' => $this->renderer->render('ajax/status-unknown.phtml')
        ];

        // Load callnumber and location settings:
        $callnumberSetting = isset($this->config->Item_Status->multiple_call_nos)
            ? $this->config->Item_Status->multiple_call_nos : 'msg';
        $locationSetting = isset($this->config->Item_Status->multiple_locations)
            ? $this->config->Item_Status->multiple_locations : 'msg';
        $showFullStatus = isset($this->config->Item_Status->show_full_status)
            ? $this->config->Item_Status->show_full_status : false;

        // Loop through all the status information that came back
        $statuses = [];
        foreach ($results as $recordNumber => $record) {
            // Filter out suppressed locations:
            $record = $this->filterSuppressedLocations($record);

            // Skip empty records:
            if (count($record)) {
                // Check for errors
                if (!empty($record[0]['error'])) {
                    $current = $this
                        ->getItemStatusError($record, $messages['unknown']);
                } elseif ($locationSetting === 'group') {
                    $current = $this->getItemStatusGroup(
                        $record, $messages, $callnumberSetting
                    );
                } else {
                    $current = $this->getItemStatus(
                        $record, $messages, $locationSetting, $callnumberSetting
                    );
                }
                // If a full status display has been requested, append the HTML:
                if ($showFullStatus) {
                    $current['full_status'] = $this->renderer->render(
                        'ajax/status-full.phtml', [
                            'statusItems' => $record,
                            'callnumberHandler' => $this->getCallnumberHandler()
                        ]
                    );
                }
//neu V
                foreach ($record as $index1 => $recordItem) {
                    foreach ($recordItem['daiaplus']['actionArray'] as $index2 => $action) {
                        if (!empty($action['beluga_core']['href'])) {
                            $id = $current['id'];
                            $docId = $action['documentId'];
                            $itemId = $action['itemId'];
                            $type = $action['type'];
                            $storageId = null;
                            if (isset($action['storageId'])) {
                                $storageId = $action['storageId'];
                            }
                            $hmacPairs = [
                                'id' => $id,
                                'doc_id' => $docId,
                                'item_id' => $itemId
                            ];
                            $hashKey = $this->hmac->generate($hmacKeys, $hmacPairs);
                            $orderLink = '/vufind/Record/' . $id . '/Hold?';
                            $orderLink .= 'doc_id=' . urlencode($docId);
                            $orderLink .= '&item_id=' . urlencode($itemId);
                            $orderLink .= '&type=' . $type;
                            if ($storageId) {
                                $orderLink .= '&storage_id=' . urlencode($storageId);
                            }
                            $orderLink .= '&hashKey=' . $hashKey;
                            $record[$index1]['daiaplus']['actionArray'][$index2]['beluga_core']['href'] = $orderLink;
                        }
                    }
                }

                // Add display for DAIA+ data
                $current['daiaplus'] = $this->renderer->render(
                    'ajax/daiaplus.phtml', [
                        'daiaResults_org' => $record,
                        'callnumberHandler' => $this->getCallnumberHandler(),
                        'list' => $list === 'true'? true: false,
                        'ppn' => $current['id'],
                        'mediatype' => $mediatype,
                        'hideLink' => $hideLink,
                        'mediatype' => $mediatype,
                    ]
                );
//neu A
                $current['record_number'] = array_search($current['id'], $ids);

                $current['daiaBackend'] = $recordNumber;

                $statuses[] = $current;

                // The current ID is not missing -- remove it from the missing list.
                unset($missingIds[$current['id']]);
            }
        }

        // If any IDs were missing, send back appropriate dummy data
        foreach ($missingIds as $missingId => $recordNumber) {
            $statuses[] = [
                'id'                   => $missingId,
                'availability'         => 'false',
                'availability_message' => $messages['unavailable'],
                'location'             => $this->translate('Unknown'),
                'locationList'         => false,
                'reserve'              => 'false',
                'reserve_message'      => $this->translate('Not On Reserve'),
                'callnumber'           => '',
                'missing_data'         => true,
                'record_number'        => $recordNumber,
                'daiaBackend'          => ''
            ];
        }

        // Done
        return $this->formatResponse(compact('statuses'));
    }

    /**
     * Support method for getItemStatuses() -- filter suppressed locations from the
     * array of item information for a particular bib record.
     *
     * @param array $record Information on items linked to a single bib record
     *
     * @return array        Filtered version of $record
     */
    protected function filterSuppressedLocations($record)
    {
        static $hideHoldings = false;
        if ($hideHoldings === false) {
//          $hideHoldings = $this->holdLogic->getSuppressedLocations();
//in der VuFind-Methode gehen die "best results" verloren
            $hideHoldings = [];
        }

        $filtered = [];
        foreach ($record as $key => $current) {
            if (!in_array($current['location'] ?? null, $hideHoldings)) {
                $filtered[$key] = $current;
            }
        }
        return $filtered;
    }
}
