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
        $imsid = $this->params()->fromPost('imsid');
        $ids = $this->params()->fromPost('ids');

        $records = $this->getRecordLoader()->loadBatch($ids);

        $imsBaseDir = getcwd().'/var/ims/';
        if (!is_dir($imsBaseDir)) {
            mkdir($imsBaseDir);
        }
        if ($fileDownload = fopen($imsBaseDir.$imsid.'-turbomarc.xml', 'w')) {
            $turbomarcData = '';
            foreach ($records as $record) {
                $temp = tmpfile();
                fwrite($temp, $record->getXML('marc21'));
                fseek($temp, 0);

                $command = 'yaz-marcdump -i marcxml -o turbomarc '.stream_get_meta_data($temp)['uri'];
                exec($command, $execResults);

                fclose($temp); // dies entfernt die Datei

                $turbomarcData .= implode("\n", $execResults);
            }
            fwrite($fileDownload, $turbomarcData);
            fclose($fileDownload);
        }

        // Send appropriate HTTP headers for requested format:
        $response = $this->getResponse();

        // Process and display the exported records
        $response->setContent(json_encode(['imsDownloadUrl' => urlencode('http://localhost:8080/vufind/Cart/imsdownload?imsid='.$imsid)]));
        return $response;
    }

    /**
     * IMS action to export file.
     *
     * @return mixed
     */
    public function imsdownloadAction()
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
        $imsid = $this->params()->fromQuery('imsid');

        $response = $this->getResponse();

        $result = '';

        $imsBaseDir = getcwd().'/var/ims/';
        $imsFile = $imsBaseDir . $imsid . '-turbomarc.xml';
        if (file_exists($imsFile)) {
            if ($fileDownload = fopen($imsFile, 'r')) {
                $result = fread($fileDownload, filesize($imsFile));
                fclose($fileDownload);
            }
        }

        // Process and display the exported records
        $response->setContent($result);
        return $response;
    }
}

