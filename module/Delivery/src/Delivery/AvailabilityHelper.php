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

    protected $signatureList;

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
        $this->deliveryConfig = $config;
    }

    public function getParentId() 
    {
        $format = array_shift(array_shift($this->getMarcData('Format')));
        if (in_array($format, $this->deliveryConfig['formats'])) {
            $articleData = $this->getMarcData('DeliveryDataArticle');
            foreach ($articleData as $articleDate) {
                if (!empty($articleDate['ppn'])) {
                    return $articleDate['ppn'];
                }
            }
        }
        return null;
    }

    public function getSignatureList() 
    {
        $this->checkSignature();
        return $this->signatureList;
    }

    public function checkSignature() 
    {
        $deliveryConfig = $this->deliveryConfig;
        $format = array_shift(array_shift($this->getMarcData('Format')));
        $signatureData = $this->getMarcData('Signature');
        $licenceData = $this->getMarcData('Licence');

        $checkPassed = false;
        $this->signatureList = [];

        $sortedSignatureData = [];
        foreach ($deliveryConfig['sigel_all'] as $sigel) {
            foreach ($signatureData as $index => $signatureDate) {
                if (isset($signatureDate['sigel']) && preg_match('#'.$sigel.'$#', $signatureDate['sigel'])) {
                    $sortedSignatureData[] = $signatureDate;
                    unset($signatureData[$index]);
                    //break;
                }
            }
        }
        if (in_array($format, $deliveryConfig['formats'])) {
            if (empty($sortedSignatureData)) {
                foreach ($signatureData as $signatureDate) {
                    if ($this->checkSigel($signatureDate, $format)) {
                        $this->signatureList[] = '!!';
                        return true;
                    }
                }
            }

            foreach ($sortedSignatureData as $signatureDate) {
                $sigel = $signatureDate['sigel'] ?? '';
                $signature = $signatureDate['signature'] ?? '';
                if ($this->checkSigel($signatureDate, $format)) {
                    if (!empty($licenceData)) {
                        foreach ($licenceData as $licenceDate) {
                            if (!$this->checkLicence($licenceDate, $format)) {
                                return false;
                            }
                        }
                    }
                    $this->signatureList[] = '!' . $sigel . '! ' . $signature;
                    $checkPassed = true;
                }
            }
        }
        return $checkPassed;
    }

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
        $sigel = $signatureDate['sigel'] ?? '';
        $indicator = $signatureDate['indicator'] ?? '';
        $licencenote = $signatureDate['licencenote'] ?? '';
        $footnote = $signatureDate['footnote'] ?? '';
        $location = $signatureDate['location'] ?? '';
        $format = str_replace(' ', '_', $format);

        $sigelOk = $this->performCheck('sigel', $sigel, $format);
        if ($sigelOk && !$sigelOnly) {
            $sigelOk = $this->performCheck('indicator', $indicator, $format);
            $sigelOk = $sigelOk && $this->performCheck('licencenote', $licencenote, $format);
            $sigelOk = $sigelOk && $this->performCheck('footnote', $footnote, $format);
            $sigelOk = $sigelOk && $this->performCheck('location', $location, $format);
        }
        return $sigelOk;
    }

    private function checkLicence($licenceDate, $format) {
        $licencetype = $licenceDate['licencetype'] ?? '';
	return $this->performCheck('licence', $licencetype, $format);
    } 
}

?>
