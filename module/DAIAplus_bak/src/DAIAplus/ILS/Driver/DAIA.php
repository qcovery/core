<?php
/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * Based on the proof-of-concept-driver by Till Kinstler, GBV.
 * Relaunch of the daia driver developed by Oliver Goldschmidt.
 *
 * PHP version 7
 *
 * Copyright (C) Jochen Lienhard 2014.
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
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace DAIAplus\ILS\Driver;

use DOMDocument;
use VuFind\Exception\ILS as ILSException;
use VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface;
use Zend\Log\LoggerAwareInterface as LoggerAwareInterface;

/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class DAIA extends \VuFind\ILS\Driver\PAIA
{
    /**
     * API key needed for DAIA+ Service
     *
     * @var string
     */
    protected $apiKey;
    protected $list;
    protected $language;

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
        $domain = $this->getDAIADomain();

        if (isset($this->config[$domain]['baseUrl'])) {
            $this->baseUrl = $this->config[$domain]['baseUrl'];
        } elseif (isset($this->config['Global']['baseUrl'])) {
            throw new ILSException(
                'Deprecated [Global] section in DAIA.ini present, but no [DAIA] ' .
                'section found: please update DAIA.ini (cf. config/vufind/DAIA.ini).'
            );
        } else {
            throw new ILSException('DAIA/baseUrl configuration needs to be set.');
        }
        if (isset($this->config[$domain]['daiaplus_api_key'])) {
            $this->apiKey = $this->config[$domain]['daiaplus_api_key'];
        } else {
            throw new ILSException('DAIA/daiaplus_api_key configuration needs to be set.');
        }
        // use DAIA specific timeout setting for http requests if configured
        if ((isset($this->config[$domain]['timeout']))) {
            $this->daiaTimeout = $this->config[$domain]['timeout'];
        }
        if (isset($this->config[$domain]['daiaResponseFormat'])) {
            $this->daiaResponseFormat = strtolower(
                $this->config[$domain]['daiaResponseFormat']
            );
        } else {
            $this->debug('No daiaResponseFormat setting found, using default: xml');
            $this->daiaResponseFormat = 'xml';
        }
        if (isset($this->config[$domain]['daiaIdPrefix'])) {
            $this->daiaIdPrefix = $this->config[$domain]['daiaIdPrefix'];
        } else {
            $this->debug('No daiaIdPrefix setting found, using default: ppn:');
            $this->daiaIdPrefix = 'ppn:';
        }
        if (isset($this->config[$domain]['multiQuery'])) {
            $this->multiQuery = $this->config[$domain]['multiQuery'];
        } else {
            $this->debug('No multiQuery setting found, using default: false');
        }
        if (isset($this->config[$domain]['daiaContentTypes'])) {
            $this->contentTypesResponse = $this->config[$domain]['daiaContentTypes'];
        } else {
            $this->debug('No ContentTypes for response defined. Accepting any.');
        }
        if (isset($this->config[$domain]['daiaCache'])) {
            $this->daiaCacheEnabled = $this->config[$domain]['daiaCache'];
        } else {
            $this->debug('Caching not enabled, disabling it by default.');
        }
        if (isset($this->config[$domain]['cacheLifetime'])
        ) {
            $this->cacheLifetime = $this->config[$domain]['cacheLifetime'];
        } else {
            $this->debug(
                'Cache lifetime not set, using VuFind\ILS\Driver\AbstractBase ' .
                'default value.'
            );
        }
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     * As the DAIA Query API supports querying multiple ids simultaneously
     * (all ids divided by "|") getStatuses(ids) would call getStatus(id) only
     * once, id containing the list of ids to be retrieved. This would cause some
     * trouble as the list of ids does not necessarily correspond to the VuFind
     * Record-id. Therefore getStatuses(ids) has its own logic for multiQuery-support
     * and performs the HTTPRequest itself, retrieving one DAIA response for all ids
     * and uses helper functions to split this one response into documents
     * corresponding to the queried ids.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @return array    An array of status information values on success.
     */
    public function getStatuses($ids)
    {
        $daiaBackends = [];
        foreach ($this->config as $key => $value) {
            if (stristr($key, 'DAIA')) {
                $daiaBackends[$key] = $value;
            }
        }

        $paiaDomain = $this->getPAIADomain();
        $paiaIsil = '';
        if (isset($this->config[$paiaDomain]['isil'])) {
            $paiaIsil = $this->config[$paiaDomain]['isil'];
        }

        $status = [];
        foreach ($daiaBackends as $daiaBackend) {

            $this->baseUrl = $daiaBackend['baseUrl'];
            $this->apiKey = $daiaBackend['daiaplus_api_key'];
            $isCurrentIsil = true;
            if (isset($daiaBackend['isil']) && $paiaIsil != '') {
                if ($paiaIsil != $daiaBackend['isil']) {
                    $isCurrentIsil = false;
                }
            }

            // check cache for given ids and skip these ids if availability data is found
            foreach ($ids as $key => $id) {
                if ($this->daiaCacheEnabled
                    && $item = $this->getCachedData($this->generateURI($id))
                ) {
                    if ($item != null) {
                        $status[] = $item;
                        unset($ids[$key]);
                    }
                }
            }

            // only query DAIA service if we have some ids left
            if (count($ids) > 0) {
                try {
                    if ($this->multiQuery) {
                        // perform one DAIA query with multiple URIs
                        $rawResult = $this
                            ->doHTTPRequest($this->generateMultiURIs($ids));
                        // the id used in VuFind can differ from the document-URI
                        // (depending on how the URI is generated)
                        foreach ($ids as $id) {
                            // it is assumed that each DAIA document has a unique URI,
                            // so get the document with the corresponding id
                            $doc = $this->extractDaiaDoc($id, $rawResult);
                            if (null !== $doc) {
                                // a document with the corresponding id exists, which
                                // means we got status information for that record
                                $data = $this->parseDaiaDoc($id, $doc, $paiaIsil, $isCurrentIsil);
                                // cache the status information
                                if ($this->daiaCacheEnabled) {
                                    $this->putCachedData($this->generateURI($id), $data);
                                }
                                $status[$daiaBackend['name']] = $data;
                            }
                            unset($doc);
                        }
                    } else {
                        // multiQuery is not supported, so retrieve DAIA documents one by
                        // one
                        foreach ($ids as $id) {
                            $rawResult = $this->doHTTPRequest($this->generateURI($id));
                            // extract the DAIA document for the current id from the
                            // HTTPRequest's result
                            $doc = $this->extractDaiaDoc($id, $rawResult);
                            if (null !== $doc) {
                                // parse the extracted DAIA document and save the status
                                // info
                                $data = $this->parseDaiaDoc($id, $doc, $paiaIsil, $isCurrentIsil);
                                // cache the status information
                                if ($this->daiaCacheEnabled) {
                                    $this->putCachedData($this->generateURI($id), $data);
                                }
                                $status[$daiaBackend['name']] = $data;
                            }
                        }
                    }
                } catch (ILSException $e) {
                    $this->debug($e->getMessage());
                }
            }
        }
        return $status;
    }

    /**
     * Perform an HTTP request.
     *
     * @param string $id id for query in daia
     *
     * @return xml or json object
     * @throws ILSException
     */
    protected function doHTTPRequest($id)
    {
        $http_headers = [
            'Content-type: ' . $this->contentTypesRequest[$this->daiaResponseFormat],
            'Accept: ' . $this->contentTypesRequest[$this->daiaResponseFormat],
        ];

        $params = [
            'apikey' => $this->apiKey,
            //'id' => $id,
            'format' => $this->daiaResponseFormat,
            'language' => $this->language,
            'list' => $this->list,
        ];

        try {
            $result = $this->httpService->get(
                $this->baseUrl.$id,
                $params, $this->daiaTimeout, $http_headers
            );
        } catch (\Exception $e) {
            throw new ILSException(
                'HTTP request exited with Exception ' . $e->getMessage() .
                ' for record: ' . $id
            );
        }

        if (!$result->isSuccess()) {
            throw new ILSException(
                'HTTP status ' . $result->getStatusCode() .
                ' received, retrieving availability information for record: ' . $id
            );
        }

        // check if result matches daiaResponseFormat
        if ($this->contentTypesResponse != null) {
            if ($this->contentTypesResponse[$this->daiaResponseFormat]) {
                $contentTypesResponse = array_map(
                    'trim',
                    explode(
                        ',',
                        $this->contentTypesResponse[$this->daiaResponseFormat]
                    )
                );
                list($responseMediaType) = array_pad(
                    explode(
                        ';',
                        $result->getHeaders()->get('Content-type')->getFieldValue(),
                        2
                    ),
                    2,
                    null
                ); // workaround to avoid notices if encoding is not set in header
                if (!in_array(trim($responseMediaType), $contentTypesResponse)) {
                    throw new ILSException(
                        'DAIA-ResponseFormat not supported. Received: ' .
                        $responseMediaType . ' - ' .
                        'Expected: ' .
                        $this->contentTypesResponse[$this->daiaResponseFormat]
                    );
                }
            }
        }

        return $result->getBody();
    }

    /**
     * Parse an array with DAIA status information.
     *
     * @param string $id        Record id for the DAIA array.
     * @param array  $daiaArray Array with raw DAIA status information.
     *
     * @return array            Array with VuFind compatible status information.
     */
    protected function parseDaiaArray($id, $daiaArray, $paiaIsil, $isCurrentIsil)
    {
        $doc_id = null;
        $doc_href = null;
        if (isset($daiaArray['id'])) {
            $doc_id = $daiaArray['id'];
        }
        if (isset($daiaArray['href'])) {
            // url of the document (not needed for VuFind)
            $doc_href = $daiaArray['href'];
        }
        if (isset($daiaArray['message'])) {
            // log messages for debugging
            $this->logMessages($daiaArray['message'], 'document');
        }
        // if one or more items exist, iterate and build result-item
        if (isset($daiaArray['item']) && is_array($daiaArray['item'])) {
            $number = 0;
            foreach ($daiaArray['item'] as $item) {
                $result_item = [];
                $result_item['id'] = $id;
                // custom DAIA field
                $result_item['doc_id'] = $doc_id;
                $result_item['item_id'] = $item['id'];
                // custom DAIA field used in getHoldLink()
                $result_item['ilslink']
                    = ($item['href'] ?? $doc_href);
                // count items
                $number++;
                $result_item['number'] = $this->getItemNumber($item, $number);
                // set default value for barcode
                $result_item['barcode'] = $this->getItemBarcode($item);
                // set default value for reserve
                $result_item['reserve'] = $this->getItemReserveStatus($item);
                // get callnumber
                $result_item['callnumber'] = $this->getItemCallnumber($item);
                // get location
                $result_item['location'] = $this->getItemDepartment($item);
                // custom DAIA field
                $result_item['locationid'] = $this->getItemDepartmentId($item);
                // get location link
                $result_item['locationhref'] = $this->getItemDepartmentLink($item);
                // custom DAIA field
                $result_item['storage'] = $this->getItemStorage($item);
                // custom DAIA field
                $result_item['storageid'] = $this->getItemStorageId($item);
                // custom DAIA field
                $result_item['storagehref'] = $this->getItemStorageLink($item);
                // status and availability will be calculated in own function
                $result_item = $this->getItemStatus($item) + $result_item;
                // add result_item to the result array

                // keep DAIA+ data
                if (isset($item['daiaplus'])) {
                    $result_item['daiaplus'] = $item['daiaplus'];
                }

                if (isset($item['chronology'])) {
                    $result_item['chronology'] = $item['chronology'];
                }

                $result_item['paiaIsil'] = $paiaIsil;
                $result_item['isCurrentIsil'] = $isCurrentIsil;

                $result[] = $result_item;
            } // end iteration on item
        }

        // keep DAIA+ data
        if (isset($daiaArray['daiaplus_best_result'])) {
            $result['daiaplus_best_result'] = $daiaArray['daiaplus_best_result'];
            $result['daiaplus_best_result']['paiaIsil'] = $paiaIsil;
            $result['daiaplus_best_result']['isCurrentIsil'] = $isCurrentIsil;
        }

        return $result;
    }

    /**
     * Parse a DAIA document depending on its type.
     *
     * Parse a DAIA document depending on its type and return a VuFind
     * compatible array of status information.
     * Supported types are:
     *      - array (for JSON results)
     *
     * @param string $id      Record Id corresponding to the DAIA document
     * @param mixed  $daiaDoc The DAIA document, only array is supported
     *
     * @return array An array with status information for the record
     * @throws ILSException
     */
    protected function parseDaiaDoc($id, $daiaDoc, $paiaIsil, $isCurrentIsil)
    {
        if (is_array($daiaDoc)) {
            return $this->parseDaiaArray($id, $daiaDoc, $paiaIsil, $isCurrentIsil);
        } else {
            throw new ILSException(
                'Unsupported document type (did not match Array or DOMNode).'
            );
        }
    }

    public function setList ($list) {
        $this->list = $list;
    }

    public function setLanguage ($language) {
        $this->language = $language;
    }

    protected function getDAIADomain() {
        $session = $this->getSession();
        if (empty($session->daia_domain)) {
            $session->daia_domain  = 'DAIA';
        }
        return $session->daia_domain;
    }

    protected function getPAIADomain()
    {
        $session = $this->getSession();
        return $session->paia_domain;
    }
}
