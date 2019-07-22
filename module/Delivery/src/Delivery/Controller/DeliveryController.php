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
            $user = $this->deliveryAuthenticator->getUser();
            $this->user = $user;
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
        if ($message == 'not_logged_in') {
            return $this->forceLogin();
        }

        $view = $this->createViewModel();
        $view->message = $message;
        $view->catalog_id = $this->user->cat_id;
        $view->delivery_email = $this->user->delivery_email;
        $view->name = trim($this->user->firstname . ' ' . $this->user->lastname);
        return $view;
    }

    /**
     * Email action
     *
     * @return mixed
     */
    public function emailAction()
    {
        $message = $this->authenticate();
        if ($message != 'authorized') {
            return $this->forwardTo('Delivery', 'Home');
        }

        $deliveryEmail = $this->params()->fromPost('delivery_email');
        $update = $this->params()->fromPost('update');
        $listData = $deliveryTable->getDeliveryList($this->user->user_delivery_id);

        if (!empty($update) && !empty($deliveryEmail)) {
            if ($this->checkEmail($deliveryEmail)) {
                $deliveryTable = $this->getTable('delivery');
                $deliveryTable->update(['delivery_email' => $deliveryEmail], ['user_id' => $this->user->id]);
                return $this->forwardTo('Delivery', 'Home');
            } else {
                return $this->forwardTo('Delivery', 'Home');
            }
        }
        return $this->forwardTo('Delivery', 'Home');
    }

    public function orderAction()
    {
        $message = $this->authenticate();
        if ($message != 'authorized') {
            return $this->forwardTo('Delivery', 'Home');
        }
        $id = $this->params()->fromQuery('id') ?? $this->params()->fromPost('id');
        $searchClassId = $this->params()->fromQuery('searchClassId') ?? $this->params()->fromPost('searchClassId');
        if (empty($id)) {
            return $this->forwardTo('Delivery', 'Home');
        }

        $orderDataConfig = $this->getConfig('deliveryOrderData')->toArray();
        $this->dataHandler = new DataHandler($this->serviceLocator->get('Delivery\Driver\PluginManager'), $this->params(), $orderDataConfig, $this->deliveryGlobalConfig);

        if (!empty($this->params()->fromPost('order'))) {
            $orderSent = $this->dataHandler->sendOrder($this->user);


            $mailData = $this->dataHandler->prepareOrderMail($this->user);
            $renderer = $this->serviceLocator->get('ViewRenderer');
            $message = $renderer->render('Email/delivery-order.phtml', $mailData);
            //$mailer->send($mailConfig['orderMailTo'], $mailConfig['orderMailFrom'], $mailConfig['orderSubject'], $message);


            $listData = [
                'record_id' => $mailData['itemSystemNo'],
                'title' => $mailData['itemTitle'],
                'author' => $mailData['itemAuthorOfArticle'],
                'year' => $mailData['itemPublicationDate'],
                'source' => $this->params()->fromQuery('searchClassId') ?? $this->params()->fromPost('searchClassId')
            ];
            $deliveryTable = $this->getTable('delivery');
            $deliveryTable->createRowForUserDeliveryId($this->user->user_delivery_id, $listData);
                return $this->forwardTo('Delivery', 'List');

        }
 
        $view = $this->createViewModel();

        if (!empty($errors)) {
            $view->errors = $errors;
        } else {
            $driver = $this->getRecordLoader()->load($id, $searchClassId);
            $availabilityConfig = $this->getConfig('deliveryAvailability');
            $AvailabilityHelper = new AvailabilityHelper($driver, $availabilityConfig['default']);
            $signature = $AvailabilityHelper->getSignature();
            if (empty($signature)) {
                return $this->forwardTo('Delivery', 'Home');
       	    }
            //$articleAvailable = ($AvailabilityHelper->checkPpnLink($this->getServiceLocator(), $id));
            $articleAvailable = false;
            $this->dataHandler->setSolrDriver($driver);
            $this->dataHandler->collectData($signature, $articleAvailable);

            $formData = $this->dataHandler->getFormData();
            $infoData = $this->dataHandler->getInfoData();

            $view->id = $id;
            $view->searchClassId = $searchClassId;
            $view->formTitle = $formData['title'];
            $view->formFields = $formData['fields'];
            $view->infoTitle = $infoData['title'];
            $view->infoFields = $infoData['fields'];
        }
        return $view;
    }

    public function listAction()
    {
        $message = $this->authenticate();
        if ($message != 'authorized') {
            return $this->forwardTo('Delivery', 'Home');
        }

        $deliveryTable = $this->getTable('delivery');
        $listData = $deliveryTable->getDeliveryList($this->user->user_delivery_id);
 
        $view = $this->createViewModel();
        $view->listData = $listData;
        return $view;
    }

    /**
     * Home action
     *
     * @return mixed
     */
    public function adminlistAction()
    {
        $message = $this->authenticate(true);
        if ($message != 'authorized') {
            return $this->forwardTo('Delivery', 'Home');
        }

// list nach status & Reihenfolge
        $deliveryTable = $this->getTable('delivery');
        $listData = $deliveryTable->getCompleteList();
 
        $view = $this->createViewModel();
        $view->listData = $listData;
        return $view;
    }

    /**
     * Home action
     *
     * @return mixed
     */
    public function admineditAction()
    {
        $message = $this->authenticate(true);
        if ($message != 'authorized') {
            return $this->forwardTo('Delivery', 'Home');
        }

// list nach status & Reihenfolge
        $deliveryTable = $this->getTable('delivery');
        $listData = $deliveryTable->getCompleteList();
 
        $view = $this->createViewModel();
        $view->listData = $listData;
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
        } elseif ($emailType == 'order') {
        }
    }
    
    private function checkEmail($email)
    {
        return (preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,31}$/', $email));
    }
}
