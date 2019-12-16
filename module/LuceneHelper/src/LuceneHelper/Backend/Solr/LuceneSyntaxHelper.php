<?php

/**
 * Lucene query syntax helper class.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2016.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
namespace LuceneHelper\Backend\Solr;

/**
 * Lucene query syntax helper class.
 *
 * @category VuFind
 * @package  Search
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   David Maus <maus@hab.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org
 */
class LuceneSyntaxHelper extends \VuFindSearch\Backend\Solr\LuceneSyntaxHelper
{
    /**
     * Normalize various problems found in unquoted text within the query.
     *
     * @param string $input String to normalize
     *
     * @return string
     */
    protected function normalizeUnquotedText($input)
    {
        $input = str_replace(['/', '?', '*', '~', '{', '}', '^'], '', $input);
        return parent::normalizeUnquotedText($input);
    }
}
