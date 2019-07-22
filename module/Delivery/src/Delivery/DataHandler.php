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

    protected $dataFields;
/*
 = [
        'PPN' => ['display' => 'PPN', 'name' => 'ppn', 'mandantory' => 1],
        'Article-PPN' => ['display' => 'PPN of Article', 'name' => 'article_ppn', 'mandantory' => 0],
        'format' => ['display' => 'Format', 'name' => 'format', 'mandantory' => 0],
        'Author' => ['display' => 'Author of Article', 'name' => 'author', 'mandantory' => 0],
        'Article-Title' => ['display' => 'Title of Article', 'name' => 'article_title', 'mandantory' => 0],
        'Issue' => ['display' => 'Issue', 'name' => 'volume_issue', 'mandantory' => 0],
        'Title' => ['display' => 'Title', 'name' => 'title', 'mandantory' => 0],
        'JournalTitle' => ['display' => 'Title of Journal', 'name' => 'title', 'mandantory' => 0],
        'PublicationPlace' => ['display' => 'Publication Place', 'name' => 'publication_place', 'mandantory' => 0],
        'PublicationYear' => ['display' => 'Year', 'name' => 'publication_year', 'mandantory' => 1],
        'UniversityNotes' => ['display' => 'University', 'name' => 'university_notes', 'mandantory' => 0],
        'Volume' => ['display' => 'Volume', 'name' => 'volume', 'mandantory' => 0],
        'Pages' => ['display' => 'Pages', 'name' => 'pages', 'mandantory' => 1],
        'Signature' => ['display' => 'Signature', 'name' => 'signature', 'mandantory' => 0],
        'Comment' => ['display' => 'Comment', 'name' => 'comment', 'mandantory' => 0]
    ]; 
*/
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
                $entry = $fieldSpecs['orderfieldprefix'] ?? '';
                $orderData[$fieldSpecs['orderfield']] .= $entry . $this->params->fromPost($fieldSpecs['form_name']) ?: '';
            }
            $this->deliveryDriver->sendOrder($orderData);
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
        $this->errors = [];
        foreach ($this->dataFields as $dataField) {
            if ($dataField['mandantory'] == 1) {
                if (empty($this->params->fromPost($dataField['name']))) {
                    $failed = true;
                    $this->errors[] = $dataField['name'];
                }
            }
        }
        return !$failed;
    }

    public function prepareOrder($user)
    {
        $email = $this->params->fromPost('email') ?: $user->delivery_email;
        $mailData = [];
        $mailData['clientName'] = $user->firstname . ' ' . $user->lastname;
        $mailData['contactPersonName'] = $user->firstname . ' ' . $user->lastname;
        $mailData['clientIdentifier'] = $user->cat_id;
        $mailData['delEmailAddress'] = $email;
        foreach ($this->dataFields as $fieldSpecs) {
            $entry = $fieldSpecs['orderfieldprefix'] ?? '';
            $mailData[$fieldSpecs['orderfield']] .= $entry . $this->params->fromPost($fieldSpecs['form_name']) ?: '';
        }
        return $mailData;
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
            foreach ($deliveryDate as $key => $item) {
                $flatData[$key] = $item['data'][0];
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

    public function getErrors()
    {
        $errors = $this->errors;
        $this->errors = [];
        return $errors;
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
}

?>
