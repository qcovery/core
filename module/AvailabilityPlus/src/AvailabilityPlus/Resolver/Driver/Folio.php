<?php

namespace AvailabilityPlus\Resolver\Driver;

use VuFind\Config\SearchSpecsReader;
use VuFind\Exception\ILS as ILSException;
use VuFindHttp\HttpService;

/**
 * Folio Resolver Driver
 *
 * Fetches item availability from a FOLIO installation.
 *
 * The FOLIO instance addressed by this resolver is still running Okapi, so all
 * requests are sent through the Okapi gateway:
 *  - authentication via POST {baseUrl}/authn/login (legacy, returns the token in
 *    the X-Okapi-Token response header)
 *  - every request carries the headers X-Okapi-Tenant and X-Okapi-Token
 *
 * Availability information is not available from a single endpoint, therefore
 * the following requests are chained in fetchLinks():
 *  1. (only if id_type = hrid) /instance-storage/instances -> instance UUID
 *  2. /holdings-storage/holdings?query=(instanceId=="<uuid>")
 *  3. /inventory/items-by-holdings-id?query=(holdingsRecordId=="<uuid>")
 *
 * Expected configuration in availabilityplus-resolver.ini:
 *
 *  [ResolverBaseURL]
 *  Folio = 'https://okapi.example.org'
 *
 *  [Folio]
 *  tenant = 'diku'
 *  username = 'vufind_api'
 *  password = 'secret'
 *  ; auth_uri is optional, default: {baseUrl}/authn/login
 *  ;auth_uri = 'https://okapi.example.org/authn/login'
 *  ; instance (default) = the record id is a FOLIO instance UUID (MARC 999 ff $i)
 *  ; hrid = the record id is a FOLIO human readable id (MARC 001)
 *  id_type = 'instance'
 *
 * Note: ResolverExtraParams is not evaluated by this resolver, since the request
 * paths are built from the Okapi base url.
 *
 * The logger is injected by VuFind's ServiceInitializer (LoggerAwareInterface),
 * the cache used for the Okapi token is set by FolioFactory.
 */
class Folio extends AvailabilityPlusResolver implements \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\Cache\CacheTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Number of records requested per FOLIO API call.
     *
     * FOLIO returns 10 records by default, so a limit has to be sent explicitly.
     *
     * TODO: records exceeding this limit are silently ignored at the moment,
     * add paging (offset parameter) if installations with more holdings/items
     * per instance show up.
     */
    protected const DEFAULT_LIMIT = 100;

    /**
     * Lifetime (in seconds) of a cached Okapi token.
     *
     * The legacy endpoint /authn/login does not return an expiration date, so
     * the token is cached for a fixed period of time and renewed whenever FOLIO
     * answers with 401/403.
     */
    protected const DEFAULT_TOKEN_LIFETIME = 600;

    /**
     * Mapping of FOLIO item status names to the status label and the css class
     * used in the view templates.
     *
     * TODO: this is the complete list of FOLIO's built-in item statuses, the
     * labels and classes have to be aligned with the templates/language files
     * of the specific installation.
     *
     * @var array
     */
    protected $statusMap = [
        'Available' => ['Available', 'folio_green'],
        'Checked out' => ['On Loan', 'folio_red'],
        'Awaiting pickup' => ['Awaiting Pickup', 'folio_orange'],
        'Awaiting delivery' => ['Awaiting Delivery', 'folio_orange'],
        'In transit' => ['In Transit', 'folio_orange'],
        'In process' => ['In Process', 'folio_orange'],
        'In process (non-requestable)' => ['In Process', 'folio_orange'],
        'On order' => ['On Order', 'folio_orange'],
        'Paged' => ['Paged', 'folio_orange'],
        'Restricted' => ['In-Library Use', 'folio_orange'],
        'Missing' => ['Missing', 'folio_red'],
        'Long missing' => ['Missing', 'folio_red'],
        'Declared lost' => ['Lost', 'folio_red'],
        'Aged to lost' => ['Lost', 'folio_red'],
        'Lost and paid' => ['Lost', 'folio_red'],
        'Claimed returned' => ['Claimed Returned', 'folio_red'],
        'Withdrawn' => ['Withdrawn', 'folio_red'],
        'Order closed' => ['Not Available', 'folio_red'],
        'Intellectual item' => ['Not Available', 'folio_red'],
        'Unavailable' => ['Not Available', 'folio_red'],
        'Unknown' => ['Not Available', 'folio_red'],
    ];

    /**
     * Runtime cache for locations resolved by uuid.
     *
     * @var array
     */
    protected $locations = [];

    /**
     * Get Resolver Url
     *
     * Returns the url of the first request sent to FOLIO. The resolver needs
     * several requests to collect the availability data (see fetchLinks), this
     * url is the entry point and is also displayed in the AvailabilityPlus
     * debug view.
     *
     * @param string $params parameters (url-encoded), e.g. "?id=<uuid>"
     *
     * @return string Returns the resolver-specific url
     */
    public function getResolverUrl($params)
    {
        $id = $this->extractId($params);

        if (empty($id) || empty($this->baseUrl)) {
            return '';
        }

        if ($this->getIdType() == 'hrid') {
            return $this->buildUrl(
                '/instance-storage/instances',
                ['query' => '(hrid=="' . $this->escapeCql($id) . '")', 'limit' => 1]
            );
        }

        return $this->getHoldingsUrl($id);
    }

    /**
     * Fetch Links
     *
     * Fetches the holdings and items of an instance
     *
     * @param string $params parameters (url-encoded), e.g. "?id=<uuid>"
     *
     * @return array json responses of the chained requests
     */
    public function fetchLinks($params)
    {
        $id = $this->extractId($params);

        if (empty($id) || empty($this->baseUrl)) {
            return ['data' => []];
        }

        $instance = $this->getInstance($id);
        $instanceId = $instance['id'] ?? '';

        $holdings = [];
        foreach ($this->getHoldings($instanceId) as $holding) {
            // suppressed holdings are excluded by the query already, but the
            // flag is checked again for holdings delivered by other paths
            if ($holding['discoverySuppress'] ?? false) {
                continue;
            }

            $holdings[] = [
                'holding' => $holding,
                'items' => $this->getItems($holding['id'] ?? ''),
            ];
        }

        return [
            'data' => [
                'instance' => $instance,
                'holdings' => $holdings,
            ],
        ];
    }

    /**
     * Parse Links
     *
     * Parses the data returned by fetchLinks and converts it to a standardised
     * format for display
     *
     * @param array $data Data returned by fetchLinks
     *
     * @return array Array of values
     */
    public function parseLinks($data)
    {
        $response = [];
        $response['data'] = $data;

        $this->parsed_data = [];
        $instance = $data['data']['instance'] ?? [];
        $holdings = $data['data']['holdings'] ?? [];

        $key = 0;
        foreach ($holdings as $holdingData) {
            $holding = $holdingData['holding'] ?? [];
            $items = $holdingData['items'] ?? [];

            // TODO: holdings records without items (e.g. journals described by
            // holdings statements only) are skipped at the moment. If they have
            // to be displayed, build a record from the holdings data here
            // (compare show_holdings_no_items in VuFind's FOLIO ILS driver).

            foreach ($items as $item) {
                if ($item['discoverySuppress'] ?? false) {
                    continue;
                }

                $record = [];

                // FOLIO ids
                $record['instance_id'] = $instance['id'] ?? ($holding['instanceId'] ?? '');
                $record['instance_hrid'] = $instance['hrid'] ?? '';
                $record['holdings_id'] = $holding['id'] ?? '';
                $record['item_id'] = $item['id'] ?? '';

                // barcode
                $record['barcode'] = $item['barcode'] ?? '';

                // call-number
                $record['callnumber'] = $this->getCallnumber($item, $holding);

                // issue-number (volume, enumeration and chronology combined)
                $record['issue_number'] = trim(
                    implode(
                        ' ',
                        array_filter(
                            [
                                $item['volume'] ?? '',
                                $item['enumeration'] ?? '',
                                $item['chronology'] ?? '',
                            ]
                        )
                    )
                );

                // copy number
                $record['copy_number'] = $item['copyNumber'] ?? '';

                // collection
                // TODO: FOLIO has no direct equivalent of Koha's collection
                // code. Depending on the installation the material type, the
                // holdings type or a statistical code may be the right source.
                $record['collection'] = $item['materialType']['name'] ?? '';

                // location
                $record['location'] = $this->getLocationName($item, $holding);

                // notes
                $record['notes'] = $this->getPublicNotes($item, $holding);

                // uri
                // TODO: check whether electronic access should be taken from
                // the holdings record, the item or the instance.
                $record['uri'] = $holding['electronicAccess'][0]['uri'] ?? '';

                // availability
                $record['loan_type'] = $item['permanentLoanType']['name']
                    ?? ($item['temporaryLoanType']['name'] ?? '');
                $record['folio_status'] = $item['status']['name'] ?? '';

                // TODO: the due date of a checked out item is only available
                // via /circulation/loans?query=(itemId=="<uuid>" AND
                // status.name==Open). This needs an additional request per item
                // and the permission circulation.loans.collection.get for the
                // configured FOLIO user, therefore it is left out for now.
                $record['due_date'] = '';

                [$status, $statusClass] = $this->mapStatus($record['folio_status']);
                $record['status'] = $status;
                $record['status_class'] = $statusClass;

                // action link
                $record['orderLink'] = '';
                $record['orderLinkLabel'] = '';
                if (in_array($record['folio_status'], ['Checked out', 'On order', 'In process'])) {
                    $record['orderLink'] = $this->computeOrderLink($record);
                    $record['orderLinkLabel'] = 'Request';
                }

                $this->parsed_data[$key++] = $record;
            }
        }

        $this->applyCustomChanges();
        $response['parsed_data'] = $this->parsed_data;

        return $response;
    }

    /**
     * Applies custom changes to the parsed data based on defined rules and conditions.
     * This method overrides the parent method from AvailabilityPlusResolver.
     * The only change is the use of different delimiters for regular expressions.
     * @see AvailabilityPlusResolver::applyCustomChanges()
     * This method uses backticks instead of pipes for regular expressions to avoid
     * issues with the pipe character being used inside the regex pattern (e.g. "or").
     *
     * @return void
     */
    protected function applyCustomChanges()
    {
        $specsReader = new SearchSpecsReader();
        $rules = $specsReader->get($this->rules) ?? [];

        // $item must be a reference to make sure that changes made to $item are available in the following actions and conditions.
        foreach ($this->parsed_data as $key => &$item) {
            $rules_applied = [];

            foreach ($rules as $rule_key => $rule) {
                $rule_applies = false;

                foreach ($rule['conditions'] as $condition) {
                    $match_array = [];
                    $field_content = $item[$condition['field']] ?? '';
                    preg_match('`' . $condition['content'] . '`', $field_content, $match_array);

                    if (!empty($match_array)) {
                        $rule_applies = true;
                    } else {
                        $rule_applies = false;
                        break;
                    }
                }

                if ($rule_applies) {
                    foreach ($rule['actions'] as $action) {
                        $content_old = $item[$action['field']] ?? '';
                        $content_new = $content_old;

                        if (!empty($action['pattern'])) {
                            $content_preg = $item[$action['content_field']] ?? '';
                            $content_new = preg_replace('`' . $action['pattern'] . '`', $action['replacement'], $content_preg);
                            $this->parsed_data[$key][$action['field'] . '_org'] = $content_old;
                            $this->parsed_data[$key][$action['field']] = $content_new;
                        } else if (isset($action['content'])) {
                            $content_new = preg_replace('`(.*)`', '$0', $action['content']);
                            $this->parsed_data[$key][$action['field'] . '_org'] = $content_old;
                            $this->parsed_data[$key][$action['field']] = $content_new;
                        } else if (!empty($action['function'])) {
                            switch ($action['function']) {
                                case 'removeItem' :
                                    foreach ($this->parsed_data[$key] AS $item_key => $item_value) {
                                        $this->parsed_data[$key]['item_removed'] = true;
                                        $this->parsed_data[$key][$item_key . '_org'] = $item_value;
                                        unset($this->parsed_data[$key][$item_key]);
                                    }
                                    break;
                                case 'adjustScore' :
                                    if ($action['score']) {
                                        $this->parsed_data[$key]['score_org'] = $this->parsed_data[$key]['score'];
                                        $this->parsed_data[$key]['score'] += $action['score'];
                                    }
                                    break;
                            }
                        }
                    }

                    $rules_applied[$rule_key] = $rule;
                }
            }

            if (!empty($rules_applied)) {
                $this->parsed_data[$key]['rules_applied'] = $rules_applied;
            }
        }

        unset($item);
    }

    /**
     * Get the FOLIO instance for the id delivered by the record.
     *
     * If the record ids are FOLIO instance UUIDs (id_type = instance) no
     * request is needed, the id can be used directly.
     *
     * @param string $id Instance UUID or human readable id (hrid)
     *
     * @return array The instance data (at least the key "id")
     * @throws ILSException
     */
    protected function getInstance(string $id): array
    {
        if ($this->getIdType() != 'hrid') {
            return ['id' => $id];
        }

        $url = $this->buildUrl(
            '/instance-storage/instances',
            ['query' => '(hrid=="' . $this->escapeCql($id) . '")', 'limit' => 1]
        );
        $result = $this->makeRequest($url);
        $instance = $result['data']['instances'][0] ?? [];

        if (empty($instance['id'])) {
            throw new ILSException('Instance not found in FOLIO: ' . $id);
        }

        return $instance;
    }

    /**
     * Get the holdings records of an instance.
     *
     * @param string $instanceId Instance UUID
     *
     * @return array
     */
    protected function getHoldings(string $instanceId): array
    {
        if (empty($instanceId)) {
            return [];
        }

        $result = $this->makeRequest($this->getHoldingsUrl($instanceId));

        return $result['data']['holdingsRecords'] ?? [];
    }

    /**
     * Get the items of a holdings record.
     *
     * @param string $holdingsId Holdings record UUID
     *
     * @return array
     */
    protected function getItems(string $holdingsId): array
    {
        if (empty($holdingsId)) {
            return [];
        }

        $url = $this->buildUrl(
            '/inventory/items-by-holdings-id',
            [
                'query' => '(holdingsRecordId=="' . $this->escapeCql($holdingsId) . '")',
                'limit' => $this->getLimit(),
            ]
        );
        $result = $this->makeRequest($url);

        return $result['data']['items'] ?? [];
    }

    /**
     * Build the url used to request the holdings records of an instance.
     *
     * @param string $instanceId Instance UUID
     *
     * @return string
     */
    protected function getHoldingsUrl(string $instanceId): string
    {
        return $this->buildUrl(
            '/holdings-storage/holdings',
            [
                'query' => '(instanceId=="' . $this->escapeCql($instanceId)
                    . '" NOT discoverySuppress==true)',
                'limit' => $this->getLimit(),
            ]
        );
    }

    /**
     * Determine the call number of an item, falling back to the call number of
     * the holdings record.
     *
     * @param array $item    FOLIO item
     * @param array $holding FOLIO holdings record
     *
     * @return string
     */
    protected function getCallnumber(array $item, array $holding): string
    {
        // TODO: check with the library whether prefix and suffix should be
        // displayed and in which order.
        $parts = [
            $item['effectiveCallNumberComponents']['prefix']
                ?? ($item['itemLevelCallNumberPrefix'] ?? ($holding['callNumberPrefix'] ?? '')),
            $item['effectiveCallNumberComponents']['callNumber']
                ?? ($item['itemLevelCallNumber'] ?? ($holding['callNumber'] ?? '')),
            $item['effectiveCallNumberComponents']['suffix']
                ?? ($item['itemLevelCallNumberSuffix'] ?? ($holding['callNumberSuffix'] ?? '')),
        ];

        return trim(implode(' ', array_filter($parts)));
    }

    /**
     * Determine the location of an item.
     *
     * Items delivered by /inventory/items-by-holdings-id usually contain the
     * effective location as an object. If only the uuid is available, the
     * location is looked up via /locations/<uuid>.
     *
     * @param array $item    FOLIO item
     * @param array $holding FOLIO holdings record
     *
     * @return string
     */
    protected function getLocationName(array $item, array $holding): string
    {
        // TODO: FOLIO locations have a "name" and a "discoveryDisplayName",
        // clarify which one should be displayed. Additionally the library or
        // the campus of a location may be needed for multi-library setups.
        if (!empty($item['effectiveLocation']['discoveryDisplayName'])) {
            return $item['effectiveLocation']['discoveryDisplayName'];
        }
        if (!empty($item['effectiveLocation']['name'])) {
            return $item['effectiveLocation']['name'];
        }

        $locationId = $item['effectiveLocationId']
            ?? ($holding['effectiveLocationId'] ?? ($holding['permanentLocationId'] ?? ''));

        if (empty($locationId)) {
            return '';
        }

        if (!isset($this->locations[$locationId])) {
            $result = $this->makeRequest($this->buildUrl('/locations/' . $locationId));
            $this->locations[$locationId] = $result['data']['discoveryDisplayName']
                ?? ($result['data']['name'] ?? '');
        }

        return $this->locations[$locationId];
    }

    /**
     * Collect the public notes of an item.
     *
     * @param array $item    FOLIO item
     * @param array $holding FOLIO holdings record
     *
     * @return string
     */
    protected function getPublicNotes(array $item, array $holding): string
    {
        // TODO: notes may also be relevant on holdings level and FOLIO
        // distinguishes several note types (item notes, circulation notes,
        // holdings notes). Currently only public item notes are used.
        $notes = [];
        foreach ($item['notes'] ?? [] as $note) {
            if ($note['staffOnly'] ?? false) {
                continue;
            }
            if (!empty($note['note'])) {
                $notes[] = $note['note'];
            }
        }

        return implode('; ', $notes);
    }

    /**
     * Map a FOLIO item status to a status label and a css class.
     *
     * @param string $folioStatus Status name delivered by FOLIO
     *
     * @return array [label, css class]
     */
    protected function mapStatus(string $folioStatus): array
    {
        // TODO: unknown statuses are displayed as "Not Available" for now,
        // adjust as soon as the statuses actually used are known.
        return $this->statusMap[$folioStatus] ?? ['Not Available', 'red'];
    }

    /**
     * Compute Order Link
     *
     * Generates the link used to request an item.
     *
     * TODO: not implemented yet. It has to be decided whether requests are
     * placed via VuFind (/Record/<solrId>/Hold, compare the Koha resolver,
     * requires an hmac hash and a matching ILS driver) or by linking to the
     * FOLIO/Stripes user interface.
     *
     * @param array $record The already parsed item data
     *
     * @return string
     */
    protected function computeOrderLink(array $record): string
    {
        return '';
    }

    /**
     * Extract the record id from the parameters delivered by the AvailabilityPlus
     * ajax handler (e.g. "?id=<uuid>").
     *
     * @param string $params Parameter string
     *
     * @return string
     */
    protected function extractId($params): string
    {
        if (empty($params)) {
            return '';
        }

        $parsed = [];
        parse_str(ltrim((string)$params, '?'), $parsed);

        return trim($parsed['id'] ?? '');
    }

    /**
     * Build a FOLIO api url.
     *
     * @param string $path   Path of the FOLIO endpoint
     * @param array  $params Query parameters
     *
     * @return string
     */
    protected function buildUrl(string $path, array $params = []): string
    {
        $url = rtrim($this->baseUrl, '/') . $path;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Escape a string for use in a CQL query.
     *
     * @param string $in Input string
     *
     * @return string
     */
    protected function escapeCql(string $in): string
    {
        return str_replace('"', '\"', str_replace('&', '%26', $in));
    }

    /**
     * Get a value from the resolver specific configuration section.
     *
     * @param string $key     Configuration key
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    protected function getConfigValue(string $key, $default = null)
    {
        $value = $this->resolverConfig[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Get the FOLIO tenant.
     *
     * @return string
     * @throws ILSException
     */
    protected function getTenant(): string
    {
        $tenant = $this->getConfigValue('tenant');

        if (empty($tenant)) {
            throw new ILSException('No tenant configured for the Folio resolver.');
        }

        return $tenant;
    }

    /**
     * Which kind of id is delivered by the record: "instance" (uuid) or "hrid"?
     *
     * @return string
     */
    protected function getIdType(): string
    {
        return $this->getConfigValue('id_type', 'instance');
    }

    /**
     * Number of records requested per FOLIO api call.
     *
     * @return int
     */
    protected function getLimit(): int
    {
        return (int)$this->getConfigValue('limit', self::DEFAULT_LIMIT);
    }

    /**
     * Get an Okapi token.
     *
     * Requests a new token if there is no valid token in the cache.
     *
     * @param bool $renew Force a new token
     *
     * @return string
     * @throws ILSException
     */
    protected function getOkapiToken(bool $renew = false): string
    {
        $cacheKey = 'okapi_token_' . md5($this->baseUrl . $this->getTenant());

        if (!$renew) {
            $token = $this->getCachedData($cacheKey);
            if (!empty($token)) {
                return $token;
            }
        }

        $token = $this->requestNewOkapiToken();

        $this->putCachedData(
            $cacheKey,
            $token,
            (int)$this->getConfigValue('token_lifetime', self::DEFAULT_TOKEN_LIFETIME)
        );

        return $token;
    }

    /**
     * Request a new Okapi token via POST /authn/login.
     *
     * TODO: FOLIO releases from Poppy on offer /authn/login-with-expiry, which
     * returns the token as the cookie "folioAccessToken" together with an
     * expiration date. As the installation still runs Okapi, only the legacy
     * endpoint is implemented here. Add the cookie based variant (e.g.
     * controlled by a config setting legacy_authentication) once the
     * installation is migrated.
     *
     * @return string The token to be sent as X-Okapi-Token
     * @throws ILSException
     */
    protected function requestNewOkapiToken(): string
    {
        $tenant = $this->getTenant();
        $url = $this->getConfigValue('auth_uri', $this->buildUrl('/authn/login'));

        $credentials = [
            'tenant' => $tenant,
            'username' => $this->getConfigValue('username', ''),
            'password' => $this->getConfigValue('password', ''),
        ];

        // use a separate client to keep the shared http client free of the
        // post body and the authentication headers
        $client = (new HttpService())->createClient($url);
        $client->setMethod('POST');
        $client->setHeaders(
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Okapi-Tenant' => $tenant,
            ]
        );
        $client->setRawBody(json_encode($credentials));

        try {
            $response = $client->send();
        } catch (\Exception $e) {
            $this->logError("Request for '$url' failed: " . $e->getMessage());
            throw new ILSException('Problem getting Okapi token.');
        }

        if (!$response->isSuccess()) {
            $this->logError(
                "Request for '$url' failed: " . $response->getStatusCode()
                . ': ' . $response->getReasonPhrase()
            );
            throw new ILSException('Problem getting Okapi token: Bad status code returned');
        }

        $tokenHeader = $response->getHeaders()->get('X-Okapi-Token');
        $token = $tokenHeader ? $tokenHeader->getFieldValue() : '';

        if (empty($token)) {
            // some setups return the token in the response body instead
            $body = json_decode($response->getBody(), true);
            $token = $body['okapiToken'] ?? '';
        }

        if (empty($token)) {
            $this->logError("Did not receive an Okapi token from '$url'.");
            throw new ILSException('Problem getting Okapi token: Empty data');
        }

        return $token;
    }

    /**
     * Make a GET request to the FOLIO api and return the decoded response.
     *
     * @param string $url        The endpoint url
     * @param bool   $renewToken Force a new Okapi token (used for the retry
     *                           after an expired token)
     *
     * @return array An associative array containing the response data, the http
     *               status code and the headers:
     *               - 'data': decoded json data of the response body
     *               - 'code': the http status code of the response
     *               - 'headers': an array of response headers
     * @throws ILSException
     */
    protected function makeRequest(string $url, bool $renewToken = false): array
    {
        $this->httpClient->getRequest()->setMethod('GET');
        $this->httpClient->setHeaders(
            [
                'Accept' => 'application/json',
                'X-Okapi-Tenant' => $this->getTenant(),
                'X-Okapi-Token' => $this->getOkapiToken($renewToken),
            ]
        );

        try {
            $response = $this->httpClient->setUri($url)->send();
        } catch (\Exception $e) {
            $this->logError("Request for '$url' failed: " . $e->getMessage());
            throw new ILSException('Problem with FOLIO API.');
        }

        // the token may have expired, renew it once and try again
        if (in_array($response->getStatusCode(), [401, 403]) && !$renewToken) {
            return $this->makeRequest($url, true);
        }

        $decodedResult = json_decode($response->getBody(), true);

        if (!$response->isSuccess()) {
            $this->logError(
                "Request for '$url' failed: "
                . $response->getStatusCode() . ': ' . $response->getReasonPhrase()
                . ', response content: ' . $response->getBody()
            );
            throw new ILSException('Problem with FOLIO API.');
        }

        return [
            'data' => $decodedResult,
            'code' => (int)$response->getStatusCode(),
            'headers' => $response->getHeaders()->toArray(),
        ];
    }
}
