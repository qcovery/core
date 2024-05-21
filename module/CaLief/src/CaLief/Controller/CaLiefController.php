<?php
/**
 * CaLief Controller
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
namespace CaLief\Controller;
//use VuFindSearch\Query\Query;
//use VuFind\Search\Factory\SolrDefaultBackendFactory;
//use Laminas\ServiceManager\ServiceLocatorAwareInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use VuFind\Controller\AbstractBase;
use VuFind\Controller\AbstractSearch;
use CaLief\Order\DodOrder;
use CaLief\Order\Available;
use PAIA\ILS\Driver\PAIA;

/**
 * Controller for the user account area.
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class CaLiefController extends AbstractBase
{        
    protected $caliefConfig;
    protected $serviceLocator;
    protected $serviceLocatorAwareInterface;

    
    /**
     * Constructor
     */
    public function __construct($serviceLocator)
    {
        $this->caliefConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/CaLief.ini'), true);
        //$config = $this->serviceLocator->get('VuFind\Config')->get('CaLief');
        $this->serviceLocator = $serviceLocator;
    }


 
    /**
     * CaLief action
     *
     * @return mixed
     */
    public function indexAction()
    {
        // First make sure user is logged in to VuFind:
        $account = $this->getAuthManager();
        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }

        // Make view
        $view = $this->createViewModel();

        $user = $this->getUser();
        $table = $this->getTable('usercalief');
        //$userCalief = $table->getByUserId($user->id);
        $userCalief = $this->getCaliefUser($user);

        if (!$userCalief) {
            return $this->forwardTo('CaLief', 'Register');
        } else {
            $this->caliefCheckAuthorize($userCalief);
            //$userCalief = $table->getByUserId($user->id);
            $userCalief = $this->getCaliefUser($user);
        }

        $view->userCalief = $userCalief;

        $table = $this->getTable('calief_admin');
        $adminCalief = $table->getByUserId($user->id);
        $view->adminCalief = $adminCalief;

        if (!$this->caliefConfig['global']['useCaliefForVufindUsers']) {
            $view->showEdit = true;
        } else {
            $view->showEdit = false;
        }

        return $view;
    }
    
    /**
     * CaLief action
     *
     * @return mixed
     */
    public function editAction()
    {
        // First make sure user is logged in to VuFind:
        $account = $this->getAuthManager();
        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }

        // Make view
        $view = $this->createViewModel();

        $user = $this->getUser();
        $table = $this->getTable('usercalief');
        //$userCalief = $table->getByUserId($user->id);
        $userCalief = $this->getCaliefUser($user);

        $view->userCalief = $userCalief;
        $view->libraries = $this->caliefLibraryKeys();

        $view->submittedLibrary = $userCalief->library;
        $view->submittedCard_number = $userCalief->card_number;
        $view->submittedGender = $userCalief->gender;
        $view->submittedTitle = $userCalief->title;
        $view->submittedName = $userCalief->name;
        $view->submittedLastname = $userCalief->lastname;
        $view->submittedEmail = $userCalief->email;

        if (isset($_POST['edit_calief_update'])) {
            $emailCorrect = true;
            if (!preg_match('/^(([a-zA-Z]|[0-9])|([-]|[_]|[.]))+[@]((([a-zA-Z0-9])|([-])){2,63}[.])*(([a-zA-Z0-9])|([-])){2,63}[.](([a-zA-Z0-9]){2,63})+$/', $_POST['email']) ) {
                $emailCorrect = false;
            }
            if ($emailCorrect) {
                $table->update(array('email' => $_POST['email']), array('id' => $userCalief->id));
                $this->caliefLog($user->id, 'edit');
                return $this->forwardTo('CaLief', 'Index');
            } else {
                $view->submittedEmail = $_POST['email'];
                if (!$emailCorrect) {
                    $view->checkEmail = 'check';
                }
            }
        }
        
        return $view;
    }
    
    /**
     * CaLief register action
     *
     * @return mixed
     */
    public function registerAction()
    {
        // First make sure user is logged in to VuFind:
        $account = $this->getAuthManager();
        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }

        // Make view
        $view = $this->createViewModel($result);
        $view->libraries = $this->caliefLibraryKeys();
        
        $user = $this->getUser();
        $table = $this->getTable('usercalief');
        
        if (!isset($_POST['calief_register'])) {
            $view->acceptedAGB = true;
            if (!isset($_POST['accept_agb'])) {
                //$view->acceptedAGB = false;
            }
        } else {
            $view->acceptedAGB = true;
            $emailCorrect = true;
            if (!preg_match('/^(([a-zA-Z]|[0-9])|([-]|[_]|[.]))+[@]((([a-zA-Z0-9])|([-])){2,63}[.])*(([a-zA-Z0-9])|([-])){2,63}[.](([a-zA-Z0-9]){2,63})+$/', $_POST['email']) ) {
                $emailCorrect = false;
            }
            $cardNumberCorrect = true;
            if (strlen($_POST['card_number']) != $this->caliefConfig[$_POST['library']]['lengthCardNumber']) {
                $cardNumberCorrect = false;
            }
            if ($emailCorrect && $cardNumberCorrect) {
                $table->insert(array('user_id' => $user->id, 'library' => $_POST['library'], 'card_number' => $_POST['card_number'], 'gender' => $_POST['gender'], 'title' => $_POST['title'], 'name' => $_POST['name'], 'lastname' => $_POST['lastname'], 'email' => $_POST['email'], 'authorized' => -1));
                $this->caliefLog($user->id, 'request');
                $this->caliefMail('request');
                return $this->forwardTo('CaLief', 'Index');
            } else {
                $view->submittedLibrary = $_POST['library'];
                $view->submittedCard_number = $_POST['card_number'];
                if (!$cardNumberCorrect) {
                    $view->checkCardNumber = 'check';
                }
                $view->submittedGender = $_POST['gender'];
                $view->submittedTitle = $_POST['title'];
                $view->submittedName = $_POST['name'];
                $view->submittedLastname = $_POST['lastname'];
                $view->submittedEmail = $_POST['email'];
                if (!$emailCorrect) {
                    $view->checkEmail = 'check';
                }
            }
        }
        return $view;
    }
    
    public function adminAction() {
        $account = $this->getAuthManager();
        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }
        
        $view = $this->createViewModel();
        
        $user = $this->getUser();
        $table = $this->getTable('caliefadmin');
        $adminCalief = $table->getByUserId($user->id);
        
        if ($adminCalief) {
            $view->adminCalief = $adminCalief;
            
            $tableCaliefUsers = $this->getTable('usercalief');
            
            if (isset($_POST['id'])) {
                if (isset($_POST['revoke']) || isset($_POST['refuse'])) {
                    $tableCaliefUsers->update(array('authorized' => 0), array('id' => $_POST['id']));
                    if (isset($_POST['revoke'])) {
                        $this->caliefLog($_POST['id'], 'revoke');
                        $this->caliefMail('revoke', $_POST['id']);
                    } else if (isset($_POST['refuse'])) {
                        $this->caliefLog($_POST['id'], 'refuse');
                        $this->caliefMail('refuse', $_POST['id']);
                    }
                } else if (isset($_POST['authorize'])) {
                    $tableCaliefUsers->update(array('authorized' => 1, 'lastorder' => NULL), array('id' => $_POST['id']));
                    $this->caliefLog($_POST['id'], 'authorize');
                    $this->caliefMail('authorize', $_POST['id']);
                }
            }
            
            $caliefUsers = $tableCaliefUsers->select(function (\Laminas\Db\Sql\Select $select) {
                 $select->order('authorized ASC');
            });
            
            $caliefUsersArray = array();
                foreach ($caliefUsers as $key => $value) {
                    $addUser = true;
                    if ($adminCalief->library != '*') {
                        if ($value->library != $adminCalief->library) {
                            $addUser = false;
                        }
                    }
                    if ($addUser) {
                        $caliefUsersArray[] = $value;
                    }
                }
            $view->usersCalief = $caliefUsersArray;
        } else {
            return $this->forwardTo('CaLief', 'Index');
        }
        
        return $view;
    }
    
    /* public function logsAction() {
        $account = $this->getAuthManager();
        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }
        
        $view = $this->createViewModel();
        
        $user = $this->getUser();
        $table = $this->getTable('caliefadmin');
        $adminCalief = $table->getByUserId($user->id);
        
        if ($adminCalief) {
            $view->adminCalief = $adminCalief;
            $tableCaliefLogs = $this->getTable('usercalieflog');
            $caliefLogs = $tableCaliefLogs->select(array());
            $view->caliefLogs = $caliefLogs;
        } else {
            return $this->forwardTo('CaLief', 'Index');
        }
        
        return $view;
    } */
/*
    private function checkPpnLink($ppn, $electronicArticle = false) {
        $user = $this->getUser();
        $table = $this->getTable('usercalief');
        $userCalief = $table->getByUserId($user->id);

        $request = 'id:'.$ppn.' AND collection_details:GBV_ILN_22 -format:Article';
        $query = new Query();
        $query->setHandler('AllFields');
        $query->setString($request);
        $solr_backend_factory = new SolrDefaultBackendFactory();
        $service = $solr_backend_factory->createService($this->serviceLocatorAwareInterface->getServiceLocator());
        $result = $service->search($query, 0, 10);
        $resultArray = $result->getResponse();
        $sigelList = $resultArray['docs'][0]['standort_str_mv'];
        $ilnList = $resultArray['docs'][0]['collection_details'];
        $ppnValid = false;
        foreach ($ilnList as $iln) {
            if ($iln == 'GBV_ILN_'.$this->caliefConfig[$userCalief->library]['signature_980_2']) {
                $ppnValid = true;
                break;
            }
        }
        if ($ppnValid) {
            if ($electronicArticle) {
                return true;
            } else {
                foreach ($this->caliefConfig[$userCalief->library]['signature_980_f'] as $signatureRegex) {
                    foreach ($sigelList as $sigel) {
                        if (preg_match('/'.$signatureRegex.'$/', $sigel)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
*/
    public function orderAction() {
        $account = $this->getAuthManager();
        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }
        
        $view = $this->createViewModel();
         
        $user = $this->getUser();
        $table = $this->getTable('usercalief');
        //$userCalief = $table->getByUserId($user->id);
        $userCalief = $this->getCaliefUser($user);

        $driver = $this->serviceLocator->get('VuFind\RecordLoader')->load($_GET['id'], 'Solr');
        $available = new Available($driver, $this->caliefConfig[$userCalief->library]);
            
        $format = '';
        $formats = $driver->getFormats();
        if (!empty($formats)) {
            $format = $formats[0];
        }
        $view->format = $format;
            
        //$signature = $available->getSignature($format);
        $signatureMarcData = $driver->getMarcData('Signature');
        $signature= '';
        if (isset($signatureMarcData[0]['signature']['data'][0])) {
            $signature = $signatureMarcData[0]['signature']['data'][0];
        }

        if (!$this->caliefConfig['global']['useCaliefForVufindUsers']) {
           	if (!$this->caliefCheckAuthorize($userCalief) || empty($signature) ) {
               	return $this->forwardTo('CaLief', 'Index');
           	}
       	}

        $view->userCalief = $userCalief;
 
        $sendOrder = false;
 
        if (isset($_POST['calief_order'])) {
            $checkForm = true;
            $errors = array();
            foreach ($_POST as $key => $value) {
                if (stristr($key, 'mandantory')) {
                    $checkKey = str_ireplace('mandantory_', '', $key);
                    if ($_POST[$checkKey] == '') {
                        $checkForm = false;
                        $errors[] = $checkKey;
                    }
                }
            }
            if ($checkForm) {
                $orderNumber = 'CaLief:'.date('ymdHis').rand(0, 9);
                //$this->caliefMail('order', $orderNumber, $driver);
                $this->caliefFileOrder($orderNumber, $driver);
                $this->caliefAuthorize($userCalief->id, 'renew');
                $sendOrder = true;
            } else {
                $view->errors = $errors;
            }
        }
        
        $mandantoryFields = $this->caliefConfig[$userCalief->library]['mandantoryFields'];
        
        if (!$sendOrder) {
            $view->driver = $driver;
            
            $formTitle = '';
            $formFields = array();
            
            $infoTitle = '';
            $infoFields = array();
            
            //PPN
            $ppn = (isset($_POST['ppn'])) ? $_POST['ppn'] : $driver->getUniqueID();
            $infoFields['PPN'] = array('display' => 'PPN', 'name' => 'ppn', 'value' => $ppn, 'mandantory' => false, 'error' => ''); 
            $formFields['PPN'] = array('display' => 'PPN', 'type' => 'hidden', 'name' => 'ppn', 'value' => $ppn, 'mandantory' => false, 'error' => '');
            
            //ISSN
            $issns = $driver->getISSNs();
            $tempIssn = '';
            if (isset($issns[0])) {
                $tempIssn = $issns[0];
            }
            $issn = (isset($_POST['issn'])) ? $_POST['issn'] : $tempIssn;
            $formFields['ISSN'] = array('display' => 'ISSN', 'type' => 'hidden', 'name' => 'issn', 'value' => $issn, 'mandantory' => false, 'error' => '');

            // Format 
            $infoFields['Format'] = array('display' => '', 'name' => 'format', 'value' => $format, 'mandantory' => false, 'error' => '');

            if ($format == 'Article' || $format == 'electronic Article') {
                $formTitle = 'Aufsatz';
                $infoTitle = 'Zeitschrift';

                $containingWorks = $driver->getContainingWork();
                $containingWork = $containingWorks[0];

                // PPN
                $formFields['Article-PPN'] = array('display' => 'PPN des Aufsatzes', 'name' => 'article_ppn', 'value' => $ppn, 'error' => '');
                
                // Autor
                $author = (!empty($_POST['article_author'])) ? $_POST['article_author'] : $driver->getTitleStatement();
                $formFields['Author'] = array('display' => 'Autor des Aufsatzes', 'name' => 'article_author', 'value' => implode(' ; ', $author), 'mandantory' => in_array('article_author', $mandantoryFields), 'error' => '');
                
                // Titel des Aufsatzes
                $title = (!empty($_POST['article_title'])) ? $_POST['article_title'] : $driver->getTitle();
                $formFields['Article-Title'] = array('display' => 'Titel des Aufsatzes', 'name' => 'article_title', 'value' => $title, 'mandantory' => in_array('article_title', $mandantoryFields), 'error' => '');
                
                // Band/Heft/Jahr/Seiten
                $yearNumberIssuePages = (isset($_POST['volume_issue'])) ? $_POST['volume_issue'] : $containingWork['issue'];
                $formFields['NumberIssueYearPages'] = array('display' => 'Band/Heft/Jahr/Seiten', 'name' => 'volume_issue', 'value' => $yearNumberIssuePages, 'mandantory' => false, 'error' => '');

                // PPN
                $infoFields['PPN'] = array('display' => 'PPN', 'name' => 'ppn', 'value' => $containingWork['ppn']);
                $formFields['PPN'] = array('hidden' => true, 'name' => 'ppn', 'value' => $containingWork['ppn']);
                if (!$available->checkPpnLink($this->serviceLocator, $infoFields['PPN']['value'])) {
            	      //return $this->forwardTo('CaLief', 'Index');
                    $view->error = 'CaLief Article not available';
                }
 
                // Zeitschriftentitel
                $infoFields['Title'] = array('display' => 'Zeitschriftentitel', 'name' => 'title', 'value' => $containingWork['title']);
                $formFields['Title'] = array('hidden' => true, 'name' => 'title', 'value' => $containingWork['title']);
                
                // Erscheinungsort
                $infoFields['Published'] = array('display' => 'Erscheinungsort', 'name' => 'publication_place', 'value' => $containingWork['location']);
                $formFields['Published'] = array('hidden' => true, 'name' => 'publication_place', 'value' => $containingWork['location']);
            } else {

                // Autor
                $formFields['Author'] = array('display' => 'Autor des Aufsatzes', 'name' => 'article_author', 'value' => $_POST['author'], 'mandantory' => in_array('article_author', $mandantoryFields), 'error' => '');
                    
		        if ($format == 'Journal' || $format == 'eJournal' || $format == 'Serial Volume') {
                    $formTitle = 'Aufsatz';
                    $infoTitle = 'Zeitschrift';
                
                    // Titel
                    $formFields['Title'] = array('display' => 'Titel des Aufsatzes', 'name' => 'article_title', 'value' => $_POST['article_title'], 'mandantory' => in_array('article_title', $mandantoryFields), 'error' => '');

                    // Zeitschriftentitel
                    $titleSection = $driver->getTitleSection();
                    $title = (empty($titleSection)) ? $driver->getTitle() : $driver->getTitle().' / '.$titleSection;
                    //$title = $driver->prepareData($title);
                    $infoFields['TitleSubtitle'] = array('display' => 'Zeitschriftentitel', 'name' => 'title', 'value' => $title);
                    $formFields['TitleSubtitle'] = array('hidden' => true, 'name' => 'title', 'value' => $title);

                    // Ausgabe

                    if ($format == 'Serial Volume') {
                        $containingWorks = $driver->getContainingWork();
                        $containingWork = $containingWorks[0];

                        // Jahr/Band
                        $yearnumber = (isset($_POST['volume_issue'])) ? $_POST['volume_issue'] : $driver->getVolumeTitle();
                        $formFields['YearNumber'] = array('display' => 'Band', 'name' => 'volume_issue', 'value' => $yearnumber, 'mandantory' => false, 'error' => '');

                        // Erscheinungsort
                        $publicationPlace = $containingWork['location'];
                        $infoFields['Published'] = array('display' => 'Erscheinungsort', 'name' => 'publication_place', 'value' => $publicationPlace, 'details' => '502 | a, 260 | a');
                        $formFields['Published'] = array('hidden' => true, 'name' => 'publication_place', 'value' => $publicationPlace);

                    } else {
                        // Band
                        $formFields['NumberIssue'] = array('display' => 'Band / Heft', 'name' => 'volume_issue', 'value' => $_POST['number_issue'], 'mandantory' => true, 'error' => '');

                        // Heft
                        $formFields['Year'] = array('display' => 'Jahr', 'name' => 'publication_date', 'value' => $_POST['publication_date'], 'mandantory' => true, 'error' => '');

                        // Erscheinungsort
                        /*
                        $publicationDetails = $driver->getPublicationDetailsFromMarc();
                        $publicationPlace = $publicationDetails[0]['location'];
                        $infoFields['Published'] = array('display' => 'Erscheinungsort', 'name' => 'publication_place', 'value' => $publicationPlace);
                        $formFields['Published'] = array('hidden' => true, 'name' => 'publication_place', 'value' => $publicationPlace);
                        */
                
                    }

                } else {
                    $formTitle = 'Teilkopie';
                    $infoTitle = 'Buch';
                    
                    // Titel
                    $formFields['Title'] = array('display' => 'Titel des Aufsatzes', 'name' => 'article_title', 'value' => $_POST['article_title'], 'mandantory' => in_array('article_title', $mandantoryFields), 'error' => '');

                    // Title/Autor
                    $titleStatement = $driver->getTitleStatement();
                    $title = (empty($titleStatement)) ? $driver->getTitle() : $driver->getTitle().' / '.implode(', ', $titleStatement);
                    //$title = $driver->prepareData($title);
                    $infoFields['TitleAuthor'] = array('display' => 'Titel / Autor', 'name' => 'title', 'value' => $title);
                    $formFields['TitleAuthor'] = array('hidden' => true, 'name' => 'title', 'value' => $title);

                    // Ausgabe
                    $infoFields['Issue'] = array('display' => 'Ausgabe', 'name' => 'volume_issue', 'value' => $driver->getEdition());
                    $formFields['Issue'] = array('hidden' => true, 'name' => 'volume_issue', 'value' => $driver->getEdition());

                    // Erscheinungsort
                    $publicationPlace = $driver->getUniversityNotes();
                    if (empty($publicationPlace)) {
                        /*
                        $publicationDetails = $driver->getPublicationDetailsFromMarc();
                        $publicationPlace = $publicationDetails[0]['location'];
                        $infoFields['Published'] = array('display' => 'Erscheinungsort', 'name' => 'publication_place', 'value' => $publicationPlace);
                        $formFields['Published'] = array('hidden' => true, 'name' => 'publication_place', 'value' => $publicationPlace);
                        */
                    } else {
                        $infoFields['University'] = array('display' => 'Hochschule', 'name' => 'university', 'value' => $publicationPlace);
                        $formFields['University'] = array('hidden' => true, 'name' => 'university', 'value' => $publicationPlace);
                    }
                }
                    
                // Seitenangabe
                $formFields['Pages'] = array('display' => 'Seitenangabe', 'name' => 'pages', 'value' => $_POST['pages'], 'mandantory' => true, 'error' => '');

                // Signatur
                $signature = ($signature == '!! ') ? '!!' : $signature;
                $infoFields['marcTitleSignature'] = array('display' => 'Signatur', 'name' => 'signature', 'value' => $signature, 'details' => '');
                $formFields['marcTitleSignature'] = array('display' => 'Signatur', 'type' => 'hidden', 'name' => 'signature', 'value' => $signature, 'details' => '');
            }
            
            // Bemerkung
            $formFields['Comment'] = array('display' => 'Bemerkung', 'name' => 'comment', 'value' => $_POST['comment'], 'mandantory' => false, 'error' => '');
    
            foreach ($this->caliefConfig[$userCalief->library]['additionalFields'] as $field) {
                $fieldArray = explode('|', $field);
                $fieldValues = array_slice($fieldArray, 2);
                $formFields[$field] = array('display' => $fieldArray[0], 'name' => str_ireplace(' ', '_', $fieldArray[0]), 'value' => $_POST[$fieldArray[0]], 'mandantory' => in_array($fieldArray[0], $mandantoryFields), 'error' => '', 'type' => $fieldArray[1], 'values' => $fieldValues);
            }
    
            foreach ($formFields as $key => $values) {
                if (isset($_POST[$values['name']])) {
                    $formFields[$key]['value'] = $_POST[$values['name']];
                }
            }
            
            $view->id = $_GET['id'];
            $view->institution = strip_tags($_GET['institution']);
            
            $view->formTitle = $formTitle;
            $view->formFields = $formFields;
            
            $view->infoTitle = $infoTitle;
            $view->infoFields = $infoFields;
        }
        return $view;
    }

    private function caliefLog($user_id, $info, $extra = array()) {
        $date = new \DateTime();
        $table = $this->getTable('usercalief');
        
        if ($info != 'order') {
        	$userCalief = $table->getByCaliefId($user_id);
        } else {
	        $userCalief = $table->getByUserId($user_id);
        }
        
        $logger = new \VuFind\Log\Logger();
        $logger->setServiceLocator($this->serviceLocator);
        $loggerConfig = new \Laminas\Config\Config(array('Logging' => array('file' => $this->caliefConfig['global']['log_dir'].'/'.$this->caliefConfig['global']['log_file'].':notice')));
        $logger->setConfig($loggerConfig);
        $extraArray = array($userCalief->name.' '.$userCalief->lastname.' ('.$userCalief->card_number.')');
        $extraArray[] = $extra['transaction-group-qualifier'];
        $logger->log(5, $info, $extraArray);
        
        if ($info == 'order') {
            $loggerEmail = new \Vufind\Log\Logger();
            $loggerEmail->setServiceLocator($this->serviceLocator);
            $filename = str_ireplace(':', '_', $extra['transaction-group-qualifier']) . '.log';
            $loggerEmailConfig = new \Laminas\Config\Config(array('Logging' => array('file' => $this->caliefConfig['global']['log_dir'].'/'.$filename.':notice')));
            $loggerEmail->setConfig($loggerEmailConfig);
            $loggerEmail->log(5, $info, $extra);
        }
    }
    
    private function caliefMail($info, $data = NULL, $driver = NULL) {
        $user = $this->getUser();
        $table = $this->getTable('usercalief');
        //$userCalief = $table->getByUserId($user->id);
        $userCalief = $this->getCaliefUser($user);
        
        if ($info != 'order') {
            if ($data) {
                $userCalief = $table->getByCaliefId($data);
            }
            $header = 'From: ' . $this->caliefConfig['global']['admin_email'] . "\r\n" .
                      'Reply-To: ' . $this->caliefConfig['global']['admin_email'] . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();
            if ($info == 'request') {
                //mail($userCalief->email, $this->caliefConfig[$userCalief->library]['text']['emailRequestSubject'], $this->caliefConfig[$userCalief->library]['text']['emailRequestBody'], $header);
                //mail($this->caliefConfig['global']['admin_email'], $this->caliefConfig[$userCalief->library]['text']['emailRequestAdminSubject'], $this->caliefConfig[$userCalief->library]['text']['emailRequestAdminBody'], $header);
            } else if ($info == 'revoke') {
                //mail($userCalief->email, $this->caliefConfig[$userCalief->library]['text']['emailRevokeSubject'], $this->caliefConfig[$userCalief->library]['text']['emailRevokeBody'], $header);
                //mail($this->caliefConfig['global']['admin_email'], $this->caliefConfig[$userCalief->library]['text']['emailRevokeAdminSubject'], $this->caliefConfig[$userCalief->library]['text']['emailRevokeAdminBody'], $header);
            } else if ($info == 'refuse') {
                //mail($userCalief->email, $this->caliefConfig[$userCalief->library]['text']['emailRefuseSubject'], $this->caliefConfig[$userCalief->library]['text']['emailRefuseBody'], $header);
            } else if ($info == 'authorize') {
                $header .= 'MIME-Version: 1.0' . "\r\n";
                $header .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";                
                mail($userCalief->email, $this->caliefConfig[$userCalief->library]['text']['emailAuthorizeSubject'], $this->caliefConfig[$userCalief->library]['text']['emailAuthorizeBody'], $header);
            }
        } else {
			
			if (!isset($this->caliefConfig[$userCalief->library]['config_file'])) {
                $ConfigFile = 'config.xml';
            } else {
                $ConfigFile = $this->caliefConfig[$userCalief->library]['config_file'];
            }

			if (!isset($this->caliefConfig[$userCalief->library]['ordermail_file'])) {
                $OrdermailFile = 'ordermail.xml';
            } else {
                $OrdermailFile = $this->caliefConfig[$userCalief->library]['ordermail_file'];
            }
			
			
            
            if (!isset($this->caliefConfig[$userCalief->library]['ordermail_file'])) {
                $OrdermailFile = 'ordermail.xml';
            } else {
                $OrdermailFile = $this->caliefConfig[$userCalief->library]['ordermail_file'];
            }
    
            $dodOrder = new dodOrder($ConfigFile, $OrdermailFile, $userCalief);
            
            //if ($dodOrder->checkEmailAddress($_POST['email'])) {
                
                $dataEmail = array();
                
                $dataEmail['client-name'] = $userCalief->name.' '.$userCalief->lastname; // Nachname Nutzer
                $dataEmail['contact-person-name'] = $userCalief->name.' '.$userCalief->lastname; // Name des Bestellers
                $dataEmail['client-identifier'] = $userCalief->card_number; // Ausweisnummer
                
                if (isset($_POST['email'])) {
                    $dataEmail['del-email-address'] = $_POST['email']; // E-Mail aus Formular
                } else {
                    $dataEmail['del-email-address'] = " ";
                }

                $dataEmail['item-system-no'] = (isset($_POST['ppn'])) ? $_POST['ppn'] : '';
                $dataEmail['item-title-of-article'] = (isset($_POST['article_title'])) ? substr($_POST['article_title'], 0, 100) : '';
                $dataEmail['item-author-of-article'] = (isset($_POST['article_author'])) ? substr($_POST['article_author'], 0, 80) : '';
                $dataEmail['item-title'] = (isset($_POST['title'])) ? substr($_POST['title'], 0, 100) : '';
                $dataEmail['item-author'] = (isset($_POST['author'])) ? substr($_POST['author'], 0, 80) : '';
                $dataEmail['item-publication-date'] = (isset($_POST['publication_date'])) ? $_POST['publication_date'] : '';
                $dataEmail['item-place-of-publication'] = (isset($_POST['publication_place'])) ? $_POST['publication_place'] : '';
                $dataEmail['item-pagination'] = (isset($_POST['pages'])) ? $_POST['pages'] : '';
                $dataEmail['item-volume-issue'] = (isset($_POST['volume_issue'])) ? $_POST['volume_issue'] : '';
                $dataEmail['item-held-medium-type'] = (isset($_POST['format'])) ? $_POST['format'] : '';
                $dataEmail['item-call-number'] = (isset($_POST['signature'])) ? $_POST['signature'] : '!!';
                if (isset($_POST['containing_ppn'])) {
                    $dataEmail['item-additional-no-letters'] = 'PPN (Werk): '.$_POST['containing_ppn'];
                }
                if (isset($_POST['article_ppn'])) {
                    $dataEmail['item-additional-no-letters'] = 'PPN (Artikel): '.$_POST['article_ppn'];
                }
                if (isset($_POST['university'])) {
                    $dataEmail['item-additional-no-letters'] = 'Hochschule: '.substr($_POST['university'], 0, 80);
                }
                $dataEmail['requester-note'] = (isset($_POST['comment'])) ? $_POST['comment'] : '';
                
                $dataEmail['transaction-initial-req-id-symbol'] = 'CaLief' . '.ORDER';
                $dataEmail['service-date-time'] = date('YmdHis');
                $dataEmail['transaction-group-qualifier'] = $data;
                
                foreach ($this->caliefConfig[$userCalief->library]['additionalFields'] as $field) {
                    $fieldArray = explode('|', $field);
                    $fieldName = str_ireplace(' ', '_', $fieldArray[0]);
                    $dataEmail[$fieldName] = (isset($_POST[$fieldName])) ? $_POST[$fieldName] : '';
                }
                
                if ($this->caliefConfig['global']['useCaliefForVufindUsersLibrary']) {
                    $account = $this->getAuthManager();
                    //$patron = $account->storedCatalogLogin();
                    //$paia = new PAIA();
                    //$profile = $paia->getMyProfile($patron);
                    $patron = $account->getIdentity();

                    if ($dataEmail['del-email-address'] == '') {
                        $dataEmail['del-email-address'] = $patron->email;
                    }
                    
                    //preg_match("/(?<=user-type:)\d*/", $patron->type, $output_array);
                    //if (isset($output_array[0])) {
                    //    $dataEmail['user_type'] = $output_array[0];
                    //}
                    //$dataEmail['user_type'] = 'USER_TYPE';
                    /* if (isset($patron->note)) {
                        $dataEmail['user_type'] = $patron->note;
                    } else {
                        $dataEmail['user_type'] = 'USER_TYPE';
                    } */
                    if (isset($_POST['comment']) && $_POST['comment'] != '') {
                        $dataEmail['user_type'] = $_POST['comment'];
                    } else {
                        $dataEmail['user_type'] = 'NO COMMENT SUBMITTED';
                    }

                    $dataEmail['ppn'] = (isset($_POST['ppn'])) ? $_POST['ppn'] : ''; 
                    $dataEmail['issn'] = (isset($_POST['issn'])) ? $_POST['issn'] : '';
                    $dataEmail['signature'] = (isset($_POST['signature'])) ? $_POST['signature'] : '';
                }
                
                if ($dodOrder->setVars($dataEmail)) {
                    $dodOrder->sendOrderMail();
                }
            //}
        }
    }
    
    private function caliefFileOrder($data = NULL, $driver = NULL) {
        $user = $this->getUser();
        $table = $this->getTable('usercalief');
        //$userCalief = $table->getByUserId($user->id);
        $userCalief = $this->getCaliefUser($user);
        
        // Titel|Nutzernummer|Vorname|Nachname|Mailadresse|Nutzertyp|Signatur|Autor|ISSN|Jahrgang|Band|PPN|EPN
        $fileContentArray = array();
        $fileContentArray[] = (isset($_POST['title'])) ? $_POST['title'] : 'TITLE';
        $fileContentArray[] = $userCalief->card_number;
        $fileContentArray[] = $userCalief->name;
        $fileContentArray[] = $userCalief->lastname;
        
        $account = $this->getAuthManager();
        //$patron = $account->storedCatalogLogin();
        //$paia = new PAIA();
        //$profile = $paia->getMyProfile($patron);
        $patron = $account->getIdentity();

        if (isset($_POST['email'])) {
            $fileContentArray[] = $_POST['email'];
        } else {
            $fileContentArray[] = $patron->email;
        }

        //preg_match("/(?<=user-type:)\d*/", $patron->type, $output_array);
        //if (isset($output_array[0])) {
        //    $fileContentArray[] = $output_array[0];
        //} else {
            $fileContentArray[] = $_POST['comment'];
        //}
        
        $fileContentArray[] = (isset($_POST['signature'])) ? $_POST['signature'] : 'SIGNATUR';
        $fileContentArray[] = (isset($_POST['article_author'])) ? $_POST['article_author'] : 'AUTHOR';
        $fileContentArray[] = (isset($_POST['issn'])) ? $_POST['issn'] : 'ISSN';
        $fileContentArray[] = (isset($_POST['publication_date'])) ? $_POST['publication_date'] : 'PUBLICATION_DATE';
        $fileContentArray[] = (isset($_POST['volume_issue'])) ? $_POST['volume_issue'] : 'VOLUME_ISSUE';
        $fileContentArray[] = (isset($_POST['ppn'])) ? $_POST['ppn'] : 'PPN';

        /* if (isset($driver)) {
            // get epn from driver
        } else {
            $fileContentArray[] = (isset($_POST['ppn'])) ? $_POST['ppn'] : 'EPN';
        } */
        $fileContentArray[] = 'EPN';
        $fileContentArray[] = (isset($_POST['article_title'])) ? $_POST['article_title'] : 'ARTICLE_TITLE';
        $fileContentArray[] = (isset($_POST['pages'])) ? $_POST['pages'] : 'PAGES';

        // lux_import_<TSTAMP>.asc
        $filename = $this->caliefConfig['global']['file_order_dir'].'lux_import_'.time().'.asc';
        
        
        error_log(print_r('*********************************** '.$filename, true));
        
        if (file_put_contents($filename, implode('|', $fileContentArray)) === true) {
            chmod($filename, 0775);
        }
    }
    
    private function caliefAuthorize($id, $info) {
        $tableCaliefUsers = $this->getTable('usercalief');
        if ($info == 'renew') {
            $date = new \DateTime();
            $tableCaliefUsers->update(array('lastorder' => $date->format('Y-m-d H:i:s')), array('id' => $id));
        } else if ($info == 'revoke') {
            $tableCaliefUsers->update(array('authorized' => 0), array('id' => $id));
        }
    }
    
    private function caliefCheckAuthorize($userCalief) {
        if (!$this->caliefConfig['global']['useCaliefForVufindUsers']) {
            $date = new \DateTime();
            $lastOrder = new \DateTime($userCalief->lastorder);
            $timeDiff = $date->diff($lastOrder);
            if ($timeDiff->y >= 1) {
                $this->caliefAuthorize($userCalief->id, 'revoke');
                return false;
            } else {
                if ($userCalief->authorized != '1') {
                    return false;
                }
                return true;
            }
        } else {
            return true;
        }
    }
    
    private function caliefLibraryKeys() {
        $result = array_keys($this->caliefConfig);
        foreach ($result as $key => $value) {
            if ($value == 'global') {
                unset($result[$key]);
            }
        }
        return $result;
    }
    
    private function getCaliefUser($user) {
        $table = $this->getTable('usercalief');
        if (!$this->caliefConfig['global']['useCaliefForVufindUsers']) {
            return $table->getByUserId($user->id);
        } else {
            $caliefUser = new \stdClass;
            $caliefUser->library = $this->caliefConfig['global']['useCaliefForVufindUsersLibrary'];
            $caliefUser->authorized = 1;
            $caliefUser->firstname = $user->firstname;
            $caliefUser->name = $user->lastname;
            $caliefUser->card_number = $user->cat_username;
            $account = $this->getAuthManager();
            $patron = $account->getIdentity();
            //$paia = new PAIA();
            //$profile = $paia->getMyProfile($patron);
            $caliefUser->email = $patron->email;
            return $caliefUser;
        }
        return false;
    }
    
}
