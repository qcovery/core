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
//use Delivery\Db\Table\PluginManager;
use Delivery\Availability;
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

    protected $userDelivery;
    protected $deliveryGlobalConfig;

    protected $user;

    /**
     * Constructor
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        parent::__construct($sm);
        $this->user = $this->getUser();
        $this->userDelivery = $this->getTable('user_delivery');
        $this->deliveryGlobalConfig = $this->getConfig('deliveryGlobal');
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
        return $this->serviceLocator->get('Delivery\DbTablePluginManager')->get($table);
    }

    /**
     * Home action
     *
     * @return mixed
     */
    public function homeAction()
    {
        // First make sure user is logged in to VuFind:
        if (!$this->getAuthManager()->isLoggedIn()) {
            return $this->forceLogin();
        } elseif (empty($this->userDelivery->get($this->user->id))) {
            return $this->forwardTo('Delivery', 'Register');
        }

        $deliveryUser = (array) $this->userDelivery->get($this->user->id);
        $deliveryUser['name'] = $this->user->firstname . ' ' . $this->user->lastname;
        // Make view
        $view = $this->createViewModel();

        $view->deliveryUser = $deliveryUser;
 
        return $view;
    }
    
    /**
     * Edit action
     *
     * @return mixed
     */
    public function editAction()
    {
        // First make sure user is logged in to VuFind:
        if (!$this->getAuthManager()->isLoggedIn()) {
            return $this->forceLogin();
        }

        // Make view
        $view = $this->createViewModel();

        $deliveryEmail = $this->params()->fromPost('delivery_email');
        $cardNumber = $this->params()->fromPost('card_number');
        $update = $this->params()->fromPost('update');

        if (!empty($update) && !empty($deliveryEmail) && !empty($cardNumber)) {
            if ($this->checkEmail($deliveryEmail)) {
                if ($this->checkCardNumber($cardNumber)) {
                    $this->userDelivery->update(['delivery_email' => $deliveryEmail, 'card_number' => $cardNumber], ['user_id' => $this->user->id]);
                    return $this->forwardTo('Delivery', 'Home');
                } else {
                    $view->checkCardNumber = 'check';
                }
            } else {
                $view->checkDeliveryEmail = 'check';
            }
        }
        $view->deliveryUser = (array) $this->userDelivery->get($this->user->id);

        return $view;
    }
    
    /**
     * register action
     *
     * @return mixed
     */
    public function registerAction()
    {
        // First make sure user is logged in to VuFind:
        if ($this->getAuthManager()->isLoggedIn() == false) {
            return $this->forceLogin();
        }

        // Make view
        $view = $this->createViewModel();
        $view->request = $this->getRequest()->getPost();
        $view->libraryCodes = $this->getLibraryCodes();
        $view->userName = $this->user->firstname . ' ' . $this->user->lastname;

        if (!empty($this->params()->fromPost('register'))) {
            $cardNumberCorrect = $this->checkCardNumber($this->params()->fromPost('card_number'));
            $deliveryEmailCorrect = $this->checkEmail($this->params()->fromPost('delivery_email'));
            if (!$deliveryEmailCorrect) {
                $view->checkDeliveryEmail = 'check';
            } elseif (!$cardNumberCorrect) {
                $view->checkCardNumber = 'check';
            } else {
                $this->userDelivery->insert([
                    'user_id' => $this->user->id,
                    'sex' => $this->params()->fromPost('sex'),
                    'title' => $this->params()->fromPost('title'), 
                    'delivery_email' => $this->params()->fromPost('delivery_email'),
                    'library' => $this->params()->fromPost('library'),
                    'card_number' => $this->params()->fromPost('card_number'), 
                    'authorized' => -1
                ]);
                $this->sendDeliveryMail('request');
                return $this->forwardTo('Delivery', 'Home');
            }
        }
        return $view;
    }
    
    public function adminAction()
    {
        // First make sure user is logged in to VuFind:
        if ($this->getAuthManager()->isLoggedIn() == false) {
            return $this->forceLogin();
        }
        
        $view = $this->createViewModel();
        $deliveryAdmin = $this->userDelivery->get($this->user->id);
        if ($deliveryAdmin->is_admin == 'y') {
            $id = $this->params()->fromPost('id');
            $action = $this->params()->fromPost('action');
            if (!empty($id)) {
                $this->authorize($id, ($action == 'revoke') || ($action == 'refuse'));
                $this->sendDeliveryMail($action, $id);
            }
            
            $deliveryUsers = $this->userDelivery->select(function (\Zend\Db\Sql\Select $select) {
                 $select->order('authorized ASC');
            });

            foreach ($deliveryUsers as $key => $value) {
                if ($deliveryAdmin->library != '*' && $deliveryAdmin->library != $value->library) {
                    if ($value->library != $deliveryAdmin->library) {
                        unset($deliveryUsers[$key]);
                    }
                }
            }
            $view->deliveryUsers = array_values($deliveryUsers);
        } else {
            return $this->forwardTo('Delivery', 'Home');
        }
        
        return $view;
    }
    
    public function orderAction()
    {
        // First make sure user is logged in to VuFind:
        if ($this->getAuthManager()->isLoggedIn() == false) {
            return $this->forceLogin();
        }
        if (!$this->checkAuthorization()) {
            return $this->forwardTo('Delivery', 'Home');
       	}
        $id = (!empty($this->params()->fromQuery('id'))) ? $this->params()->fromQuery('id') : $this->params()->fromPost('id');

        $view = $this->createViewModel();
        $view->deliveryUser = $this->userDelivery->get($this->user->id);
        $orderDataConfig = $this->getConfig('deliveryOrderData');
        $DataHandler = new DataHandler(null, $this->params(), $orderDataConfig);

        $sendOrder = false;
        if (!empty($this->params()->fromPost('order'))) {
            $errors = $DataHandler->checkData();
            if (empty($errors)) {
                $orderNumber = 'CaLief:'.date('ymdHis').rand(0, 9);
                $this->sendDeliveryMail('order', $orderNumber, $driver);
                $date = new \DateTime();
                $this->userDelivery->update(['last_order' => $date->format('Y-m-d H:i:s')], ['user_id' => $this->user->id]);
                $sendOrder = true;
            } else {
                $view->errors = $errors;
            }
        }
 
        if (!$sendOrder) {
            $availabilityConfig = $this->getConfig('deliveryAvailability');
            $driver = $this->getRecordLoader()->load($id, 'Solr');
            $DataHandler->setSolrDriver($driver);
            $Availability = new Availability($driver, $availabilityConfig['default']);
            $signature = $Availability->getSignature();
            if (empty($signature)) {
                return $this->forwardTo('Delivery', 'Home');
       	    }
            $articleAvailable = ($Availability->checkPpnLink($this->getServiceLocator(), $id));
            $DataHandler->collectData($signature, $articleAvailable);

            $formData = $DataHandler->getFormData();
            $infoData = $DataHandler->getInfoData();

            $view->id = $id;
            $view->formTitle = $formData['title'];
            $view->formFields = $formData['fields'];
            $view->infoTitle = $infoData['title'];
            $view->infoFields = $infoData['fields'];
        }
        return $view;
    }

    private function sendDeliveryMail($emailType)
    {
        $mailer = $this->serviceLocator->get('VuFind\Mailer');
        $mailConfig = $this->deliveryGlobalConfig['Email'];

        $deliveryUser = $this->userDelivery->get($this->user->id);
        $mailTo = $deliveryUser->delivery_email;
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
            $containingPpn = $this->params()->fromPost('containing_ppn');
            $articlePpn = $this->params()->fromPost('article_ppn');
            $university = $this->params()->fromPost('university_notes');
            if (isset($containingPpn)) {
                $itemAdditionalNoLetters = 'PPN (Werk): ' . $containingPpn;
            } elseif (isset($articlePpn)) {
                $itemAdditionalNoLetters = 'PPN (Artikel): ' . $articlePpn;
            } elseif (isset($university)) {
                $itemAdditionalNoLetters = 'Hochschule: ' . $university;
            }

            $renderer = $this->getViewRenderer();
            $message = $renderer->render('Email/delivery-order.phtml',
                [
                    'clientName' => $this->user->firstname . ' ' . $this->user->lastname,
                    'contactPersonName' => $this->user->firstname . ' ' . $this->user->lastname,
                    'clientIdentifier' => $deliveryUser->card_number,
                    'delEmailAddress' => (!empty($this->params()->fromPost('email'))) ? $this->params()->fromPost('email') : $this->user->email,
                    'itemSystemNo' => (!empty($this->params()->fromPost('ppn'))) ? $this->params()->fromPost('ppn') : '',
                    'itemTitleOfArticle' => (!empty($this->params()->fromPost('article_title'))) ? $this->params()->fromPost('article_title') : '',
                    'itemAuthorOfArticle' => (!empty($this->params()->fromPost('article_author'))) ? $this->params()->fromPost('article_author') : '',
                    'itemTitle' => (!empty($this->params()->fromPost('title'))) ? $this->params()->fromPost('title') : '',
                    'itemAuthor' => (!empty($this->params()->fromPost('author'))) ? $this->params()->fromPost('author') : '',
                    'itemPublicationDate' => (!empty($this->params()->fromPost('publication_year'))) ? $this->params()->fromPost('publication_year') : '',
                    'itemPlaceOfPublication' => (!empty($this->params()->fromPost('publication_place'))) ? $this->params()->fromPost('publication_place') : '',
                    'itemPagination' => (!empty($this->params()->fromPost('pages'))) ? $this->params()->fromPost('pages') : '',
                    'itemVolumeIssue' => (!empty($this->params()->fromPost('volume_issue'))) ? $this->params()->fromPost('volume_issue') : '',
                    'itemHeldMediumType' => (!empty($this->params()->fromPost('format'))) ? $this->params()->fromPost('format') : '',
                    'itemCallNumber' => (!empty($this->params()->fromPost('signature'))) ? $this->params()->fromPost('signature') : '',
                    'itemAdditionalNoLetters' => (!empty($itemAdditionalNoLetters)) ? $itemAdditionalNoLetters : '',
                    'requesterNote' => (!empty($this->params()->fromPost('comment'))) ? $this->params()->fromPost('comment') : '',
                ]
            );
            $mailer->send($mailConfig['orderMailTo'], $mailConfig['orderMailFrom'], $mailConfig['orderSubject'], $message);
        }
    }
    
    private function authorize($id, $revoke)
    {
        if ($revoke) {
            $this->userDelivery->update(['authorized' => 0], ['id' => $id]);
        } else {
            $this->userDelivery->update(['authorized' => 1, 'last_order' => NULL], ['id' => $id]);
        }
    }
    
    private function checkAuthorization()
    {
        $date = new \DateTime();
        $deliveryUser = $this->userDelivery->get($this->user->id);
        $lastOrder = new \DateTime($deliveryUser->last_order);
        $timeDiff = $date->diff($lastOrder);
        if ($timeDiff->y >= 1) {
            $this->authorize($deliveryUser->id, true);
            return false;
        } else {
            return ($deliveryUser->authorized == '1');
        }
    }
    
    private function getLibraryCodes()
    {
        $libraryCodes = array_keys($this->deliveryConfig); //!!
        unset($libraryCodes['global']);
        return $libraryCodes;
    }


    private function checkEmail($email)
    {
        return (preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,31}$/', $email));
    }

    private function checkCardNumber($cardNumber)
    {
        return (strlen($cardNumber) == $this->deliveryGlobalConfig[Card]['numberLength']);
    }

}
