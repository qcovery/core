<?php
/**
 * Wrapper class for handling logged-in user in session.
 *
 * PHP version 5
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Authentication
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
namespace PAIA\Auth;

use VuFind\Cookie\CookieManager;
use VuFind\Db\Row\User as UserRow;
use VuFind\Db\Table\User as UserTable;
use VuFind\Exception\Auth as AuthException;
use Zend\Config\Config;
use Zend\Session\SessionManager;
use Zend\Validator\Csrf;
use VuFind\Auth\PluginManager;
use PAIA\Config\PAIAConfigService;

/**
 * Wrapper class for handling logged-in user in session.
 *
 * @category VuFind2
 * @package  Authentication
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
class Manager extends \VuFind\Auth\Manager
{

    private $ilsConnection;

    /**
     * Constructor
     *
     * @param Config         $config         VuFind configuration
     * @param UserTable      $userTable      User table gateway
     * @param SessionManager $sessionManager Session manager
     * @param PluginManager  $pm             Authentication plugin manager
     * @param CookieManager  $cookieManager  Cookie manager
     */
    public function __construct(Config $config, UserTable $userTable,
                                SessionManager $sessionManager, PluginManager $pm,
                                CookieManager $cookieManager, \VuFind\ILS\Connection $ilsConnection
    ) {
        // Store dependencies:
        $this->config = $config;
        $this->userTable = $userTable;
        $this->sessionManager = $sessionManager;
        $this->pluginManager = $pm;
        $this->cookieManager = $cookieManager;

        // Set up session:
        $this->session = new \Zend\Session\Container('Account', $sessionManager);

        // Set up CSRF:
        $this->csrf = new Csrf(
            [
                'session' => new \Zend\Session\Container('csrf', $sessionManager),
                'salt' => isset($this->config->Security->HMACkey)
                    ? $this->config->Security->HMACkey : 'VuFindCsrfSalt',
            ]
        );

        // Initialize active authentication setting (defaulting to Database
        // if no setting passed in):
        $method = isset($config->Authentication->method)
            ? $config->Authentication->method : 'Database';
        $this->legalAuthOptions = [$method];   // mark it as legal
        $this->setAuthMethod($method);              // load it

        $this->ilsConnection = $ilsConnection;
    }

    /**
     * Attempt to log in the user to the ILS, and save credentials if it works.
     *
     * @param string $username Catalog username
     * @param string $password Catalog password
     *
     * Returns associative array of patron data on success, false on failure.
     *
     * @return array|bool
     */
    public function newCatalogLogin($username, $password, $isil)
    {
        try {
            $catalog = $this->getILS();
            $result = $catalog->patronLogin($username, $password, $isil);
        } catch (ILSException $e) {
            return false;
        }
        if ($result) {
            $user = $this->isLoggedIn();
            if ($user) {
                $user->saveCredentials($username, $password, $isil);
                $this->updateSession($user);
                $this->ilsAccount = $result;    // cache for future use
            }
            return $result;
        }
        return false;
    }


    /**
     * Delete saved user credentials to switch to a different catalog.
     *
     */
    public function deleteCatalogLogin()
    {
        $user = $this->isLoggedIn();
        if ($user) {
            $user->clearCredentials();
            $user->saveCredentials(NULL, NULL, NULL);
            $this->updateSession($user);
        }
    }

    /**
     * Get the ILS connection.
     *
     * @return \VuFind\ILS\Connection
     */
    protected function getILS()
    {
        return $this->ilsConnection;
    }


    /**
     * Log the current user into the catalog using stored credentials; if this
     * fails, clear the user's stored credentials so they can enter new, corrected
     * ones.
     *
     * Returns associative array of patron data on success, false on failure.
     *
     * @return array|bool
     */
    public function storedCatalogLogin()
    {
        // Fail if no username is found, but allow a missing password (not every ILS
        // requires a password to connect).
        if (($user = $this->isLoggedIn()) && !empty($user->cat_username)) {
            // Do we have a previously cached ILS account?
            if (isset($this->ilsAccount[$user->cat_username])) {
                return $this->ilsAccount[$user->cat_username];
            }
            $paiaConfigService = new PAIAConfigService();
            $patron = $this->ilsConnection->patronLogin(
                $user->cat_username, $user->getCatPassword(), $paiaConfigService->getIsil()
            );
            if (empty($patron)) {
                // Problem logging in -- clear user credentials so they can be
                // prompted again; perhaps their password has changed in the
                // system!
                $user->clearCredentials();
            } else {
                // cache for future use
                $this->ilsAccount[$user->cat_username] = $patron;
                return $patron;
            }
        }

        return false;
    }
}
