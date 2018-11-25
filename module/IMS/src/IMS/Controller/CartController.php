<?php

namespace IMS\Controller;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;

class CartController extends \VuFind\Controller\CartController
{

    /**
     * IMS action to export file.
     *
     * @return mixed
     */
    public function searchimsAction()
    {
        $id = $this->params()->fromQuery('id');
        $lookfor = $this->params()->fromQuery('lookfor');
        $this->session->imsId = $id;

        header('Location: /vufind/Search/Results?lookfor='.$lookfor.'&type=AllFields&limit=20');
        die();
    }

    /**
     * IMS action to export file.
     *
     * @return mixed
     */
    public function imsAction()
    {
        // Bail out if cart is disabled.
        if (!$this->getCart()->isActive()) {
            return $this->redirect()->toRoute('home');
        }

        // If a user is coming directly to the cart, we should clear out any
        // existing context information to prevent weird, unexpected workflows
        // caused by unusual user behavior.
        $this->followup()->retrieveAndClear('cartAction');
        $this->followup()->retrieveAndClear('cartIds');

        // We use abbreviated parameters here to keep the URL short (there may
        // be a long list of IDs, and we don't want to run out of room):
        $id = $this->params()->fromQuery('id');

        // Send appropriate HTTP headers for requested format:
        $response = $this->getResponse();
        $response->getHeaders()->addHeaders($this->getExport()->getHeaders($format));

        // Process and display the exported records
        $response->setContent(json_encode(['imsDownloadUrl' => urlencode('http://localhost:8080/vufind/Cart/imsdownload?id='.$id)]));
        return $response;
    }

}

