<?php

namespace PubPharm\Controller;
use VuFind\Controller\AbstractBase;

class PubPharmController extends AbstractBase
{    
    public function helpAction() {
       return $this->createViewModel();
    }
   
    public function contactAction() {
       return $this->createViewModel();
    }
    
    public function searchtoolsAction() {
       return $this->createViewModel();
    }
}
?>