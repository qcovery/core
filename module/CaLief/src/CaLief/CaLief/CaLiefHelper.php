<?php

namespace CaLief\CaLief;

use \Zend\View\Helper\AbstractHelper;
use CaLief\Db\Table\UserCalief;
use CaLief\Order\Available;
use PAIA\ILS\Driver\PAIA;

class CaLiefHelper extends AbstractHelper
{
    protected $caliefConfig;
    protected $caliefUser;
    protected $caliefAdmin;
    
    /**
     * Constructor
     */
    public function __construct($sm)
    {
        $this->caliefConfig = parse_ini_file(realpath(APPLICATION_PATH . '/local/config/vufind/CaLief.ini'), true);
        
        $sl = $sm->getServiceLocator();
        $auth = $sl->get('VuFind\AuthManager');
        $user = $auth->isLoggedIn();
        if ($user) {
            $table = $sl->get('CaLief\DB\Table\UserCalief');
            $tableAdmin = $sl->get('CaLief\DB\Table\CaliefAdmin');
            if (!$this->caliefConfig['global']['useCaliefForVufindUsers']) {
                $this->caliefUser = $table->getByUserId($user->id);
            } else {
                $this->caliefUser = new \stdClass;
                $this->caliefUser->library = $this->caliefConfig['global']['useCaliefForVufindUsersLibrary'];
                $this->caliefUser->authorized = 1;
                $this->caliefUser->firstname = $user->firstname;
                $this->caliefUser->name = $user->lastname;
                $this->caliefUser->card_number = $user->cat_username;
                $patron = $auth->storedCatalogLogin();
                $paia = new PAIA();
                $profile = $paia->getMyProfile($patron);
                $this->caliefUser->email = $profile['email'];
            }
            $this->caliefAdmin = $tableAdmin->getByUserId($user->id);
        }
    }
    
    public function link ($home = false) {
       $style = '';
       if ($home) {
           $style = ' style="display:none"';
       }
       $iln = $this->caliefConfig[$this->caliefUser->library]['iln'];
       $result = '';
       if (strip_tags($_GET['institution']) == 'GBV_ILN_'.$iln || $home) {
          $result = '<li id="caliefLink"'.$style.'><a href="'.$this->caliefConfig['global']['info_link'].'" id="mn_calief" title="'.$this->view->translate('Campuslieferdienst').'">'.$this->view->translate('Campuslieferdienst').'</a></li>';
       }
       return $result;
       
    }
    
    public function button ($driver, $mobile = false) {
       $result = '';
       if ($driver) {
           $format = '';
           $formats = $driver->getFormats();
           if (!empty($formats)) {
               $format = $formats[0];
           }
           
           if (get_class($driver) != 'VuFind\RecordDriver\Missing') {
               if ($this->caliefUser->authorized == '1' && $this->foundSignatures($driver) && $this->checkFormat($format, $this->caliefUser)) {
                   $institution = '';
                   if ($mobile) {
                      $result .= '<li>';    
                   }
                   $result .= '<a href="/vufind/CaLief/Order?id='.$driver->getUniqueId().$institution.'" class="grey_button tooltip bu_calief" data-content-tooltip="content_'.$driver->getUniqueId().'">';
                   if (!$mobile) {
                      $result .= 'Liefern<span>'.$this->view->translate('Aufsätze und Buchkapitel per Campuslieferdienst zusenden lassen').'</span>';
                   }
                   $result .= '</a>';
                   if ($mobile) {
                      $result .= '</li>';    
                   }
               }
           }
       }
       return $result;
    }
    
    public function myBeluga () {
       $result = '';
       if (!$this->caliefConfig['global']['useCaliefForVufindUsers']) {
           $result .= '<li><a href="/vufind/CaLief/Index" title="' . $this->view->translate('Campuslieferdienst') . '">' . $this->view->translate('Campuslieferdienst') . '</a></li>';
           if ($this->caliefAdmin) {
               $result .= '<li><a href="/vufind/CaLief/Admin" title="' . $this->view->translate('Campuslieferdienst') . ' Admin">' . $this->view->translate('Campuslieferdienst') . ' Admin</a></li>';
           }
       }
       return $result;
    }
    
    public function myBelugaRightColumn () {
       $result = '';
       if (!$this->caliefConfig['global']['useCaliefForVufindUsers']) {
           $result .= '<dd><a href="/vufind/CaLief/Index" title="' . $this->view->translate('Campuslieferdienst') . '">' . $this->view->translate('Campuslieferdienst') . '</a></dd>';
           if ($this->caliefAdmin) {
               $result .= '<dd><a href="/vufind/CaLief/Admin" title="' . $this->view->translate('Campuslieferdienst') . ' Admin">' . $this->view->translate('Campuslieferdienst') . ' Admin</a></dd>';
           }
       }
       return $result;
    }
    
    private function foundSignatures($driver) {
        $format = '';
        $formats = $driver->getFormats();
        if (!empty($formats)) {
            $format = $formats[0];
        } else {
            return false;
        }

        $iln = $this->caliefConfig[$this->caliefUser->library]['iln'];
        if (!in_array('GBV_ILN_'.$iln, $driver->getCollectionDetails())) {
            return false;
        }
        $available = new Available($driver, $this->caliefConfig[$this->caliefUser->library]);
        $signature = $available->getSignature($format);

        return (empty($signature) == false);
    }
    
    private function checkFormat($format, $userCalief) {
        return in_array($format, $this->caliefConfig[$userCalief->library]['formats']);
    }
}

?>