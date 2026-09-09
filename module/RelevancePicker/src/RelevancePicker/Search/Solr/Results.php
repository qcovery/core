<?php
/**
 * Results Extension for RelevancePicker Module
 *
 * PHP version 8
 *
 * Copyright (C) Staats- und Universitätsbibliothek 2017.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Search
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */

namespace RelevancePicker\Search\Solr;

use Libraries\Search\Solr\Results as BaseResults;
use RelevancePicker\Backend\Solr\Response\Json\RecordCollectionFactory;

/**
 * Solr results providing the explain data of the current result page.
 *
 * Extends the Libraries results, since both modules provide their own Solr
 * results class.
 */
class Results extends BaseResults
{
    /**
     * Explain data of the current result page, record id as key.
     *
     * @var ?array
     */
    protected $explain = null;

    /**
     * Get the explain data of the current result page.
     * The data is read from the records, where it was added by RecordCollectionFactory.
     *
     * @return array|null Explain data, using record id as key
     */
    public function getExplain(): ?array
    {
        if (null === $this->explain) {
            $this->explain = [];
            foreach ($this->getResults() as $record) {
                $rawData = $record->tryMethod('getRawData') ?? [];
                $explain = $rawData[RecordCollectionFactory::EXPLAIN_FIELD] ?? null;
                if (null !== $explain) {
                    $this->explain[$record->getUniqueID()] = $this->reduceExplain($explain);
                }
            }
        }
        return $this->explain;
    }

    /**
     * Reduce Solr explanation to the summary lines used for the tooltip.
     *
     * @param string $explain Explanation of a single record
     *
     * @return string
     */
    protected function reduceExplain($explain): string
    {
        $summaryLines = [];
        foreach (explode("\n", $explain) as $line) {
            if (preg_match('/^[0-9 ].+of:$/', $line)) {
                $summaryLines[] = $line;
            }
        }
        return "\n" . implode("\n", $summaryLines);
    }
}
