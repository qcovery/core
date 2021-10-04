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

    protected $deliveryDomain;

    protected $pluginConfig;

    protected $solrDriver;

    protected $deliveryDriver;

    protected $driverManager;

    protected $params;

    protected $formData;

    protected $formTitle;

//    protected $infoData = ['title' => '', 'fields' => []];

    protected $errors = [];

    protected $missingFields = [];

    protected $dataFields;

    protected $order_id;

    protected $format;

    public function __construct(PluginManager $driverManager, $params, $orderDataConfig, $pluginConfig, $deliveryDomain)
    {
        $this->deliveryDomain = $deliveryDomain;
        $this->pluginConfig = $pluginConfig;
        $this->dataFields = $orderDataConfig;
        $this->driverManager = $driverManager;
        $this->params = $params;
    }

    public function setSolrDriver($solrDriver, $deliveryMarcYaml = null)
    {
        $this->solrDriver = $solrDriver;
        if (!empty($deliveryMarcYaml)) {
            $this->solrDriver->addSolrMarcYaml($deliveryMarcYaml);
        }
        $formats = $solrDriver->getMarcData('Format');
        $this->format = $formats[0][0]['data'][0];
    }

    public function setFormat() {
        $this->format = $this->params->fromPost('format') ?? null;
        return $this->format;
    }

    public function sendOrder($user)
    {
        if (!$this->checkData()) {
            return false;
        }
        if ($this->setDeliveryDriver()) {
            $orderData = $this->deliveryDriver->prepareOrder($user);
            foreach ($this->dataFields as $fieldSpecs) {
                if (!empty($fieldSpecs['orderfield']) && !empty($fieldSpecs['form_name'])) {
                    $prefix = $fieldSpecs['orderfieldprefix'] ?? '';
                    $value = $prefix . $this->params->fromPost($fieldSpecs['form_name']);
                    if (empty($orderData[$fieldSpecs['orderfield']])) {
                        $orderData[$fieldSpecs['orderfield']] = $value;
                    } elseif ($value != $orderData[$fieldSpecs['orderfield']]) {
                        $orderData[$fieldSpecs['orderfield']] .= ', ' . $value;
                    }
                }
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
        $listData = [];
        foreach ($this->dataFields as $fieldSpecs) {
            if (!empty($fieldSpecs['tablefield'])) {
                $field = $fieldSpecs['tablefield'];
                $listData[$field] = $this->params->fromPost($fieldSpecs['form_name']);
            }
        }
        foreach ($this->dataFields as $fieldSpecs) {
            if (isset($fieldSpecs['fallbacktablefield'])) {
                $field = $fieldSpecs['fallbacktablefield'];
                if (empty($listData[$field])) {
                    $listData[$field] = $this->params->fromPost($fieldSpecs['form_name']);
                }
            }
        }
        $listData['source'] = $this->params->fromQuery('searchClassId') ?? $this->params->fromPost('searchClassId');
        $table->createRowForUserDeliveryId($user->user_delivery_id, $this->order_id, $this->deliveryDomain, $listData);
    }

    public function getSavedOrderData($table, $notDelivered = true) {
        $savedOrderData = $table->getCompleteList($this->deliveryDomain, $notDelivered);
        foreach ($savedOrderData as $index => $order) {
            if (!empty($order['extra_metadata'])) {
                $extraData = explode(';', $order['extra_metadata']);
                foreach ($extraData as $data) {
                    list($key, $value) = explode(':', $data, 2);
                    if (!empty($value)) {
                        $savedOrderData[$index][$key] = $value;
                    }
                }
            }
            unset($savedOrderData[$index]['extra_metadata']);
        }
        return $savedOrderData;
    }

    public function reOrderAll($recordLoader, $deliveryMarcYaml, $table) {
        $orderData = $this->getSavedOrderData($table, true);
        foreach ($orderData as $orderValues) {
            $id = $orderValues['record_id'];
            $searchClassId = $orderValues['source'];
            try {
                $driver = $recordLoader->load($id, $searchClassId);
            } catch (\Exception $e) {
                $driver = null;
            }
            if ($driver) {
                $this->setSolrDriver($driver, $deliveryMarcYaml);
                $this->reOrder($table, $orderValues);
            }
        }
    }

    public function reOrder($table, $orderValues) {
        if ($this->setDeliveryDriver()) {
	    $this->collectData();
            $user = (object) [
                'firstname' => $orderValues['firstname'],
                'lastname' => $orderValues['lastname'],
                'cat_id' => $orderValues['userid'],
                'delivery_email' => $orderValues['email'],
                'patron_types' => ''
            ];
            $orderData = $this->deliveryDriver->prepareOrder($user);
            foreach ($this->dataFields as $fieldSpecs) {
                $key = $fieldSpecs['form_name'];
                if (!empty($fieldSpecs['orderfield']) && !empty($key)) {
                    $prefix = $fieldSpecs['orderfieldprefix'] ?? '';
                    if (!empty($orderValues[$key])) {
                        $value = $prefix . $orderValues[$key];
                    } else {
                        $fieldTypes = explode(',', $fieldSpecs['type']);
                        foreach ($fieldTypes as $fieldType) {
                            if (!empty($this->formData[$fieldType][$key])) {
                                $value = $prefix . $this->formData[$fieldType][$key];
                                break;
                            }
                        }
                    }
                    if (empty($orderData[$fieldSpecs['orderfield']])) {
                        $orderData[$fieldSpecs['orderfield']] = $value;
                    } elseif ($value != $orderData[$fieldSpecs['orderfield']]) {
                        $orderData[$fieldSpecs['orderfield']] .= ', ' . $value;
                    }
                }
            }
            if ($this->order_id = $this->deliveryDriver->sendOrder($orderData)) {
                $table->updateOrder($orderValues['delivery'], $this->order_id);
                return true;
            } else {
                $this->errors = $this->deliveryDriver->getErrors();
            }
        }
        return false;
    }

    public function getOrderStatus()
    {
        if ($this->setDeliveryDriver()) {
            return $this->deliveryDriver->getOrderStatus($this->order_id);
        }
    }

    private function setDeliveryDriver()
    {
        $deliveryDriver = $this->pluginConfig['plugin'];
        if (empty($deliveryDriver)) {
            throw new \Exception('Delivery driver configuration missing');
        }
        if (!$this->driverManager->has($deliveryDriver)) {
            throw new \Exception('Delivery driver missing: ' . $deliveryDriver);
        }
        $this->deliveryDriver = $this->driverManager->get($deliveryDriver);
        try {
            $this->deliveryDriver->setConfig($this->pluginConfig);
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
            if (null !== $this->params->fromPost($fieldSpecs['form_name'])
                && (in_array('all', $fieldSpecs['formats']) 
                    || in_array($this->format, $fieldSpecs['formats']))) {
                if (isset($fieldSpecs['mandatory']) && $fieldSpecs['mandatory'] == 1) {
                    if (empty($this->params->fromPost($fieldSpecs['form_name']))) {
                        $failed = true;
                        $this->missingFields[] = $fieldSpecs['form_name'];
                    }
                }
            }
        }
        return !$failed;
    }

    public function collectData($presetData = [])
    {
        $deliveryData = [];
	$format = $this->format;

        if (isset($this->solrDriver)) {
            if ($format == 'Article' || $format == 'electronic Article') {
                $deliveryData = $this->solrDriver->getMarcData('DeliveryDataArticle');
            } elseif ($format == 'Journal' || $format == 'eJournal') {
                $deliveryData = $this->solrDriver->getMarcData('DeliveryDataJournal');
            } elseif ($format == 'Serial Volume') {
                $deliveryData = $this->solrDriver->getMarcData('DeliveryDataSerialVolume');
            } else {
                $deliveryData = $this->solrDriver->getMarcData('DeliveryData');
	    }
        }

        $flatData = [];
        foreach ($deliveryData as $deliveryDate) {
            if (is_array($deliveryDate)) {
                foreach ($deliveryDate as $key => $item) {
                    $flatData[$key] = implode(', ', $item['data']);
                }
            }
        }

        foreach ($this->dataFields as $fieldKey => $fieldSpecs) {
            if (in_array('all', $fieldSpecs['formats']) || in_array($format, $fieldSpecs['formats'])) {
                $key = $fieldSpecs['form_name'];
                if (!empty($presetData[$fieldKey])) {
                    $data = $presetData[$fieldKey];
                } else {
                    $data = $this->params->fromPost($key);
                }
                if (empty($data) && !empty($flatData[$fieldKey])) {
                    $data = $flatData[$fieldKey];
                }
                $dataArray = array_merge($this->dataFields[$fieldKey], ['value' => $data]);
                $fieldTypes = explode(',', $fieldSpecs['type']);
                foreach ($fieldTypes as $fieldType) {
                    $this->formData[$fieldType][$fieldKey] = $dataArray;
                    if (empty($this->formTitle[$fieldType])) {
                        $this->formTitle[$fieldType] = $this->getTitle($format, $fieldType);
                    }
                }
            }
        }
    }

    public function getFormData($fieldType)
    {
        return $this->formData[$fieldType];
    }

    public function getFormTitle($fieldType)
    {
        return $this->formTitle[$fieldType];
    }

    private function getTitle($format, $type = 'info')
    {
        if ($format == 'Article' || $format == 'electronic Article') {
            return ($type == 'info') ? 'Journal' : 'Article';
        } elseif ($format == 'Journal' || $format == 'eJournal' || $format == 'Serial Volume') {
            return ($type == 'info' || $type == 'openform') ? 'Journal' : 'Article';
        } else {
            return ($type == 'info' || $type == 'openform') ? 'Book' : 'Article';
        }
    }

    public function getOrderId() {
        return $this->order_id;
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
