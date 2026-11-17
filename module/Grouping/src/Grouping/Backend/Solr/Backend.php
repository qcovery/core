<?php

/**
 * Solr backend with result grouping.
 *
 * PHP version 8
 *
 * @category VuFind
 * @package  Grouping
 * @author   <dku@outermedia.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace Grouping\Backend\Solr;

use VuFindSearch\ParamBag;
use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\Query;
use VuFindSearch\Query\QueryGroup;
use VuFindSearch\Query\WorkKeysQuery;

/**
 * Solr backend with result grouping.
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
class Backend extends \VuFindSearch\Backend\Solr\Backend
{
    /**
     * Default grouping field
     *
     * @var string
     */
    public const DEFAULT_GROUP_FIELD = 'matchkey_str';

    /**
     * Default number of records per group
     *
     * @var int
     */
    public const DEFAULT_GROUP_LIMIT = 100;

    /**
     * Grouping configuration ([Grouping] section of config.ini)
     *
     * @var array
     */
    protected $groupingConfig = [];

    /**
     * Set the grouping configuration.
     *
     * @param array $config [Grouping] section of config.ini
     *
     * @return void
     */
    public function setGroupingConfig(array $config)
    {
        $this->groupingConfig = $config;
    }

    /**
     * Perform a search and return record collection.
     *
     * Grouping is applied unless it is disabled in config.ini or the caller
     * explicitly passes a "group" parameter (e.g. group=false).
     *
     * @param AbstractQuery $query  Search query
     * @param int           $offset Search offset
     * @param int           $limit  Search limit
     * @param ParamBag      $params Search backend parameters
     *
     * @return \VuFindSearch\Response\RecordCollectionInterface
     */
    public function search(
        AbstractQuery $query,
        $offset,
        $limit,
        ParamBag $params = null
    ) {
        if (!($query instanceof WorkKeysQuery)) {
            $params = $params ?: new ParamBag();
            $this->addGroupingParams($params);
            $query = $this->stripAmpersands($query);
        }
        return parent::search($query, $offset, $limit, $params);
    }

    /**
     * Add the Solr grouping parameters if grouping is active.
     *
     * @param ParamBag $params Search backend parameters
     *
     * @return void
     */
    protected function addGroupingParams(ParamBag $params)
    {
        if (empty($this->groupingConfig['enabled']) || $params->hasParam('group')) {
            return;
        }
        $field = $this->groupingConfig['field'] ?? self::DEFAULT_GROUP_FIELD;
        $params->set('group', 'true');
        $params->set('group.field', $field);
        $params->set(
            'group.limit',
            (string)($this->groupingConfig['limit'] ?? self::DEFAULT_GROUP_LIMIT)
        );
        // ngroups has a massive performance penalty; the number of groups is
        // determined via the cardinality stats instead (see RecordCollection).
        $params->set('group.ngroups', 'false');
        $params->set('stats', 'true');
        $params->set('stats.field', '{!cardinality=true}' . $field);
    }

    /**
     * Return a copy of the given query with every "&" character removed from
     * its search terms. The original $query object is left untouched to display
     * the search string exactly as the user typed it.
     *
     * @param AbstractQuery $query Query or QueryGroup to sanitize
     *
     * @return AbstractQuery Sanitized copy of $query
     */
    protected function stripAmpersands(AbstractQuery $query)
    {
        $sanitizedQuery = clone $query;
        $this->removeAmpersands($sanitizedQuery);
        return $sanitizedQuery;
    }

    /**
     * Recursively remove "&" characters from every Query string in the query.
     *
     * @param AbstractQuery $query Query or QueryGroup to modify in place
     *
     * @return void
     */
    protected function removeAmpersands(AbstractQuery $query)
    {
        if ($query instanceof Query) {
            $query->setString(str_ireplace('&', '', $query->getString()));
        } elseif ($query instanceof QueryGroup) {
            // QueryGroup::__clone() deep-clones its sub queries, so we can
            // modify them in place without touching the original query.
            foreach ($query->getQueries() as $subQuery) {
                $this->removeAmpersands($subQuery);
            }
        }
    }
}
