<?php

namespace IMS\Controller;

class CartController extends \VuFind\Controller\CartController
{

    /**
     * IMS action to export file.
     *
     * @return mixed
     */
    public function imsAction()
    {
        // We use abbreviated parameters here to keep the URL short (there may
        // be a long list of IDs, and we don't want to run out of room):
        $id = $this->params()->fromQuery('id');

        // Send appropriate HTTP headers for requested format:
        $response = $this->getResponse();
        $response->getHeaders()->addHeaders($this->getExport()->getHeaders($format));

        // Process and display the exported records
        $response->setContent('IMS for id: '.$id);
        return $response;
    }

}

