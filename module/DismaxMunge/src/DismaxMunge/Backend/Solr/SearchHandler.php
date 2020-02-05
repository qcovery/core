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
     * Known configuration keys.
     *
     * @var array
     */
    protected static $configKeys = [
        'CustomMunge', 'DismaxFields', 'DismaxHandler', 'QueryFields',
        'DismaxParams', 'FilterQuery', 'DismaxMunge'
    ];

    /**
     * Constructor.
     *
     * @param array  $spec                 Search handler specification
     * @param string $defaultDismaxHandler Default dismax handler (if no
     * DismaxHandler set in specs).
     *
     * @return void
     */
    public function __construct(array $spec, $defaultDismaxHandler = 'dismax')
    {
        foreach (self::$configKeys as $key) {
            $this->specs[$key] = $spec[$key] ?? [];
        }
        // Set dismax handler to default if not specified:
        if (empty($this->specs['DismaxHandler'])) {
            $this->specs['DismaxHandler'] = $defaultDismaxHandler;
        }
        // Set default mm handler if necessary:
        $this->setDefaultMustMatch();
    }

    /**
     * Apply standard pre-processing to the query string.
     *
     * @param string $search Search string
     *
     * @return string
     */
    public function preprocessQueryString($search)
    {
        // Apply Dismax munging, if required:
        if ($this->hasDismax()) {
            return $this->dismaxMunge($search);
        }
        return $search;
    }

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
    protected function mungeValues($search, $tokenize = true)
    {
        if ($tokenize) {
            $tokens = $this->tokenize($search);
            $mungeValues = [
                'onephrase' => sprintf(
                    '"%s"', str_replace('"', '', implode(' ', $tokens))
                ),
                'and' => implode(' AND ', $tokens),
                'or'  => implode(' OR ', $tokens),
                'identity' => $search,
            ];
        } else {
            $mungeValues = [
                'and' => $search,
                'or'  => $search,
            ];
            // If we're skipping tokenization, we just want to pass $lookfor through
            // unmodified (it's probably an advanced search that won't benefit from
            // tokenization).  We'll just set all possible values to the same thing,
            // except that we'll try to do the "one phrase" in quotes if possible.
            // IMPORTANT: If we detect a boolean NOT, we MUST omit the quotes. We
            // also omit quotes if the phrase is already quoted or if there is no
            // whitespace (in which case phrase searching is pointless and might
            // interfere with wildcard behavior):
            if (strstr($search, '"') || strstr($search, ' NOT ')
                || !preg_match('/\s/', $search)
            ) {
                $mungeValues['onephrase'] = $search;
            } else {
                $mungeValues['onephrase'] = sprintf('"%s"', $search);
            }
        }

        $mungeValues['identity'] = $search;

        foreach ($this->specs['CustomMunge'] as $mungeName => $mungeOps) {
            $mungeValues[$mungeName] = $search;
            foreach ($mungeOps as $operation) {
                $mungeValues[$mungeName]
                    = $this->customMunge($mungeValues[$mungeName], $operation);
            }
        }
        return $mungeValues;
    }

    /**
     * Apply custom search string munging to a Dismax query.
     *
     * @param string $search searchstring
     *
     * @return string
     */
    protected function dismaxMunge($search)
    {
        foreach ($this->specs['DismaxMunge'] as $operation) {
            $search = $this->customMunge($search, $operation);
        }
        return $search;
    }

    /**
     * Apply a munge operation to a search string.
     *
     * @param string $string    string to munge
     * @param array  $operation munge operation
     *
     * @return string
     */
    protected function customMunge($string, $operation)
    {
        switch ($operation[0]) {
        case 'append':
            $string .= $operation[1];
            break;
        case 'lowercase':
            $string = strtolower($string);
            break;
        case 'preg_replace':
            $string = preg_replace(
                $operation[1], $operation[2], $string
            );
            break;
        case 'ucfirst':
            $string = ucfirst($string);
            break;
        case 'uppercase':
            $string = strtoupper($string);
            break;
        default:
            throw new \InvalidArgumentException(
                sprintf('Unknown munge operation: %s', $operation[0])
            );
        }
        return $string;
    }

    /**
     * Return query string for specified search string.
     *
     * If optional argument $advanced is true the search string contains
     * advanced lucene query syntax.
     *
     * @param string $search   Search string
     * @param bool   $advanced Is the search an advanced search string?
     *
     * @return string
     */
    protected function createQueryString($search, $advanced = false)
    {
        // If this is a basic query and we have Dismax settings (or if we have
        // Extended Dismax available), let's build a Dismax subquery to avoid
        // some of the ugly side effects of our Lucene query generation logic.
        if (($this->hasExtendedDismax() || !$advanced) && $this->hasDismax()) {
            $query = $this->dismaxSubquery(
                $this->dismaxMunge($search)
            );
        } else {
            $mungeRules  = $this->mungeRules();
            // Do not munge w/o rules
            if ($mungeRules) {
                $mungeValues = $this->mungeValues($search, !$advanced);
                $query       = $this->munge($mungeRules, $mungeValues);
            } else {
                $query = $search;
            }
        }
        if ($this->hasFilterQuery()) {
            $query = sprintf('(%s) AND (%s)', $query, $this->getFilterQuery());
        }
        return "($query)";
    }
}
