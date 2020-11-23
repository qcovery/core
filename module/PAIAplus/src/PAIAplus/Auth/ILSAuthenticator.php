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
namespace PAIAplus\Auth;

use VuFind\Auth\Manager as AuthManager;
use VuFind\ILS\Connection as ILSConnection;

/**
 * Class for managing ILS-specific authentication.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class ILSAuthenticator extends \VuFind\Auth\ILSAuthenticator
{
    /**
     * Auth manager
     *
     * @var Manager
     */
    protected $auth;

    /**
     * ILS connector
     *
     * @var ILSConnection
     */
    protected $catalog;

    /**
     * Cache for ILS account information (keyed by username)
     *
     * @var array
     */
    protected $ilsAccount = [];

    /**
     * Constructor
     *
     * @param Manager       $auth    Auth manager
     * @param ILSConnection $catalog ILS connection
     */
    public function __construct(AuthManager $auth, ILSConnection $catalog)
    {
        $this->auth = $auth;
        $this->catalog = $catalog;
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
    public function setILSDomain($firstDomain, $secondDomain = '')
    {
        // Fail if no username is found, but allow a missing password (not every ILS
        // requires a password to connect).
        if ($this->auth->checkCapability('setILSDomain')) {
            return $this->auth->getDriver()->setILSDomain($firstDomain, $secondDomain);
        }
        return false;
    }
}
