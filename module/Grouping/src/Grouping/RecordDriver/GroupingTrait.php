<?php

/**
 * Record driver functionality for grouped search results.
 *
 * PHP version 8
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace Grouping\RecordDriver;

use Grouping\Backend\Solr\Backend;
use VuFindSearch\Command\SearchCommand;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query;

/**
 * Record driver functionality for grouped search results.
 *
 * Expects to be used in a subclass of \VuFind\RecordDriver\SolrDefault
 * (uses $fields, $mainConfig and the attached search service).
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
trait GroupingTrait
{
    /**
     * Cached result of getDuplicates()
     *
     * @var array|null
     */
    protected $groupingDuplicates = null;

    /**
     * Is grouping enabled in config.ini?
     *
     * @return bool
     */
    public function useGrouping()
    {
        return (bool)($this->mainConfig->Grouping->enabled ?? false);
    }

    /**
     * Get the Solr field used for grouping.
     *
     * @return string
     */
    public function getGroupingField()
    {
        return $this->mainConfig->Grouping->field ?? Backend::DEFAULT_GROUP_FIELD;
    }

    /**
     * Get the value of the grouping field of this record.
     *
     * @return string
     */
    public function getGroupingKey()
    {
        $value = $this->fields[$this->getGroupingField()] ?? '';
        return (string)(is_array($value) ? ($value[0] ?? '') : $value);
    }

    /**
     * Is this record a sub record of a group (i.e. not the first record)?
     *
     * @return bool
     */
    public function isSubRecord()
    {
        return (bool)($this->fields['_isSubRecord'] ?? false);
    }

    /**
     * Get the further records of the group (only set on the first record of a
     * group in a search result list).
     *
     * @return \Grouping\Backend\Solr\Response\Json\RecordCollection|null
     */
    public function getSubRecords()
    {
        return $this->fields['_subRecords'] ?? null;
    }

    /**
     * Does this record have sub records?
     *
     * @return bool
     */
    public function hasSubRecords()
    {
        $collection = $this->getSubRecords();
        return null !== $collection && $collection->count() > 0;
    }

    /**
     * Get the matchkey of the record.
     *
     * @return string
     */
    public function getMatchkey()
    {
        return $this->fields['matchkey'] ?? $this->fields['matchkey_str'] ?? '';
    }

    /**
     * Get all other records sharing the grouping key of this record
     * (e.g. for the Duplicates record tab).
     *
     * @return \VuFind\RecordDriver\AbstractBase[]
     */
    public function getDuplicates()
    {
        if (null !== $this->groupingDuplicates) {
            return $this->groupingDuplicates;
        }
        $this->groupingDuplicates = [];

        $key = $this->getGroupingKey();
        if (!$this->useGrouping() || '' === $key || null === $this->searchService) {
            return $this->groupingDuplicates;
        }

        $query = new Query(
            $this->getGroupingField() . ':"' . addcslashes($key, '"\\') . '"'
        );
        $params = new ParamBag(
            [
                'group' => 'false',
                'hl' => 'false',
                'sort' => 'publishDateSort asc, title_sort asc',
            ]
        );
        $collectionDetails = $this->mainConfig->Grouping->collection_details ?? '';
        if ('' !== $collectionDetails) {
            $params->add(
                'fq',
                'collection_details:"' . addcslashes($collectionDetails, '"\\') . '"'
            );
        }
        $command = new SearchCommand(
            $this->getSourceIdentifier(),
            $query,
            0,
            Backend::DEFAULT_GROUP_LIMIT,
            $params
        );
        try {
            $records = $this->searchService->invoke($command)->getResult()
                ->getRecords();
        } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
            return $this->groupingDuplicates;
        }
        foreach ($records as $record) {
            if ($record->getUniqueID() !== $this->getUniqueID()) {
                $this->groupingDuplicates[] = $record;
            }
        }
        return $this->groupingDuplicates;
    }
}
