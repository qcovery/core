<?php
/**
 * Authentication view helper
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Delivery\View\Helper\Delivery;

use Delivery\Auth\DeliveryAuthenticator;

/**
 * Authentication view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Authenticator extends \Zend\View\Helper\AbstractHelper
{
    protected $authenticator;

    /**
     * Constructor
     *
     * @param \VuFind\Auth\Manager          $manager          Authentication manager
     * @param \VuFind\Auth\ILSAuthenticator $ilsAuthenticator ILS Authenticator
     */
    public function __construct(DeliveryAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function isAuthorized($deliveryDomain = 'main', $asAdmin = false)
    {
        $status = $this->authenticator->authenticate($deliveryDomain, $asAdmin);
        return ($status == 'authorized');
    }

    public function getDeliveryDomains()
    {
        return $this->authenticator->getDeliveryDomains();
    }

    public function getTemplateParams($deliveryDomain)
    {
        return $this->authenticator->getTemplateParams($deliveryDomain);
    }
}
