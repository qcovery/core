<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DataHandler
 *
 * @author seng
 */
namespace Delivery;

use Delivery\Driver\PluginManager;

class DataHandler {

    protected $deliveryConfig;

    protected $solrDriver;

    protected $deliveryDriver;

    protected $driverManager;

    protected $params;

    protected $formData = ['title' => '', 'fields' => []];

    protected $infoData = ['title' => '', 'fields' => []];

    protected $errors = [];

    protected $missingFields = [];

    protected $dataFields;

    protected $order_id;

    public function __construct(PluginManager $driverManager, $params, $orderDataConfig, $deliveryConfig)
    {
        $this->deliveryConfig = $deliveryConfig;
        $this->dataFields = $orderDataConfig;
        $this->driverManager = $driverManager;
        $this->params = $params;
    }

    public function setSolrDriver($solrDriver)
    {
        $this->solrDriver = $solrDriver;
    }

    public function sendOrder($user)
    {
        if (!$this->checkData()) {
            return false;
        }
        if ($this->setDeliveryDriver()) {
            $orderData = $this->deliveryDriver->prepareOrder($user);
            foreach ($this->dataFields as $fieldSpecs) {
                $prefix = $fieldSpecs['orderfieldprefix'] ?? '';
                $orderData[$fieldSpecs['orderfield']] = $prefix . $this->params->fromPost($fieldSpecs['form_name']) ?: '';
            }
            if ($this->order_id = $this->deliveryDriver->sendOrder($orderData)) {
                return true;
            } else {
                $this->errors = $this->deliveryDriver->getErrors();
            }
        }
        return false;
    }

    public function insertOrderData($user, $table)
    {
        $tableFields = ['record_id', 'title', 'author', 'year'];
        $listData = [];
        foreach ($this->dataFields as $fieldSpecs) {
            if (isset($fieldSpecs['tablefield']) && in_array($fieldSpecs['tablefield'], $tableFields)) {
                $field = $fieldSpecs['tablefield'];
                $listData[$field] = $this->params->fromPost($fieldSpecs['form_name']);
            }
        }
        $listData['source'] = $this->params->fromQuery('searchClassId') ?? $this->params->fromPost('searchClassId');

        if (!empty($listData['record_id'])) {
            $table->createRowForUserDeliveryId($user->user_delivery_id, $this->order_id, $listData);
        }
    }

    private function setDeliveryDriver()
    {
        $deliveryDriver = $this->deliveryConfig['Order']['plugin'];
        if (empty($deliveryDriver)) {
            throw new \Exception('Delivery driver configuration missing');
        }
        if (!$this->driverManager->has($deliveryDriver)) {
            throw new \Exception('Delivery driver missing: ' . $deliveryDriver);
        }
        $this->deliveryDriver = $this->driverManager->get($deliveryDriver);
        try {
            $this->deliveryDriver->setConfig($this->deliveryConfig[$deliveryDriver]);
            $this->deliveryDriver->init();
        } catch (\Exception $e) {
            throw $e;
        }
        return true;
    }

    private function checkData()
    {
        $failed = false;
        $this->missingFields = [];
        foreach ($this->dataFields as $fieldSpecs) {
            if (isset($fieldSpecs['mandantory']) && $fieldSpecs['mandantory'] == 1) {
                if (empty($this->params->fromPost($fieldSpecs['form_name']))) {
                    $failed = true;
                    $this->missingFields[] = $fieldSpecs['form_name'];
                }
            }
        }
        return !$failed;
    }

    public function collectData($signature, $articleAvailable = false)
    {
        $formats = $this->solrDriver->getMarcData('Format');
        $format = $formats[0][0]['data'][0];

        if ($format == 'Article' || $format == 'electronic Article') {
            $deliveryData = $this->solrDriver->getMarcData('DeliveryDataArticle');
        } elseif ($format == 'Journal' || $format == 'eJournal') {
            $deliveryData = $this->solrDriver->getMarcData('DeliveryDataJournal');
        } elseif ($format == 'Serial Volume') {
            $deliveryData = $this->solrDriver->getMarcData('DeliveryDataSerialVolume');
        } else {
            $deliveryData = $this->solrDriver->getMarcData('DeliveryData');
        }

        $flatData = [];
        foreach ($deliveryData as $deliveryDate) {
            if (is_array($deliveryDate)) {
                foreach ($deliveryDate as $key => $item) {
                    $flatData[$key] = $item['data'][0];
                }
            }
        }
        $flatData['format'] = $format;

        foreach ($this->dataFields as $fieldKey => $fieldSpecs) {
            if (in_array('all', $fieldSpecs['formats']) || in_array($format, $fieldSpecs['formats'])) {
                $key = $fieldSpecs['form_name'];
                $data = $this->params->fromPost($key);
                if (empty($data) && !empty($flatData[$fieldKey])) {
                    $data = $flatData[$fieldKey];
                }
                $dataArray = array_merge($this->dataFields[$fieldKey], ['value' => $data]);
                if ($fieldSpecs['type'] == 'info') {
                    $this->infoData['fields'][$fieldKey] = $dataArray;
                } else {
                    $this->formData['fields'][$fieldKey] = $dataArray;
                }
            }
        }
        $this->infoData['title'] = $this->getTitle($format, 'info');
        $this->formData['title'] = $this->getTitle($format, 'form');

        if (($format == 'Article' || $format == 'electronic Article') && !$articleAvailable) {
            $this->errors[] = 'Article not available';
        }
    }

    public function getFormData()
    {
        return $this->formData;
    }
 
    public function getInfoData()
    {
        return $this->infoData;
    }

    private function getTitle($format, $type = 'info')
    {
        if ($format == 'Article' || $format == 'electronic Article') {
            return ($type == 'info') ? 'Journal' : 'Article';
        } elseif ($format == 'Journal' || $format == 'eJournal' || $format == 'Serial Volume') {
            return ($type == 'info') ? 'Journal' : 'Article';
        } else {
            return ($type == 'info') ? 'Book' : 'Copy';
        }
    }

    public function getErrors() {
        $errors = ($fieldErrors) ? $this->fieldErrors : $this->errors;
        $this->fieldErrors = $this->errors = [];
        return $errors;
    }

    public function getMissingFields() {
        return $this->missingFields;
    }
}

?>
