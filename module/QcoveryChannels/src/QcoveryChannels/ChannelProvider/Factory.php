<?php
/**
 * Factory for ChannelProvider plugins.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2016.
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
 * @package  Channels
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace QcoveryChannels\ChannelProvider;

use Zend\ServiceManager\ServiceManager;

/**
 * Factory for ChannelProvider plugins.
 *
 * @category VuFind
 * @package  Channels
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 *
 * @codeCoverageIgnore
 */
class Factory extends \VuFind\ChannelProvider\Factory
{
    /**
     * Construct the Facets channel provider.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Topics
     */
    public static function getTopics(ServiceManager $sm)
    {
        return new Topics(
            $sm->get('VuFind\Search\Results\PluginManager'),
            $sm->get('ControllerPluginManager')->get('url')
        );
    }
}
