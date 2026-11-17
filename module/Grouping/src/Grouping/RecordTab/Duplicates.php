<?php

/**
 * Record tab listing the other records of the same group.
 *
 * PHP version 8
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */

namespace Grouping\RecordTab;

/**
 * Record tab listing the other records of the same group.
 *
 * Needs a RecordTab/duplicates.phtml template in the theme; the records are
 * available via $this->driver->getDuplicates().
 *
 * @category VuFind
 * @package  Grouping
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
class Duplicates extends \VuFind\RecordTab\AbstractBase
{
    /**
     * Is this tab enabled?
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Constructor
     *
     * @param bool $enabled is this tab enabled?
     */
    public function __construct($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * Is this tab active?
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->enabled
            && parent::isActive()
            && !empty($this->getRecordDriver()->tryMethod('getDuplicates'));
    }

    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Duplicates';
    }
}
