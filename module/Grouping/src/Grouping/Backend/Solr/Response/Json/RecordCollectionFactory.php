<?php

/**
 * JSON-based factory for record collections supporting grouped Solr responses.
 *
 * PHP version 8
 *
 * @category VuFind
 * @package  Grouping
 * @author   <dku@outermedia.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace Grouping\Backend\Solr\Response\Json;

use VuFindSearch\Backend\Solr\Response\Json\Record;
use VuFindSearch\Exception\InvalidArgumentException;
use VuFindSearch\Response\RecordCollectionFactoryInterface;

/**
 * JSON-based factory for record collections supporting grouped Solr responses.
 *
 * For a grouped response, the first record of every group becomes the "master"
 * record; all further records of the group are attached to it as sub records
 * (raw field "_subRecords", each sub record carries "_isSubRecord").
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
class RecordCollectionFactory implements RecordCollectionFactoryInterface
{
    /**
     * Raw field receiving the Solr relevance explanation of a record
     * (same as RelevancePicker\...\RecordCollectionFactory::EXPLAIN_FIELD).
     *
     * @var string
     */
    public const EXPLAIN_FIELD = 'relevancepicker_explain';

    /**
     * Factory to turn data into a record object.
     *
     * @var callable
     */
    protected $recordFactory;

    /**
     * Class of collection.
     *
     * @var string
     */
    protected $collectionClass;

    /**
     * Constructor.
     *
     * @param ?callable $recordFactory   Callback to construct records
     * @param string    $collectionClass Class of collection
     *
     * @return void
     */
    public function __construct(
        $recordFactory = null,
        $collectionClass = RecordCollection::class
    ) {
        $this->recordFactory = $recordFactory ?? function ($data) {
            return new Record($data);
        };
        $this->collectionClass = $collectionClass;
    }

    /**
     * Return record collection.
     *
     * @param array $response Deserialized JSON response
     *
     * @return RecordCollection
     */
    public function factory($response)
    {
        if (!is_array($response)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unexpected type of value: Expected array, got %s',
                    gettype($response)
                )
            );
        }

        $collection = new $this->collectionClass($response);
        $groups = $collection->getGroups();
        $explain = $response['debug']['explain'] ?? [];

        if (empty($groups)) {
            foreach ($response['response']['docs'] ?? [] as $doc) {
                $doc = $this->addExplain($doc, $explain);
                $collection->add(call_user_func($this->recordFactory, $doc));
            }
            return $collection;
        }

        $groupField = array_key_first($groups);
        foreach ($groups[$groupField]['groups'] ?? [] as $group) {
            $docs = $group['doclist']['docs'] ?? [];
            // The first doc of the group is the master record:
            $docFirst = array_shift($docs);
            if (null === $docFirst) {
                continue;
            }
            $docFirst = $this->addExplain($docFirst, $explain);

            if (!empty($docs)) {
                $collectionSub = new $this->collectionClass(
                    ['response' => ['numFound' => count($docs), 'start' => 0]]
                );
                // Merge topics of all records of the group into the master record
                $topics = (array)($docFirst['topic'] ?? []);
                foreach ($docs as $doc) {
                    $doc = $this->addExplain($doc, $explain);
                    $doc['_isSubRecord'] = true;
                    $collectionSub->add(call_user_func($this->recordFactory, $doc));
                    if (isset($doc['topic']) && is_array($doc['topic'])) {
                        $topics = array_merge($topics, $doc['topic']);
                    }
                }
                if (!empty($topics)) {
                    $docFirst['topic'] = array_values(array_unique($topics));
                }
                $docFirst['_subRecords'] = $collectionSub;
            }

            $collection->add(call_user_func($this->recordFactory, $docFirst));
        }

        return $collection;
    }

    /**
     * Attach the Solr relevance explanation (debugQuery, used by the
     * RelevancePicker module) to a document, if available.
     *
     * @param array $doc     Solr document
     * @param array $explain Explanations from the Solr debug output, keyed by id
     *
     * @return array
     */
    protected function addExplain(array $doc, array $explain)
    {
        $id = $doc['id'] ?? '';
        if (isset($explain[$id])) {
            $doc[static::EXPLAIN_FIELD] = $explain[$id];
        }
        return $doc;
    }
}
