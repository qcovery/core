<?php
/**
 * Database table plugin manager
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
 * @package  Db_Table
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
namespace CaLief\Db\Table;

/**
 * Database table plugin manager
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
class PluginManager extends \VuFind\Db\Table\PluginManager
{
    /**
     * Default plugin aliases.
     *
     * @var array
     */
    protected $aliases = [
        'changetracker' => 'VuFind\Db\Table\ChangeTracker',
        'comments' => 'VuFind\Db\Table\Comments',
        'caliefadmin' => 'CaLief\Db\Table\CaliefAdmin',
        'externalsession' => 'VuFind\Db\Table\ExternalSession',
        'oairesumption' => 'VuFind\Db\Table\OaiResumption',
        'record' => 'VuFind\Db\Table\Record',
        'resource' => 'VuFind\Db\Table\Resource',
        'resourcetags' => 'VuFind\Db\Table\ResourceTags',
        'search' => 'VuFind\Db\Table\Search',
        'session' => 'VuFind\Db\Table\Session',
        'tags' => 'VuFind\Db\Table\Tags',
        'user' => 'VuFind\Db\Table\User',
        'usercard' => 'VuFind\Db\Table\UserCard',
        'usercalief' => 'CaLief\Db\Table\UserCalief',
        'usercalieflog' => 'CaLief\Db\Table\UserCaliefLog',
        'userlist' => 'VuFind\Db\Table\UserList',
        'userresource' => 'VuFind\Db\Table\UserResource',
    ];

    /**
     * Default plugin factories.
     *
     * @var array
     */
    protected $factories = [
        'CaLief\Db\Table\CaliefAdmin' => 'VuFind\Db\Table\GatewayFactory',
        'CaLief\Db\Table\UserCalief' => 'VuFind\Db\Table\GatewayFactory',
        'CaLief\Db\Table\UserCaliefLog' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\ChangeTracker' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\Comments' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\ExternalSession' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\OaiResumption' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\Record' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\Resource' => 'VuFind\Db\Table\ResourceFactory',
        'VuFind\Db\Table\ResourceTags' => 'VuFind\Db\Table\CaseSensitiveTagsFactory',
        'VuFind\Db\Table\Search' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\Session' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\Tags' => 'VuFind\Db\Table\CaseSensitiveTagsFactory',
        'VuFind\Db\Table\User' => 'VuFind\Db\Table\UserFactory',
        'VuFind\Db\Table\UserCard' => 'VuFind\Db\Table\GatewayFactory',
        'VuFind\Db\Table\UserList' => 'VuFind\Db\Table\UserListFactory',
        'VuFind\Db\Table\UserResource' => 'VuFind\Db\Table\GatewayFactory',
    ];
}
