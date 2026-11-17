<?php

/**
 * Beluga SolrMarc record driver with grouping support.
 *
 * PHP version 8
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace Grouping\RecordDriver;

/**
 * Beluga SolrMarc record driver with grouping support.
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
class SolrMarc extends \RecordDriver\RecordDriver\SolrMarc
{
    use GroupingTrait;
}
