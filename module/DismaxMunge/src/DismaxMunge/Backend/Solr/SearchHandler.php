<?php

/**
 * VuFind SearchHandler.
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

/**
 * VuFind SearchHandler.
 *
 * The SearchHandler implements the rule-based translation of a user search
 * query to a SOLR query string.
 *
 * @category VuFind
 * @package  Search
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   David Maus <maus@hab.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class SearchHandler extends \VuFindSearch\Backend\Solr\SearchHandler
{
    /**
     * Return the munge values for specified search string.
     *
     * If optional argument $tokenize is true tokenize the search string.
     *
     * @param string $search   Search string
     * @param bool   $tokenize Tokenize the search string?
     *
     * @return string
     */
    public function customMunge($search)
    {
        foreach ($this->specs['CustomMunge'] as $mungeName => $mungeOps) {
            foreach ($mungeOps as $operation) {
                switch ($operation[0]) {
                case 'append':
                    $search .= $operation[1];
                    break;
                case 'lowercase':
                    $search = strtolower($search);
                    break;
                case 'uppercase':
                    $search = strtoupper($search);
                    break;
                case 'ucfirst':
                    $search = ucfirst($search);
                    break;
                case 'preg_replace':
                    $search = preg_replace(
                        $operation[1], $operation[2], $search
                    );
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf('Unknown munge operation: %s', $operation[0])
                    );
                }
            }
        }
        return $search;
    }
}
