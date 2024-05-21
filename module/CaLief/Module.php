<?php
/**
 * Template for ZF2 module for storing local overrides.
 *
 * PHP version 5
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Module
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/dmj/vf2-proxy
 */
namespace CaLief;
use Laminas\ModuleManager\ModuleManager,
    Laminas\Mvc\MvcEvent,
    Laminas\ModuleManager\Feature\ConfigProviderInterface,
    Laminas\Db\ResultSet\ResultSet,
    Laminas\Db\TableGateway\TableGateway;
use CaLief\CaLief\CaLiefHelper;
use CaLief\Db\Table\UserCalief;
use CaLief\Model\UserCaliefModel;
use CaLief\Db\Table\CaliefAdmin;
use CaLief\Model\CaliefAdminModel;
use VuFind\Db\Table\Gateway;

/**
 * Template for ZF2 module for storing local overrides.
 *
 * @category VuFind2
 * @package  Module
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/dmj/vf2-proxy
 */
class Module implements ConfigProviderInterface
{
    /**
     * Get module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * Initialize the module
     *
     * @param ModuleManager $m Module manager
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function init(ModuleManager $m)
    {
    }

    /**
     * Bootstrap the module
     *
     * @param MvcEvent $e Event
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onBootstrap(MvcEvent $e)
    {
    }
    
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'calief' => function($sm) {
                    return new CaLiefHelper($sm);
                },
            ),
        );
    }
    
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'CaLief\DB\Table\UserCalief' =>  function($sm) {
                    $tableGateway = $sm->get('UserCaliefGateway');
                    $table = new UserCalief($tableGateway);
                    return $table;
                },
                'UserCaliefGateway' => function ($sm) {
                    $dbAdapter = $sm->get('VuFind\DbAdapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new UserCaliefModel());
                    return new TableGateway('usercalief', $dbAdapter, null, $resultSetPrototype);
                },
                'CaLief\DB\Table\CaliefAdmin' =>  function($sm) {
                    $tableGateway = $sm->get('CaliefAdminGateway');
                    $table = new CaliefAdmin($tableGateway);
                    return $table;
                },
                'CaliefAdminGateway' => function ($sm) {
                    $dbAdapter = $sm->get('VuFind\DbAdapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new CaliefAdminModel());
                    return new TableGateway('caliefadmin', $dbAdapter, null, $resultSetPrototype);
                },
            ),
        );
    }
}
