<?php

/**
 * JSON-based record collection supporting grouped Solr responses.
 *
 * PHP version 8
 *
 * @category VuFind
 * @package  Grouping
 * @author   <dku@outermedia.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace Grouping\Backend\Solr\Response\Json;

/**
 * JSON-based record collection supporting grouped Solr responses.
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
class RecordCollection extends \VuFindSearch\Backend\Solr\Response\Json\RecordCollection
{
    /**
     * Grouping field name if the response is grouped.
     *
     * @var string|null
     */
    protected $groupFieldName = null;

    /**
     * Constructor.
     *
     * @param array $response Deserialized Solr response
     *
     * @return void
     */
    public function __construct(array $response)
    {
        parent::__construct($response);
        if ($this->isGrouped()) {
            $this->groupFieldName = array_key_first($this->getGroups());
            $this->offset = 0;
        }
    }

    /**
     * Is this a grouped response?
     *
     * @return bool
     */
    public function isGrouped()
    {
        return !empty($this->getGroups());
    }

    /**
     * Return total number of records (= number of groups) found.
     *
     * @return int
     */
    public function getTotal()
    {
        if (!$this->isGrouped()) {
            return parent::getTotal();
        }
        $field = $this->groupFieldName;
        return (int)($this->response['stats']['stats_fields'][$field]['cardinality']
            ?? $this->response['grouped'][$field]['matches']
            ?? 0);
    }

    /**
     * Set the source backend identifiers (also for the grouped sub records).
     *
     * @param string $recordSourceId  Record source identifier
     * @param string $searchBackendId Search backend identifier (if different from
     * $recordSourceId)
     *
     * @return void
     */
    public function setSourceIdentifiers($recordSourceId, $searchBackendId = '')
    {
        parent::setSourceIdentifiers($recordSourceId, $searchBackendId);
        foreach ($this->records as $record) {
            $subRecords = method_exists($record, 'getSubRecords')
                ? $record->getSubRecords() : null;
            if ($subRecords instanceof self) {
                $subRecords->setSourceIdentifiers($recordSourceId, $searchBackendId);
            }
        }
    }
}
