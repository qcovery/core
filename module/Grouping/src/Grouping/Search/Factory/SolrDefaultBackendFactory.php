<?php

/**
 * Factory for the default Solr backend with result grouping.
 *
 * PHP version 8
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace Grouping\Search\Factory;

use Grouping\Backend\Solr\Backend;
use Grouping\Backend\Solr\Response\Json\RecordCollection;
use Grouping\Backend\Solr\Response\Json\RecordCollectionFactory;
use VuFindSearch\Backend\Solr\Connector;

/**
 * Factory for the default Solr backend with result grouping.
 *
 * Reuses the VuFind core factory and only swaps the backend and record
 * collection classes, so all core settings (page size, similar builder,
 * logger, ...) are kept.
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
class SolrDefaultBackendFactory extends \VuFind\Search\Factory\SolrDefaultBackendFactory
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->backendClass = Backend::class;
        $this->recordCollectionClass = RecordCollection::class;
        $this->recordCollectionFactoryClass = RecordCollectionFactory::class;
    }

    /**
     * Create the Solr backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    protected function createBackend(Connector $connector)
    {
        $backend = parent::createBackend($connector);
        $grouping = $this->getService(\VuFind\Config\PluginManager::class)
            ->get('config')->Grouping;
        $backend->setGroupingConfig(
            $grouping ? $grouping->toArray() : []
        );
        return $backend;
    }
}
