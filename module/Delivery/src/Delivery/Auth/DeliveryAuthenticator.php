<?php
/**
 * Class for managing ILS-specific authentication.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2007.
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
 * @package  Authentication
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace Delivery\Auth;

use VuFind\Auth\ILSAuthenticator;
use VuFind\Auth\Manager;
use VuFind\Config\PluginManager as ConfigManager;
use PAIAplus\ILS\Connection as ILSConnection;
use Delivery\ConfigurationManager;

/**
 * Class for managing ILS-specific authentication.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Demian Katz <demian.katz@villanova.edu>
 *  
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class DeliveryAuthenticator extends ILSAuthenticator
{
    protected $configurationManager;

    protected $table;

    protected $user;

    /**
     * Constructor
     *
     * @param Manager       $auth    Auth manager
     * @param ILSConnection $catalog ILS connection
     */
    public function __construct(Manager $auth, ILSConnection $catalog,
        ConfigManager $configManager, $table)
    {
        $this->configurationManager = new ConfigurationManager($configManager);
        $this->setTable($table);
        parent::__construct($auth, $catalog);
    }

    /**
     * Get access to the user table.
     *
     * @return \VuFind\Db\Table\User
     */
    protected function getAllowedPatronTypes($deliveryDomain)
    {
        $this->configurationManager->setConfigurations($deliveryDomain);
        $config = $this->configurationManager->getMainConfig();
        $allowedTypes = $config['allowed'];
        if (!is_array($allowedTypes)) {
            $allowedTypes = [];
        }
        return $allowedTypes;
    }

    /**
     * Get access to the user table.
     *
     * @return \VuFind\Db\Table\User
     */
    protected function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Get access to the user table.
     *
     * @return \VuFind\Db\Table\User
     */
    protected function getTable()
    {
        return $this->table;
    }

    /**
     * Get a database table object.
     *
     * @param string $table Name of table to retrieve
     *
     * @return \VuFind\Db\Table\Gateway
     */
    private function extractUserType($rawType)
    {
        list(,,$type) = explode(':', $rawType);
        return $type;
    }

    /**
     * Get stored catalog credentials for the current user.
     *
     * Returns associative array of cat_username and cat_password if they are
     * available, false otherwise. This method does not verify that the credentials
     * are valid.
     *
     * @return array|bool
     */
    public function authenticate($deliveryDomain = 'main', $asAdmin = false)
    {
        if (!$user = $this->auth->isLoggedIn()) {
            return 'not_logged_in';
        }

        $patron = $this->storedCatalogLogin();
        $expireDate = new \DateTime($patron['expires']);
        $today = new \DateTime('today');
        if ($expireDate < $today) {
            return 'not_authorized';
        }

        $patronTypes = array_map([$this, 'extractUserType'], $patron['type']);
        $allowedTypes = $this->getAllowedPatronTypes($deliveryDomain);

        if (!empty(array_intersect($patronTypes, $allowedTypes))) {
            $userDeliveryTable = $this->getTable();
            if (!is_object($userDeliveryTable->get($user->id))) {
                $userDeliveryTable->createRowForUserId($user->id, $user->email);
            }
            $deliveryUser = $userDeliveryTable->get($user->id);
            $user->delivery_email = $deliveryUser->delivery_email;
            $user->user_delivery_id = $deliveryUser->id;
            $user->patron_types = implode(', ', $patronTypes);
            if ($asAdmin) {
                $user->is_admin = $deliveryUser->is_admin;
            }
            $this->user = $user;
            if (!$asAdmin || $user->is_admin == 'y') {
                return 'authorized';
            }
        }
        return 'not_authorized';
    }

    /**
     * Get stored catalog credentials for the current user.
     *
     * Returns associative array of cat_username and cat_password if they are
     * available, false otherwise. This method does not verify that the credentials
     * are valid.
     *
     * @return array|bool
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getDeliveryDomains()
    {
        return $this->configurationManager->getDeliveryDomains();
    }

    public function getTemplateParams($deliveryDomain)
    {
        $this->configurationManager->setConfigurations($deliveryDomain);
        $config = $this->configurationManager->getMainConfig();
        $templateParams = [];
        $templateParams['show_home'] = $config['template_show_home'] ?: '';
        $templateParams['title'] = $config['template_title'] ?: '';
        $templateParams['text'] = $config['template_text'] ?: '';
        $templateParams['icon'] = $config['template_icon'] ?: '';
        $templateParams['belugino'] = $config['template_belugino'] ?: '';
        return $templateParams;
    }
}
