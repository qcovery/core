<?php

/**
 * JSON-based factory for record collections with Solr explain data.
 *
 * PHP version 8
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

namespace RelevancePicker\Backend\Solr\Response\Json;

/**
 * JSON-based factory for record collections with Solr explain data.
 *
 * The record collection drops the Solr debug section. The explanation of each
 * record is attached to the record itself before the record drivers are built.
 * That way it stays available in the result list.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class RecordCollectionFactory extends \VuFindSearch\Backend\Solr\Response\Json\RecordCollectionFactory
{
    /**
     * Field the explanation is stored in.
     *
     * @var string
     */
    public const EXPLAIN_FIELD = 'relevancepicker_explain';

    /**
     * Return record collection.
     *
     * @param array $response Deserialized JSON response
     *
     * @return \VuFindSearch\Response\RecordCollectionInterface
     */
    public function factory($response)
    {
        if (is_array($response) && !empty($response['debug']['explain'])) {
            foreach ($response['response']['docs'] ?? [] as $index => $doc) {
                $explain = $response['debug']['explain'][$doc['id'] ?? ''] ?? null;
                if (null !== $explain) {
                    $response['response']['docs'][$index][static::EXPLAIN_FIELD] = $explain;
                }
            }
        }
        return parent::factory($response);
    }
}
