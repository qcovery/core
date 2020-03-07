<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Available
 *
 * @author seng
 */
namespace CaLief\Order;

use VuFindSearch\Query\Query;
use Beluga\Search\Factory\SolrDefaultBackendFactory;

class Available {

    protected $caliefConfig;
    protected $solrDriver;

    public function __construct($solrDriver, $caliefConfig) {
        $this->solrDriver = $solrDriver;
        $this->caliefConfig = $caliefConfig;
    }

    public function getSignature($format) {
        $caliefConfig = $this->caliefConfig;
        $iln = $caliefConfig['iln'];
        $signatureData = ''; //$this->solrDriver->getSignatureData($iln);
        $licenceData = ''; //$this->solrDriver->getLicenceData($iln);

        $sigel = '';
        $signature = '';
        $available = false;

        if (in_array($format, $caliefConfig['formats'])) {
            if (empty($signatureData) && $this->checkSigel(array(), $format)) {
                return '!'.$sigel.'! '.$signature;
            }
            foreach ($signatureData as $signatureDate) {
                $sigel = $signatureDate['sigel'];
                $signature = $signatureDate['signature'];
                if ($this->checkSigel($signatureDate, $format)) {
                    if (empty($licenceData)) {
                        return '!'.$sigel.'! '.$signature;
                    } else {
                        foreach ($licenceData as $licenceDate) {
                            if (!$this->checkLicence($licenceDate, $format)) {
                                return '';
                            }
                        }
                        return '!'.$sigel.'! '.$signature;
                    }
                }
            }
        }
        return '';
    }

    private function checkSigel($signatureDate, $format, $sigelOnly = false) {
        $format = str_replace(' ', '_', $format);
        $sigelOk = false;
        $caliefConfig = $this->caliefConfig;
        if (!empty($caliefConfig['sigel_'.$format])) {
            foreach ($caliefConfig['sigel_'.$format] as $regex) {
                if ($regex == 'ppnlink' || preg_match('#'.$regex.'$#', $signatureDate['sigel'])) {
                    $sigelOk = true;
                    break;
                }
            }
        } else {
            foreach ($caliefConfig['sigel_all'] as $regex ) {
                if (preg_match('#'.$regex.'$#', $signatureDate['sigel'])) {
                    $sigelOk = true;
                    break;
                }
            }
        }
        if ($sigelOk) {
            if ($sigelOnly) {
                return true;
            } elseif (!empty($caliefConfig['licencenote_'.$format])) {
                foreach ($caliefConfig['licencenote_'.$format] as $regex) {
                    if (preg_match('#'.$regex.'$#', $signatureDate['licence_note'])) {
                        return true;
                    }
                }
            } else {
                foreach ($caliefConfig['licencenote_all'] as $regex ) {
                    if (preg_match('#'.$regex.'$#', $signatureDate['licence_note'])) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function checkLicence($licenceDate, $format) {
        $caliefConfig = $this->caliefConfig;
        if (!empty($caliefConfig['licence_'.$format])) {
            foreach ($caliefConfig['licence_'.$format] as $regex) {
                if (empty($licenceDate['licence_type']) || preg_match('#'.$regex.'$#', $licenceDate['licence_type'])) {
                    return true;
                }
            }
        } else {
            foreach ($caliefConfig['licence_all'] as $regex ) {
                if (empty($licenceDate['licence_type']) || preg_match('#'.$regex.'$#', $licenceDate['licence_type'])) {
                    return true;
                }
            }
        }
        return false;
    }

    public function checkPpnLink($serviceLocator, $ppn) {
        $caliefConfig = $this->caliefConfig;
        $request = 'id:'.$ppn.' AND collection_details:GBV_ILN_'.$caliefConfig['iln'].' -format:Article';

        $query = new Query();
        $query->setHandler('AllFields');
        $query->setString($request);
        $solr_backend_factory = new SolrDefaultBackendFactory();
        $service = $solr_backend_factory->createService($serviceLocator);
        $result = $service->search($query, 0, 10);
        $resultArray = $result->getResponse();
        $sigelList = $resultArray['docs'][0]['standort_str_mv'];
        $ilnList = $resultArray['docs'][0]['collection_details'];
        $format = $resultArray['docs'][0]['format'][0];

        $ppnValid = false;
        foreach ($ilnList as $iln) {
            if ($iln == 'GBV_ILN_'.$caliefConfig['iln']) {
                $ppnValid = true;
                break;
            }
        }
        if ($ppnValid) {
            foreach ($sigelList as $sigel) {
                if ($this->checkSigel(array('sigel' => $sigel), $format, true) ) {
                    return true;
                }
            }
        }
        return false;
    }
 
}

?>