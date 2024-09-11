<?php
/**
 * Params Extension for Libraries Module
 *
 * PHP version 5
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
 * @category VuFind2
 * @package  Search
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */
namespace RelevancePicker\Search\Solr;

//use VuFind\Search\Solr\Params as BaseParams;
use VuFind\Search\Solr\Results as BaseResults;
use VuFindSearch\Command\SearchCommand;

class Results extends BaseResults
{
    /**
     * ExplainData.
     *
     * @array explain
     */
    protected $explain = [];

    /**
     * Support method for performAndProcessSearch -- perform a search based on the
     * parameters passed to the object.
     *
     * @return void
     */
    protected function performSearch()
    {
        $query  = $this->getParams()->getQuery();
        $limit  = $this->getParams()->getLimit();
        $offset = $this->getStartRecord() - 1;
        $params = $this->getParams()->getBackendParameters();
        $searchService = $this->getSearchService();

        $command = new SearchCommand(
            $this->backendId,
            $query,
            $offset,
            $limit,
            $params
        );
        $searchService->invoke($command);
        $collection = $command->getResult();

        $this->responseFacets = $collection->getFacets();
        $this->resultTotal = $collection->getTotal();

        // Process spelling suggestions
        $spellcheck = $collection->getSpellcheck();
        $this->spellingQuery = $spellcheck->getQuery();
        $this->suggestions = $this->getSpellingProcessor()
            ->getSuggestions($spellcheck, $this->getParams()->getQuery());

        // Construct record drivers for all the items in the response:
        $this->results = $collection->getRecords();

        // Process Explain Data:
        $this->explain = $collection->getExplain();
    }

    /**
     * Get explain Data
     *
     * @return array.
     */
    public function getExplain()
    {
        return $this->explain;
    }
}

