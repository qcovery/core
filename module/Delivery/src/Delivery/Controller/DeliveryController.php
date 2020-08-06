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
use Delivery\ConfigurationManager;
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
    protected $deliveryTable;
    protected $configurationManager;

    protected $user;

    /**
     * Constructor
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);
        $this->deliveryAuthenticator = $sm->get('Delivery\Auth\DeliveryAuthenticator');
        $this->configurationManager = new ConfigurationManager($sm->get('VuFind\Config\PluginManager'));
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
    
    private function authenticate($deliveryDomain, $asAdmin = false)
    {
        $message = $this->deliveryAuthenticator->authenticate($deliveryDomain, $asAdmin);
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
        $deliveryDomain = $this->params()->fromQuery('domain') ?? $this->params()->fromPost('domain');
        $message = $this->authenticate($deliveryDomain);
        if ($message != 'authorized') {
            return $this->forwardTo('MyResearch', 'Profile');
        }

        $deliveryTable = $this->getTable('delivery');
        $listData = $deliveryTable->getDeliveryList($this->user->user_delivery_id);
        $templateParams = $this->deliveryAuthenticator->getTemplateParams($deliveryDomain);

        $error = $this->updateDeliveryMail();

        $view = $this->createViewModel();
        $view->title = $templateParams['title'];
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
        $deliveryDomain = $this->params()->fromQuery('domain') ?? $this->params()->fromPost('domain');
        $message = $this->authenticate($deliveryDomain);
        if ($message != 'authorized') {
            return $this->forwardTo('MyResearch', 'Profile');
        }

        $id = $this->params()->fromQuery('id') ?? $this->params()->fromPost('id');
        $searchClassId = $this->params()->fromQuery('searchClassId') ?? $this->params()->fromPost('searchClassId');

        $errors = $missingFields = [];

        if (empty($id) || empty($searchClassId)) {
            $errors[] = 'record id is missing';
        } else {
            $driver = $this->getRecordLoader()->load($id, $searchClassId);

            $this->configurationManager->setConfigurations($deliveryDomain);
            $orderDataConfig = $this->configurationManager->getOrderDataConfig();
            $pluginConfig =  $this->configurationManager->getPluginConfig();
            $mainConfig = $this->configurationManager->getMainConfig();
            $dataHandler = new DataHandler($this->serviceLocator->get('Delivery\Driver\PluginManager'), $this->params(), $orderDataConfig, $pluginConfig);
            $dataHandler->setSolrDriver($driver);
        
            if ($error = $this->updateDeliveryMail()) {
                $errors[] = $error;
            }

            if (!empty($this->params()->fromPost('order'))) {
                if ($this->checkEmail($this->params()->fromPost('delivery_email'))) {
                    $this->user->delivery_email = $this->params()->fromPost('delivery_email');
                    if ($dataHandler->sendOrder($this->user)) {
                        $dataHandler->insertOrderData($this->user, $this->getTable('delivery'));
                        $orderId = $dataHandler->getOrderId();
                    } else {
                        $errors = $dataHandler->getErrors();
                        $missingFields = $dataHandler->getMissingFields();
                    }
                } else {
                    $missingFields = ['delivery_email'];
                }
            }
 
            $availabilityConfig = $this->configurationManager->getAvailabilityConfig();
            $availabilityHelper = new AvailabilityHelper($driver, $availabilityConfig['checkparent']);
            if ($parentId = $availabilityHelper->getParentId()) {
                $parentDriver = $this->getRecordLoader()->load($parentId, DEFAULT_SEARCH_BACKEND);
                $availabilityHelper->setSolrDriver($parentDriver);
            }
            $availabilityHelper->setDeliveryConfig($availabilityConfig['default']);

            $signatureCount = $mainConfig['collectedCallnumbers'] ?: 1;
            $signatureList = array_slice($availabilityHelper->getSignatureList(), 0 , $signatureCount);
            $signature = implode("\n", $signatureList);
        }

        $templateParams = $this->deliveryAuthenticator->getTemplateParams($deliveryDomain);

        $view = $this->createViewModel();
        $view->title = $templateParams['title'];
        $view->errors = $errors;
        $view->missingFields = $missingFields;

        if (!empty($orderId)) {
            $view->id = $id;
            $view->searchClassId = $searchClassId;
            $view->orderId = $orderId;
        } elseif (!empty($id) && !empty($signature)) {
            $preset = [];
            if ($mainConfig['presetCallnumbers'] == 'y') {
                $preset = ['signature' => $signature];
            }
            $dataHandler->collectData($preset);

            $formData = $dataHandler->getFormData();
            $infoData = $dataHandler->getInfoData();

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
        }
        return $view;
    }

    private function updateDeliveryMail()
    {
        $error = '';
        $deliveryEmail = $this->params()->fromPost('delivery_email');
        $update = $this->params()->fromPost('update_email');
        if (!empty($update) && !empty($deliveryEmail)) {
            if ($deliveryEmail != $this->user->delivery_email) {
                if ($this->checkEmail($deliveryEmail)) {
                    $userDeliveryTable = $this->getTable('userdelivery');
                    $userDeliveryTable->update(['delivery_email' => $deliveryEmail], ['user_id' => $this->user->id]);
                    $this->authenticate();
                } else {
                    $error = 'wrong email format';
                }
            }
        }
        return $error;
    }

    
    private function checkEmail($email)
    {
        return (preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,31}$/', $email));
    }
}
