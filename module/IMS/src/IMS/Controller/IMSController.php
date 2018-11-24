<?php

namespace IMS\Controller;

use \VuFind\Controller\AbstractBase;

class IMSController extends AbstractBase
{

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function exportAction()
    {
        return $this->createViewModel();
    }
}