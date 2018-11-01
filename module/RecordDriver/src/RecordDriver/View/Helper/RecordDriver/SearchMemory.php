<?php
/**
 * View helper for remembering recent user searches/parameters.
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace RecordDriver\View\Helper\RecordDriver;

use VuFind\Search\Memory;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for remembering recent user searches/parameters.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class SearchMemory extends \VuFind\View\Helper\Root\SearchMemory
{


    /**
     * If a previous search is recorded in the session, return the link url of it;
     * otherwise, return a blank string.
     *
     * @return string
     */
    public function getLastSearchLinkUrl()
    {
        $last = $this->memory->retrieveSearch();
        if (!empty($last)) {
            $escaper = $this->getView()->plugin('escapeHtml');
            return $escaper($last);
        }
        return '';
    }
}
