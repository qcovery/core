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
namespace Delivery;

use VuFindSearch\Query\Query;
use VuFind\Search\Factory\SolrDefaultBackendFactory;

class AvailabilityHelper {

    protected $deliveryConfig;
    protected $solrDriver;

    public function __construct($solrDriver = null, $deliveryConfig = null) 
    {
        if (!empty($solrDriver)) {
            $this->setSolrDriver($solrDriver);
        }
        if (!empty($deliveryConfig)) {
            $this->setDeliveryConfig($deliveryConfig);
        }
    }

    private function getMarcData($item)
    {
        $data = $this->solrDriver->getMarcData($item);
        $flatData = [];
        foreach ($data as $date) {
            $tmpData = [];
            if (is_array($date)) {
                foreach ($date as $key => $value) {
                    $tmpData[$key] = $value['data'][0];
                }
            }
            $flatData[] = $tmpData;
        }
        return $flatData;
    }

    public function setSolrDriver($driver)
    {
        $this->solrDriver = $driver;
    }

    public function setDeliveryConfig($config)
    {
        $this->deliveryConfig = $config->toArray();
    }

    public function getSignature() 
    {
        $deliveryConfig = $this->deliveryConfig;
        $format = array_shift(array_shift($this->getMarcData('Format')));
        $signatureData = $this->getMarcData('Signature');
        $licenceData = $this->getMarcData('Licence');

        $sigel = $signature = '';
        $available = false;
        $sortedSignatureData = [];

        foreach ($deliveryConfig['sigel_all'] as $sigel) {
            foreach ($signatureData as $index => $signatureDate) {
                if (isset($signatureDate['sigel']) && preg_match('#'.$sigel.'$#', $signatureDate['sigel'])) {
                    $sortedSignatureData[] = $signatureDate;
                    unset($signatureData[$index]);
                    break;
                }
            }
        }

        //$sortedSignatureData = array_merge($sortedSignatureData, $signatureData);
        if (in_array($format, $deliveryConfig['formats'])) {
            if (empty($sortedSignatureData) && $this->checkSigel([], $format)) {
                return '!!';
            }
            foreach ($sortedSignatureData as $signatureDate) {
                $sigel = $signatureDate['sigel'] ?? '';
                $signature = $signatureDate['signature'] ?? '';
                if ($this->checkSigel($signatureDate, $format)) {
                    if (empty($licenceData)) {
                        return '!' . $sigel . '! ' . $signature;
                    } else {
                        foreach ($licenceData as $licenceDate) {
                            if (!$this->checkLicence($licenceDate, $format)) {
                                return '';
                            }
                        }
                        return '!' . $sigel . '! ' . $signature;
                    }
                }
            }
        }
        return '';
    }

/*
    public function checkItem()
    {
        $deliveryConfig = $this->deliveryConfig;
        $format = array_shift(array_shift($this->getMarcData('Format')));
        $signatureData = $this->getMarcData('Signature');
        $licenceData = $this->getMarcData('Licence');
        if (in_array($format, $deliveryConfig['formats'])) {
            if (empty($signatureData) && $this->checkSigel([], $format)) {
                return true;
            }
            foreach ($signatureData as $signatureDate) {
                $sigel = $signatureDate['sigel'];
                $signature = $signatureDate['signature'];
                if ($this->checkSigel($signatureDate, $format)) {
                    if (empty($licenceData)) {
                        return true;
                    } else {
                        foreach ($licenceData as $licenceDate) {
                            if (!$this->checkLicence($licenceDate, $format)) {
                                return false;
                            }
                        }
                        return true;
                    }
                }
            }
        }
        return false;
    }
*/

    private function performCheck($item, $data, $format) 
    {
        if (empty($this->deliveryConfig[$item.'_'.$format])) {
            $format = 'all';
        }
        if (!empty($this->deliveryConfig[$item.'_'.$format])) {
            foreach ($this->deliveryConfig[$item.'_'.$format] as $regex) {
                $noMatch = false;
                if (strpos($regex, '!') === 0) {
                    if (empty($data)) {
                        return true;
                    }
                    $regex = substr($regex, 1);
                    $noMatch = true;
                }
                if ((!$noMatch && preg_match('#' . $regex . '$#', $data)) 
                    || ($noMatch && !preg_match('#' . $regex . '$#', $data))) {
                    return true;
                }
            }
        } else {
            return true;
        }
        return false;
    }

    private function checkSigel($signatureDate, $format, $sigelOnly = false) 
    {
        $signatureDate['sigel'] = $signatureDate['sigel'] ?? '';
        $signatureDate['licencenote'] = $signatureDate['licencenote'] ?? '';
        $signatureDate['footnote'] = $signatureDate['footnote'] ?? '';
        $signatureDate['location'] = $signatureDate['location'] ?? '';
        $format = str_replace(' ', '_', $format);
        $sigelOk = $this->performCheck('sigel', $signatureDate['sigel'], $format);
        if ($sigelOk) {
            if ($sigelOnly) {
                return true;
            }
            $sigelOk = $this->performCheck('licencenote', $signatureDate['licencenote'], $format);
            $sigelOk = $sigelOk && $this->performCheck('footnote', $signatureDate['footnote'], $format);
            $sigelOk = $sigelOk && $this->performCheck('location', $signatureDate['locationnote'], $format);
        }
        return $sigelOk;
    }

    private function checkLicence($licenceDate, $format) {
	return $this->performCheck('licence', $licenceDate['licencetype'], $format);
    }

    public function checkParent($serviceLocator, $ppn) {
        return false;
        $deliveryConfig = $this->deliveryConfig;
        $request = 'id:'.$ppn.' AND collection_details:GBV_ILN_'.$deliveryConfig['iln'].' -format:Article';
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
            if ($iln == 'GBV_ILN_'.$deliveryConfig['iln']) {
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
