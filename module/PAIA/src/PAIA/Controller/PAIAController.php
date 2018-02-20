<?php
/**
 * PAIA Controller
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace PAIA\Controller;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use VuFind\Controller\AbstractSearch;
use CaLief\Classes\DodOrder;
use PAIA\PAIAHelper;

/**
 * Controller for the user account area.
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class PAIAController extends AbstractSearch implements ServiceLocatorAwareInterface
{        
    protected $paiaHelper;
    protected $serviceLocator;
    protected $serviceLocatorAwareInterface;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paiaHelper = new PAIAHelper;
    }

   /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        if ($serviceLocator instanceof ServiceLocatorAwareInterface) {
            $this->serviceLocatorAwareInterface = $serviceLocator;
        } else {
            $this->serviceLocator = $serviceLocator;
        }
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator() {
        return $this->serviceLocator;
    }

    
    function availabilityAction() {
        return $this->createViewModel();
    }
	
	function electronicavailabilityAction() {
        return $this->createViewModel();
    }
}
