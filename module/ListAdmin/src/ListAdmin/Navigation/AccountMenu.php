<?php

/**
 * Account menu with ListAdmin checks
 *
 * PHP version 8
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
 * @package  ListAdmin
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace ListAdmin\Navigation;

use function in_array;

/**
 * Account menu extended by a check for ListAdmin users
 *
 * @category VuFind
 * @package  ListAdmin
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class AccountMenu extends \VuFind\Navigation\AccountMenu
{
    /**
     * ListAdmin configuration (ListAdmin.ini)
     *
     * @var array
     */
    protected array $listAdminConfig = [];

    /**
     * Set ListAdmin configuration
     *
     * @param array $config ListAdmin configuration
     *
     * @return void
     */
    public function setListAdminConfig(array $config): void
    {
        $this->listAdminConfig = $config;
    }

    /**
     * Check whether the ListAdmin menu should be rendered: only for logged-in users that
     * are listed in ListAdmin.ini ([ListAdmin] admins[]).
     *
     * @return bool
     */
    public function checkListAdminMode(): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        $admins = array_map(
            'strval',
            (array)($this->listAdminConfig['ListAdmin']['admins'] ?? [])
        );
        return in_array($user->getUsername(), $admins, true);
    }
}
