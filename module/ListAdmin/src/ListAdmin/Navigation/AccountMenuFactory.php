<?php

/**
 * Factory for the ListAdmin account menu
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

use Psr\Container\ContainerInterface;

/**
 * Factory for the ListAdmin account menu
 *
 * @category VuFind
 * @package  ListAdmin
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class AccountMenuFactory extends \VuFind\Navigation\AccountMenuFactory
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        $menu = parent::__invoke($container, $requestedName, $options);
        $menu->setListAdminConfig(
            $container->get(\VuFind\Config\ConfigManagerInterface::class)
                ->getConfigArray('ListAdmin')
        );
        return $menu;
    }
}
