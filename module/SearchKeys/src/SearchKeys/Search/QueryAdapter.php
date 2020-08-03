<?php

/**
 * Legacy adapter: search query parameters to AbstractQuery object
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2011.
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
 * @package  Search_Solr
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace SearchKeys\Search;

use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\Query;
use VuFindSearch\Query\QueryGroup;
use Zend\StdLib\Parameters;

/**
 * Legacy adapter: search query parameters to AbstractQuery object
 *
 * The class is a intermediate solution to translate the (possibly modified)
 * search query parameters in an object required by the new search system.
 *
 * @category VuFind
 * @package  Search_Solr
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
abstract class QueryAdapter extends \VuFind\Search\QueryAdapter
{
    /**
     * Convert a Query or QueryGroup into a human-readable display query.
     *
     * @param AbstractQuery $query     Query to convert
     * @param callable      $translate Callback to translate strings
     * @param callable      $showName  Callback to translate field names
     *
     * @return string
     */
    public static function display(AbstractQuery $query, $translate, $showName)
    {
        // Simple case -- basic query:
        if ($query instanceof Query) {
            return $query->getString();
        }

        // Complex case -- advanced query:
        return self::displayAdvanced($query, $translate, $showName);
    }

    /**
     * Support method for display() -- process advanced queries.
     *
     * @param AbstractQuery $query     Query to convert
     * @param callable      $translate Callback to translate strings
     * @param callable      $showName  Callback to translate field names
     *
     * @return string
     */
    protected static function displayAdvanced(AbstractQuery $query, $translate,
        $showName
    ) {
        // Groups and exclusions.
        $groups = $excludes = [];

        foreach ($query->getQueries() as $search) {
            if ($search instanceof QueryGroup) {
                $thisGroup = [];
                // Process each search group
                foreach ($search->getQueries() as $group) {
                    if ($group instanceof Query) {
                        // Build this group individually as a basic search
                        $thisGroup[]
                            = call_user_func($showName, $group->getHandler()) . ':'
                            . $group->getString();
                    } else {
                        throw new \Exception('Unexpected ' . get_class($group));
                    }
                }
                // Is this an exclusion (NOT) group or a normal group?
/*
                $str = join(
                    ' ' . call_user_func($translate, $search->getOperator())
                    . ' ', $thisGroup
                );
*/
                $str = join(
                    ' ', $thisGroup
                );
                if ($search->isNegated()) {
                    $excludes[] = $str;
                } else {
                    $groups[] = $str;
                }
            } else {
                throw new \Exception('Unexpected ' . get_class($search));
            }
        }

        // Base 'advanced' query
        $operator = call_user_func($translate, $query->getOperator());
/*
        $output = '(' . join(') ' . $operator . ' (', $groups) . ')';
*/
        $output = join(') ' . $operator . ' (', $groups);

        // Concatenate exclusion after that
        if (count($excludes) > 0) {
            $output .= ' ' . call_user_func($translate, 'NOT') . ' (('
                . join(') ' . call_user_func($translate, 'OR') . ' (', $excludes)
                . '))';
        }

        return $output;
    }
}
