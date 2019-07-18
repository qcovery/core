<?php

namespace LMS\Controller;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;

class CartController extends \VuFind\Controller\CartController
{

    /**
     * IMS action to export file.
     *
     * @return mixed
     */
    public function searchlmsAction()
    {
        $id = $this->params()->fromQuery('id');
        $lookfor = $this->params()->fromQuery('lookfor');
        $this->session->lmsId = $id;

        header('Location: /vufind/Search/Results?lookfor='.$lookfor.'&type=AllFields&limit=20');
        die();
    }

    /**
     * IMS action to export file.
     *
     * @return mixed
     */
    public function lmsAction()
    {
        $response = $this->getResponse();

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
        $lmsid = $this->params()->fromPost('lmsid');
        $ids = $this->params()->fromPost('ids');

        if (!empty($ids)) {
            $lmsBaseDir = getcwd() . '/var/lms/';
            if (!is_dir($lmsBaseDir)) {
                mkdir($lmsBaseDir);
            }
            if ($file = fopen($lmsBaseDir . $lmsid . '-lms.xml', 'w')) {
                $writeResult = fwrite($file, implode(';', $ids));
                fclose($file);
            }

            $config = $this->serviceLocator->get('VuFind\Config')->get('config');
            $response->setContent(json_encode(['lmsDownloadUrl' => urlencode($config['Site']['url'] . '/Cart/lmsdownload?lmsid=' . $lmsid), 'filepath' => $lmsBaseDir . $lmsid . '-turbomarc.xml', 'writeResult' => error_get_last()]));
        } else {
            $response->setContent('No ids available');
        }

        return $response;
    }

    /**
     * IMS action to export file.
     *
     * @return mixed
     */
    public function lmsdownloadAction()
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
        $lmsid = $this->params()->fromQuery('lmsid');
        $format = $this->params()->fromQuery('format');

        $response = $this->getResponse();

        $result = '';

        $lmsBaseDir = getcwd().'/var/lms/';
        $lmsFile = $lmsBaseDir . $lmsid . '-lms.xml';

        if (file_exists($lmsFile)) {
            if ($fileDownload = fopen($lmsFile, 'r')) {
                $ids = explode(';', fread($fileDownload, filesize($lmsFile)));
                fclose($fileDownload);
                if (!empty($ids)) {
                    if ($format == 'turbomarc') {
                        $records = $this->getRecordLoader()->loadBatch($ids);
                        $turbomarcData = '';
                        foreach ($records as $record) {
                            $temp = tmpfile();
                            fwrite($temp, $record->getXML('marc21'));
                            fseek($temp, 0);

                            $command = 'yaz-marcdump -i marcxml -o turbomarc ' . stream_get_meta_data($temp)['uri'];
                            $execResults = [];
                            exec($command, $execResults);

                            fclose($temp);

                            foreach ($execResults as $index => $execResult) {
                                if ($execResult == '</collection>' || $execResult == '<collection xmlns="http://www.indexdata.com/turbomarc">') {
                                    unset($execResults[$index]);
                                }
                            }

                            if ($turbomarcData != '') {
                                $turbomarcData .= "\n";
                            }
                            
                            $turbomarcData .= $this->addFormat(implode("\n", $execResults), $record);
                        }
                        $turbomarcData = str_ireplace('<?xml version="1.0"?>', '', $turbomarcData);
                        $result = '<?xml version="1.0"?>' . "\n" . '<collection xmlns="http://www.indexdata.com/turbomarc">' . "\n" . $turbomarcData . "\n" . '</collection>';
                    } else if ($format == 'marc21') {
                        $records = $this->getRecordLoader()->loadBatch($ids);
                        $marc21Data = [];
                        foreach ($records as $record) {
                            $marc21Xml = $this->addFormat($record->getXML('marc21'), $record);
                            $marc21Data[] = str_ireplace('<?xml version="1.0"?>', '', $marc21Xml);
                        }
                        $result = '<?xml version="1.0"?>' . "\n" . '<collection>' . "\n" . implode("\n", $marc21Data) . "\n" . '</collection>';
                    } else {
                        $result = json_encode($ids);
                    }
                }
            }
        }
        // Process and display the exported records
        $response->setContent($result);
        return $response;
    }
    
    private function addFormat ($xml, $record) {        
        $marcxml = simplexml_load_string($xml);
        $marcxml->addChild('format', implode(',', $record->getFormats()));
        return $marcxml->asXML();
    }
}

