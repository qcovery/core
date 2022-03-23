<?php
/**
 * MyResearch Controller
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace PAIAplus\Controller;


/**
 * Controller for the user account area.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class MyResearchController extends \VuFind\Controller\MyResearchController
{
    /**
     * Send list of checked out books to view
     *
     * @return mixed
     */
    public function checkedoutAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Display account blocks, if any:
        $this->addAccountBlocksToFlashMessenger($catalog, $patron);

        // Get the current renewal status and process renewal form, if necessary:
        $renewStatus = $catalog->checkFunction('Renewals', compact('patron'));
        $renewResult = $renewStatus
            ? $this->renewals()->processRenewals(
                $this->getRequest()->getPost(), $catalog, $patron
            )
            : [];

        // By default, assume we will not need to display a renewal form:
        $renewForm = false;

        // Get checked out item details:
        $result = $catalog->getMyTransactions($patron);

        // Get page size:
        $config = $this->getConfig();
        $limit = isset($config->Catalog->checked_out_page_size)
            ? $config->Catalog->checked_out_page_size : 50;

        // Build paginator if needed:
        if ($limit > 0 && $limit < count($result)) {
            $adapter = new \Zend\Paginator\Adapter\ArrayAdapter($result);
            $paginator = new \Zend\Paginator\Paginator($adapter);
            $paginator->setItemCountPerPage($limit);
            $paginator->setCurrentPageNumber($this->params()->fromQuery('page', 1));
            $pageStart = $paginator->getAbsoluteItemNumber(1) - 1;
            $pageEnd = $paginator->getAbsoluteItemNumber($limit) - 1;
        } else {
            $paginator = false;
            $pageStart = 0;
            $pageEnd = count($result);
        }

        $transactions = $hiddenTransactions = [];
        foreach ($result as $i => $current) {
            // Add renewal details if appropriate:
            $current = $this->renewals()->addRenewDetails(
                $catalog, $current, $renewStatus
            );
            if ($renewStatus && !isset($current['renew_link'])
                && $current['renewable']
            ) {
                // Enable renewal form if necessary:
                $renewForm = true;
            }

            // Build record driver (only for the current visible page):
            if ($i >= $pageStart && $i <= $pageEnd) {
                $transactions[] = $this->getDriverForILSRecord($current);
            } else {
                $hiddenTransactions[] = $current;
            }
        }

        $displayItemBarcode
            = !empty($config->Catalog->display_checked_out_item_barcode);

        $patron = $this->catalogLogin();
        $profileExpires = "not set";
        $profileNote = null;
        $patronStatus = null;
        if (is_array($patron)) {
            $profile = $catalog->getMyProfile($patron);
            $profileExpires = $profile['expires'];
            if ($this->showProfileNoteOnAllPages()) {
                $profileNote = $profile['note'];
            }
            if ($this->showPatronStatusOnAllPages()) {
                $patronStatus = $patron['status'];
            }
        }

        return $this->createViewModel(
            compact(
                'transactions', 'renewForm', 'renewResult', 'paginator',
                'hiddenTransactions', 'displayItemBarcode', 'profileExpires', 'profileNote', 'patronStatus'
            )
        );
    }

    /**
     * Login Action
     *
     * @return mixed
     */
    public function loginAction()
    {
        // If this authentication method doesn't use a VuFind-generated login
        // form, force it through:
        if ($this->getSessionInitiator()) {
            // Don't get stuck in an infinite loop -- if processLogin is already
            // set, it probably means Home action is forwarding back here to
            // report an error!
            //
            // Also don't attempt to process a login that hasn't happened yet;
            // if we've just been forced here from another page, we need the user
            // to click the session initiator link before anything can happen.
            if (!$this->params()->fromPost('processLogin', false)
                && !$this->params()->fromPost('forcingLogin', false)
            ) {
                $this->getRequest()->getPost()->set('processLogin', true);
                return $this->forwardTo('MyResearch', 'Home');
            }
        }

        // Make request available to view for form updating:
        $view = $this->createViewModel();

        // Check for multiple PAIA backends
        $paiaConfig = $this->getConfig('PAIA');
        $paiaBackends = [];
        foreach ($paiaConfig as $key => $value) {
            if (stristr($key, 'PAIA')) {
                $name = $key;
                if (isset($paiaConfig[$key]['name'])) {
                    $name = $paiaConfig[$key]['name'];
                }
                $docIdPattern = '';
                if (isset($paiaConfig[$key]['docIdPattern'])) {
                    $docIdPattern = $paiaConfig[$key]['docIdPattern'];
                }
                $paiaBackends[$key] = [
                    'name' => $name,
                    'docIdPattern' => $docIdPattern
                ];
            }
        }
        $view->paiaBackends = $paiaBackends;

        $view->request = $this->getRequest()->getPost();
        return $view;
    }

    public function recallsAction () {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Process cancel requests if necessary:
        $cancelStatus = $catalog->checkFunction('cancelHolds', compact('patron'));
        $view = $this->createViewModel();
        $view->cancelResults = $cancelStatus
            ? $this->holds()->cancelHolds($catalog, $patron) : [];
        // If we need to confirm
        if (!is_array($view->cancelResults)) {
            return $view->cancelResults;
        }

        // By default, assume we will not need to display a cancel form:
        $view->cancelForm = false;

        // Get held item details:
        $result = $catalog->getMyRecalls($patron);
        $recordList = [];
        $this->holds()->resetValidation();
        foreach ($result as $current) {
            // Add cancel details if appropriate:
            $current = $this->holds()->addCancelDetails(
                $catalog, $current, $cancelStatus
            );
            if ($cancelStatus && $cancelStatus['function'] != "getCancelHoldLink"
                && isset($current['cancel_details'])
            ) {
                // Enable cancel form if necessary:
                $view->cancelForm = true;
            }

            // Build record driver:
            $recordList[] = $this->getDriverForILSRecord($current);
        }

        // Get List of PickUp Libraries based on patron's home library
        try {
            $view->pickup = $catalog->getPickUpLocations($patron);
        } catch (\Exception $e) {
            // Do nothing; if we're unable to load information about pickup
            // locations, they are not supported and we should ignore them.
        }
        $view->recordList = $recordList;
        return $view;
    }

    public function holdsAction()
    {
        $view = parent::holdsAction();
        if ($this->showProfileNoteOnAllPages()) {
            $this->addProfileNoteToView($view);
        }
        if ($this->showPatronStatusOnAllPages()) {
            $this->addPatronStatusToView($view);
        }
        return $view;
    }

    public function finesAction()
    {
        $view = parent::finesAction();
        if ($this->showProfileNoteOnAllPages()) {
            $this->addProfileNoteToView($view);
        }
        if ($this->showPatronStatusOnAllPages()) {
            $this->addPatronStatusToView($view);
        }
        return $view;
    }

    public function changePasswordAction()
    {
        $view = parent::changePasswordAction();
        if ($this->showProfileNoteOnAllPages()) {
            $this->addProfileNoteToView($view);
        }
        if ($this->showPatronStatusOnAllPages()) {
            $this->addPatronStatusToView($view);
        }
        return $view;
    }

    public function profileAction()
    {
        $view = parent::profileAction();
        if ($this->showProfileNoteOnAllPages()) {
            $this->addProfileNoteToView($view);
        }
        if ($this->showPatronStatusOnAllPages()) {
            $this->addPatronStatusToView($view);
        }
        return $view;
    }

    private function showProfileNoteOnAllPages () {
        $paiaConfig = $this->serviceLocator->get('VuFind\Config\PluginManager')->get('PAIA');
        if (isset($paiaConfig['PAIA']['show_note_on_all_pages']) && $paiaConfig['PAIA']['show_note_on_all_pages']) {
            return true;
        }
        return false;
    }

    private function addProfileNoteToView (&$view) {
        $catalog = $this->getILS();
        $patron = $this->catalogLogin();
        if (is_array($patron)) {
            $profile = $catalog->getMyProfile($patron);
            $view->profileNote = $profile['note'];
        }
    }

    private function showPatronStatusOnAllPages () {
        $paiaConfig = $this->serviceLocator->get('VuFind\Config\PluginManager')->get('PAIA');
        if (isset($paiaConfig['PAIA']['show_patron_status_on_all_pages']) && $paiaConfig['PAIA']['show_patron_status_on_all_pages']) {
            return true;
        }
        return false;
    }

    private function addPatronStatusToView (&$view) {
        $patron = $this->catalogLogin();
        if (is_array($patron)) {
            $view->patronStatus = $patron['status'];
        }
    }
}
