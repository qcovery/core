<?php
/**
 * Delivery Controller
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
namespace Delivery\Controller;

# use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use VuFind\Controller\AbstractBase;
use Delivery\AvailabilityHelper;
use Delivery\DataHandler;

/**
 * Controller for the user account area.
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class DeliveryController extends AbstractBase
{

    protected $deliveryAuthenticator;
    protected $dataHandler;
    protected $deliveryTable;
    protected $deliveryGlobalConfig;

    protected $user;

    /**
     * Constructor
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);
        $this->deliveryAuthenticator = $sm->get('Delivery\Auth\DeliveryAuthenticator');
        $this->deliveryGlobalConfig = $this->getConfig('deliveryGlobal')->toArray();
    }

    /**
     * Get a database table object.
     *
     * @param string $table Name of table to retrieve
     *
     * @return \VuFind\Db\Table\Gateway
     */
    public function getTable($table)
    {
        return $this->serviceLocator->get('Delivery\Db\Table\PluginManager')->get($table);
    }
    
    private function authenticate($asAdmin = false)
    {
        $message = $this->deliveryAuthenticator->authenticate($asAdmin);
        if ($message != 'not_logged_in') {
            $this->user = $this->deliveryAuthenticator->getUser();
        }
        return $message;
    }

    /**
     * Home action
     *
     * @return mixed
     */
    public function homeAction()
    {
        $message = $this->authenticate();
        if ($message != 'authorized') {
            return $this->forwardTo('MyResearch', 'Profile');
        }

        $deliveryTable = $this->getTable('delivery');
        $listData = $deliveryTable->getDeliveryList($this->user->user_delivery_id);

        $error = $this->updateDeliveryMail();

        $view = $this->createViewModel();
        $view->message = $message;
        $view->error = $error;
        $view->catalog_id = $this->user->cat_id;
        $view->delivery_email = $this->user->delivery_email;
        $view->name = trim($this->user->firstname . ' ' . $this->user->lastname);
        $view->listData = $listData;
        return $view;
    }

    /**
     * Order action
     *
     * @return mixed
     */
    public function orderAction()
    {
        $message = $this->authenticate();
        if ($message != 'authorized') {
            return $this->forwardTo('MyResearch', 'Profile');
        }

        $id = $this->params()->fromQuery('id') ?? $this->params()->fromPost('id');
        $searchClassId = $this->params()->fromQuery('searchClassId') ?? $this->params()->fromPost('searchClassId');
        if (empty($id)) {
            return $this->forwardTo('Delivery', 'Home');
        }

        $orderDataConfig = $this->getConfig('deliveryOrderData')->toArray();
        $this->dataHandler = new DataHandler($this->serviceLocator->get('Delivery\Driver\PluginManager'), $this->params(), $orderDataConfig, $this->deliveryGlobalConfig);

        $errors = $missingFields = [];
        
        if ($error = $this->updateDeliveryMail()) {
            $errors[] = $error;
        }

        if (!empty($this->params()->fromPost('order'))) {
            if ($this->dataHandler->sendOrder($this->user)) {
                $this->dataHandler->insertOrderData($this->user, $this->getTable('delivery'));
                return $this->forwardTo('Delivery', 'List');
            } else {
                $errors = $this->dataHandler->getErrors();
                $missingFields = $this->dataHandler->getMissingFields();
            }
        }
 
        $driver = $this->getRecordLoader()->load($id, $searchClassId);
        $availabilityConfig = $this->getConfig('deliveryAvailability');
        $AvailabilityHelper = new AvailabilityHelper($driver, $availabilityConfig['checkparent']);

        if ($parentId = $AvailabilityHelper->getParentId()) {
            $searchClassId = DEFAULT_SEARCH_BACKEND;
            $parentDriver = $this->getRecordLoader()->load($parentId, $searchClassId);
            $AvailabilityHelper = new AvailabilityHelper($parentDriver, $availabilityConfig['default']);
        } else {
            $AvailabilityHelper = new AvailabilityHelper($driver, $availabilityConfig['default']);
        }

        $signatureCount = $this->deliveryGlobalConfig['Order']['collectedCallnumbers'] ?: 1;
        $signatureList = array_slice($AvailabilityHelper->getSignatureList(), 0 , $signatureCount);
//$signatureList = $AvailabilityHelper->getSignatureList();
        $signature = implode(', ', $signatureList);

        $preset = [];
        if ($this->deliveryGlobalConfig['Order']['presetCallnumbers'] == 'y') {
            $preset = ['signature' => $signature];
        }

        if (empty($signature)) {
            return $this->forwardTo('Delivery', 'Home');
       	}

        $view = $this->createViewModel();

        $view->errors = $errors;
        $view->missingFields = $missingFields;
        $this->dataHandler->setSolrDriver($driver);
        $this->dataHandler->collectData($preset);

        $formData = $this->dataHandler->getFormData();
        $infoData = $this->dataHandler->getInfoData();

        $view->id = $id;
        $view->searchClassId = $searchClassId;
        $view->formTitle = $formData['title'];
        $view->formFields = $formData['fields'];
        $view->checkboxFields = $formData['checkbox'];
        $view->infoTitle = $infoData['title'];
        $view->infoFields = $infoData['fields'];
        $view->catalog_id = $this->user->cat_id;
        $view->delivery_email = $this->user->delivery_email;
        $view->name = trim($this->user->firstname . ' ' . $this->user->lastname);
        return $view;
    }

    private function sendDeliveryMail($emailType)
    {
        $mailer = $this->serviceLocator->get('VuFind\Mailer');
        $mailConfig = $this->deliveryGlobalConfig['Email'];

        $mailTo = $this->user->delivery_email;
        $mailFrom = $mailConfig['mailFrom'];
        $admin = $mailConfig['deliveryAdmin'];
        if ($emailType == 'request') {
            $mailer->send($mailTo, $mailFrom, $mailConfig['requestSubject'], $mailConfig['requestText']);
            $mailer->send($admin, $mailFrom, $mailConfig['requestSubject'], $mailConfig['requestText']);
        } elseif ($emailType == 'revoke') {
            $mailer->send($mailTo, $mailFrom, $mailConfig['revokeSubject'], $mailConfig['revokeText']);
            $mailer->send($admin, $mailFrom, $mailConfig['revokeSubject'], $mailConfig['revokeText']);
        } elseif ($emailType == 'refuse') {
            $mailer->send($mailTo, $mailFrom, $mailConfig['refuseSubject'], $mailConfig['refuseText']);
            $mailer->send($admin, $mailFrom, $mailConfig['refuseSubject'], $mailConfig['refuseText']);
        } elseif ($emailType == 'authorize') {
            $mailer->send($mailTo, $mailFrom, $mailConfig['authorizeSubject'], $mailConfig['authorizeText']);
            $mailer->send($admin, $mailFrom, $mailConfig['authorizeSubject'], $mailConfig['authorizeText']);
        }
    }

    private function updateDeliveryMail()
    {
        $error = '';
        $deliveryEmail = $this->params()->fromPost('delivery_email');
        $update = $this->params()->fromPost('update_email');
        if (!empty($update) && !empty($deliveryEmail)) {
            if ($deliveryEmail != $this->user->delivery_email && $this->checkEmail($deliveryEmail)) {
                $userDeliveryTable = $this->getTable('userdelivery');
                $userDeliveryTable->update(['delivery_email' => $deliveryEmail], ['user_id' => $this->user->id]);
                $this->authenticate();
            } else {
                $error = 'wrong email format';
            }
        }
        return $error;
    }

    
    private function checkEmail($email)
    {
        return (preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,31}$/', $email));
    }
}
