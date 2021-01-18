<?php

/**
 * Simple JSON-based record collection.
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
namespace RelevancePicker\Backend\Solr\Response\Json;

use VuFindSearch\Response\AbstractRecordCollection;

/**
 * Simple JSON-based record collection.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class RecordCollection extends \VuFindSearch\Backend\Solr\Response\Json\RecordCollection
{
    /**
     * Return explain part.
     *
     * @return array
     */
    public function getExplain()
    {
        $explainData = array();
        if (isset($this->response['debug']['explain'])) {
            foreach ($this->response['debug']['explain'] as $ppn => $explain) {
                $explainDataList = array();
                $lines = explode("\n", $explain);
                foreach ($lines as $line) {
                    if (preg_match('/^[0-9 ].+of:$/', $line)) {
                        $explainDataList[] = $line;
                    }
                }
                $explainData[$ppn] = "\n" . implode("\n", $explainDataList);
            }
        }
        return $explainData;
    }
}
