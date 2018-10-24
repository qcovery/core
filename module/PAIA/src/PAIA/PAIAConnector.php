<?php

namespace PAIA;

use PAIA\Config\PAIAConfigService;
use Zend\Http\Client;
use Zend\Http\Request;

class PAIAConnector
{
    protected $http_client;
    protected $debug;
    protected $base_url;
    protected $isil;

    private $paiaConfigService;

    public function __construct(PAIAConfigService $paiaConfigService)
    {
        $this->paiaConfigService = $paiaConfigService;
        $paiaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/PAIA.ini'), true);
        $this->http_client = new \Zend\Http\Client(null, array('adapter' => 'Zend\Http\Client\Adapter\Curl', 'curloptions' => array(CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false), 'timeout' => $paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['timeout']));
        $this->debug = true;
        $this->base_url = $paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['baseUrl'];
        $this->isil = '';
        if (isset($_POST['paia_isil'])) {
           $this->isil .= $_POST['paia_isil'];
        } else if (isset($_GET['paia_isil'])) {
           $this->isil .= $_GET['paia_isil'];
        } else {
           $this->isil = $paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['isil'];
        }
    }

    /*
     * PAIA Auth
     */
    function login ($username, $password, $grandType = 'password', $scope = null) {
       $client = new \PAIA\RestClient($this->base_url.$this->isil.'/auth/login');
       $client->setHttpClient($this->http_client);
       $client->username($username);
       $client->password($password);
       $client->grant_type($grandType);
       if (!$scope) {
           $scope = 'read_patron read_fees read_items write_items change_password read_availability';
       }
       $client->scope($scope);
       return $client->post();
    }
    
    function logout ($patron) {
       $client = new \PAIA\RestClient($this->base_url.$this->isil.'/auth/logout');
       $client->setHttpClient($this->http_client);
       $client->patron($patron);
       return $client->post();
    }
    
    function change ($patron, $access_token, $username, $old_password, $new_password) {
       $client = new \PAIA\RestClient($this->base_url.$this->isil.'/auth/change?access_token='.$access_token);
       $client->setHttpClient($this->http_client);
       $client->patron($patron);
       $client->username($username);
       $client->old_password($old_password);
       $client->new_password($new_password);
       return $client->post();
    }
    
    /*
     * PAIA Core
     */
    function patron ($patron, $access_token) {
       $client = new \PAIA\RestClient($this->base_url.$this->isil.'/core/'.$patron);
       $client->setHttpClient($this->http_client);
       $client->access_token($access_token);
       return $client->get();
    }
    
    function items ($patron, $access_token) {
       $client = new \PAIA\RestClient($this->base_url.$this->isil.'/core/'.$patron.'/items');
       $client->setHttpClient($this->http_client);
       $client->access_token($access_token);
       return $client->get();
    }
    
    function renew ($patron, $doc, $access_token) {
       $client = new \Zend\Http\Client($this->base_url.$this->isil.'/core/'.$patron.'/renew?access_token='.$access_token);
       $client->setHeaders(array('Content-Type' => 'application/json'))
              ->setOptions(array('sslverifypeer' => false))
              ->setMethod('POST')
              ->setRawBody($doc);
       return $client->send();
    }
    
    function request ($patron, $doc, $access_token) {
        $client = new \Zend\Http\Client($this->base_url.$this->isil.'/core/'.$patron.'/request?access_token='.$access_token);
        $client->setHeaders(array('Content-Type' => 'application/json'))
              ->setOptions(array('sslverifypeer' => false))
              ->setMethod('POST')
              ->setRawBody($doc);
        return $client->send();
    }
    
    function cancel ($patron, $doc, $access_token) {
       $client = new \Zend\Http\Client($this->base_url.$this->isil.'/core/'.$patron.'/cancel?access_token='.$access_token);
       $client->setHeaders(array('Content-Type' => 'application/json'))
              ->setOptions(array('sslverifypeer' => false))
              ->setMethod('POST')
              ->setRawBody($doc);
       return $client->send();
    }
    
    function fees ($patron, $access_token) {
       $client = new \PAIA\RestClient($this->base_url.$this->isil.'/core/'.$patron.'/fees');
       $client->setHttpClient($this->http_client);
       $client->access_token($access_token);
       return $client->get();
    }
    
    public function setIsil ($isil) {
       $this->isil = $isil;
    }
}

?>
