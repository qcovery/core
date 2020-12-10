<?php
/**
 * SOLR backend.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
namespace LimitBatch\Backend\Solr;

use VuFindSearch\Backend\Solr\Backend as BackendBase;
use VuFindSearch\ParamBag;

/**
 * SOLR backend.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class Backend extends BackendBase
{
    /**
     * Maximum number of records for a batch query.
     *
     * @var pageSize
     */
    protected $pageSize = 100;

    public function setPageSize($pageSize) 
    {
        $this->pageSize = $pageSize;
    }

    /**
     * Retrieve a batch of documents.
     *
     * @param array    $ids    Array of document identifiers
     * @param ParamBag $params Search backend parameters
     *
     * @return RecordCollectionInterface
     */
    public function retrieveBatch($ids, ParamBag $params = null)
    {
        $params = $params ?: new ParamBag();

        // Callback function for formatting IDs:
        $formatIds = function ($i) {
            return '"' . addcslashes($i, '"') . '"';
        };

        // Retrieve records a page at a time:
        $results = false;
        while (count($ids) > 0) {
            $currentPage = array_splice($ids, 0, $this->pageSize, []);
            $currentPage = array_map($formatIds, $currentPage);
            $params->set('q', 'id:(' . implode(' OR ', $currentPage) . ')');
            $params->set('start', 0);
            $params->set('rows', $this->pageSize);
            $this->injectResponseWriter($params);
            $next = $this->createRecordCollection(
                $this->connector->search($params)
            );
            if (!$results) {
                $results = $next;
            } else {
                foreach ($next->getRecords() as $record) {
                    $results->add($record);
                }
            }
        }
        $this->injectSourceIdentifier($results);
        return $results;
    }
}
