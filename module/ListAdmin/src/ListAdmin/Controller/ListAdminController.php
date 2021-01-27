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
namespace ListAdmin\Controller;

use VuFind\Controller\AbstractBase;


/**
 * Controller for the user account area.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class ListAdminController extends AbstractBase
{
    public function migrateAction () {
        $translator = $this->serviceLocator->get('Zend\Mvc\I18n\Translator');
        $view = $this->createViewModel();

        $view->old_account = $this->params()->fromPost('old_account');
        $view->new_account = $this->params()->fromPost('new_account');

        if ($this->formWasSubmitted('submit', false)) {
            if (empty($view->old_account) || empty($view->new_account)) {
                $this->flashMessenger()->addMessage('bulk_error_missing', 'error');
                return $view;
            }

            if (!$this->checkAccount($view->old_account)) {
                $this->flashMessenger()->addMessage('Old user account does not exist', 'error');
                return $view;
            }

            if (!$this->checkAccount($view->new_account)) {
                $this->flashMessenger()->addMessage('New user account does not exist', 'error');
                return $view;
            }

            if ($lists = $this->migrateLists($view->old_account, $view->new_account)) {
                $this->flashMessenger()->addMessage($lists.' '.$translator->translate('lists migrated'), 'success');
            } else {
                $this->flashMessenger()->addMessage('No lists to migrate', 'success');
            }
        }

        return $view;
    }

    private function getAccount($account) {
        $userTable = $this->serviceLocator->get('VuFind\Db\Table\PluginManager')->get('user');
        $user = $userTable->select(
            function ($select) use ($account) {
                $select->where->equalTo('username', $account);
            }
        );
        return $user->current();
    }

    private function checkAccount($account) {
        $account = $this->getAccount($account);
        if (!empty($account)) {
            return true;
        }
        return false;
    }

    private function migrateLists($old_account, $new_account) {
        $oldAccount = $this->getAccount($old_account)->toArray();
        $newAccount = $this->getAccount($new_account)->toArray();

        $userListTable = $this->serviceLocator->get('VuFind\Db\Table\PluginManager')->get('userlist');
        if ($lists = $userListTable->update(array('user_id' => $newAccount['id']), array('user_id' => $oldAccount['id']))) {
            return $lists;
        }
        return false;
    }
}
