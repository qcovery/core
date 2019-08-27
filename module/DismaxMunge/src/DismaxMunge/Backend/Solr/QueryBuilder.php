<?php

/**
 * SOLR QueryBuilder.
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
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   David Maus <maus@hab.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
namespace DismaxMunge\Backend\Solr;

use VuFindSearch\ParamBag;
use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\Query;
use VuFindSearch\Query\QueryGroup;
use VuFindSearch\Backend\Solr\QueryBuilderInterface;
//use DismaxMunge\Backend\Solr\SearchHandler;

/**
 * SOLR QueryBuilder.
 *
 * @category VuFind
 * @package  Search
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   David Maus <maus@hab.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class QueryBuilder extends \VuFindSearch\Backend\Solr\QueryBuilder implements QueryBuilderInterface
{
    /// Public API


    /**
     * Set query builder search specs.
     *
     * @param array $specs Search specs
     *
     * @return void
     */
    public function setSpecs(array $specs)
    {
        foreach ($specs as $handler => $spec) {
            if (isset($spec['ExactSettings'])) {
                $this->exactSpecs[strtolower($handler)] = new SearchHandler(
                    $spec['ExactSettings'], $this->defaultDismaxHandler
                );
                unset($spec['ExactSettings']);
            }
            $this->specs[strtolower($handler)]
                = new SearchHandler($spec, $this->defaultDismaxHandler);
        }
    }

    /**
     * Return SOLR search parameters based on a user query and params.
     *
     * @param AbstractQuery $query User query
     *
     * @return ParamBag
     */
    public function build(AbstractQuery $query)
    {
        $params = new ParamBag();

        // Add spelling query if applicable -- note that we must set this up before
        // we process the main query in order to avoid unwanted extra syntax:
        if ($this->createSpellingQuery) {
            $params->set(
                'spellcheck.q',
                $this->getLuceneHelper()->extractSearchTerms($query->getAllTerms())
            );
        }


        if ($query instanceof QueryGroup) {
            $finalQuery = $this->reduceQueryGroup($query);
        } else {
            // Clone the query to avoid modifying the original user-visible query
            $finalQuery = clone $query;
            $finalQuery->setString($this->getNormalizedQueryString($query));
        }
        $string = $finalQuery->getString() ?: '*:*';

        // Highlighting is enabled if we have a field list set.
        $highlight = !empty($this->fieldsToHighlight);

        if ($handler = $this->getSearchHandler($finalQuery->getHandler(), $string)) {
            if ($handler->hasDismax()) {
                 $string = array_pop($handler->mungeValues($string, false));
            }
            if (!$handler->hasExtendedDismax()
                && $this->getLuceneHelper()->containsAdvancedLuceneSyntax($string)
            ) {
                $string = $this->createAdvancedInnerSearchString($string, $handler);
                if ($handler->hasDismax()) {
                    $oldString = $string;
                    $string = $handler->createBoostQueryString($string);

                    // If a boost was added, we don't want to highlight based on
                    // the boost query, so we should use the non-boosted version:
                    if ($highlight && $oldString != $string) {
                        $params->set('hl.q', $oldString);
                    }
                }
            } elseif ($handler->hasDismax()) {
                $params->set('qf', implode(' ', $handler->getDismaxFields()));
                $params->set('qt', $handler->getDismaxHandler());
                foreach ($handler->getDismaxParams() as $param) {
                    $params->add(reset($param), next($param));
                }
                if ($handler->hasFilterQuery()) {
                    $params->add('fq', $handler->getFilterQuery());
                }
            } else {
                $string = $handler->createSimpleQueryString($string);
            }
        }
        // Set an appropriate highlight field list when applicable:
        if ($highlight) {
            $filter = $handler ? $handler->getAllFields() : [];
            $params->add('hl.fl', $this->getFieldsToHighlight($filter));
        }
        $params->set('q', $string);
        return $params;
    }

    /**
     * Reduce components of query group to a search string of a simple query.
     *
     * This function implements the recursive reduction of a query group.
     *
     * @param AbstractQuery $component Component
     *
     * @return string
     *
     * @see self::reduceQueryGroup()
     */
    protected function reduceQueryGroupComponents(AbstractQuery $component)
    {
        if ($component instanceof QueryGroup) {
            $reduced = array_map(
                [$this, 'reduceQueryGroupComponents'], $component->getQueries()
            );
            $searchString = $component->isNegated() ? 'NOT ' : '';
            $reduced = array_filter(
                $reduced,
                function ($s) {
                    return '' !== $s;
                }
            );
            if ($reduced) {
                $searchString .= sprintf(
                    '(%s)', implode(" {$component->getOperator()} ", $reduced)
                );
            }
        } else {
            $searchString = $this->getNormalizedQueryString($component);
            $searchHandler = $this->getSearchHandler(
                $component->getHandler(),
                $searchString
            );
            if ($searchHandler->hasDismax()) {
                $searchString = array_pop($searchHandler->mungeValues($searchString, false));
            }
            if ($searchHandler && '' !== $searchString) {
                $searchString
                    = $this->createSearchString($searchString, $searchHandler);
            }
        }
        return $searchString;
    }
}
