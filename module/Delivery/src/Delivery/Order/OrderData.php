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
namespace Delivery\Order;

class OrderData {

    protected $solrDriver;

    protected $params;

    protected $formData = ['title' => '', 'fields' => []];

    protected $infoData = ['title' => '', 'fields' => []];

    protected $errors = [];

    protected $dataFields = [
        'PPN' => ['display' => 'PPN', 'name' => 'ppn', 'mandantory' => 1],
        'Article-PPN' => ['display' => 'PPN of Article', 'name' => 'article_ppn', 'mandantory' => 0],
        'format' => ['display' => 'Format', 'name' => 'format', 'mandantory' => 0],
        'Author' => ['display' => 'Author of Article', 'name' => 'article_author', 'mandantory' => 0],
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

    public function __construct($solrDriver = null, $params)
    {
        $this->solrDriver = $solrDriver;
        $this->params = $params;
    }

    public function checkData()
    {
        $errors = [];
        foreach ($this->dataFields as $dataField) {
            $name = $this->params->fromPost($dataField['name']);
            if (isset($name)) {
                if ($dataField['mandantory'] == 1) {
                    if (empty($name)) {
                        $errors[] = $dataField['name'];
                    }
                }
            }
        }
        return $errors;
    }

    public function collectData($signature, $articleAvailable = false)
    {
        $driver = $this->solrDriver;
        $ppn = (!empty($this->params->fromPost('ppn'))) ? $this->params->fromPost('ppn') : $driver->getUniqueID();
        $formats = $driver->getFormats();
        $format = $formats[0];
        $this->formData['fields']['format'] = array_merge($this->dataFields['format'], ['value' => $format]);
        if ($format == 'Article' || $format == 'electronic Article') {
            $this->formData['title'] = 'Article';
            $this->infoData['title'] = 'Journal';

            $authorList = (!empty($this->params->fromPost('article_author'))) ? $this->params->fromPost('article_author') : $driver->getTitleStatement();
            $author = $authorList[0][0];
            $title = (!empty($this->params->fromPost('article_title'))) ? $this->params->fromPost('article_title') : $driver->getTitle();
            $containingWorks = $driver->getContainingWork();
            $issue = (!empty($this->params->fromPost('volume_issue'))) ? $this->params->fromPost('volume_issue') : $containingWorks[0]['issue'];

            $this->formData['fields']['Article-PPN'] = array_merge($this->dataFields['Article-PPN'], ['value' => $ppn]);
            $this->formData['fields']['Author'] = array_merge($this->dataFields['Author'], ['value' => $author]);
            $this->formData['fields']['Article-Title'] = array_merge($this->dataFields['Article-Title'], ['value' => $title]);
            $this->formData['fields']['Issue'] = array_merge($this->dataFields['Issue'], ['value' => $issue]);
            $this->infoData['fields']['PPN'] = array_merge($this->dataFields['PPN'], ['value' => $containingWorks[0]['ppn']]);
            $this->infoData['fields']['Title'] = array_merge($this->dataFields['Title'], ['value' => $containingWorks[0]['title']]);
            $this->infoData['fields']['PublicationPlace'] = array_merge($this->dataFields['PublicationPlace'], ['value' => $containingWorks[0]['location']]);

            if (!$articleAvailable) {
                $this->errors[] = 'Article not available';
            }
        } else {
            $this->formData['fields']['PPN'] = array_merge($this->dataFields['PPN'], ['value' => $ppn]);
            $this->formData['fields']['Author'] = array_merge($this->dataFields['Author'], ['value' => $this->params->fromPost('article_author')]);
            $this->formData['fields']['Title'] = array_merge($this->dataFields['Title'], ['value' => $this->params->fromPost('article_title')]);

            if ($format == 'Journal' || $format == 'eJournal' || $format == 'Serial Volume') {
                $this->formData['title'] = 'Article';
                $this->infoData['title'] = 'Journal';
                $titleSection = $driver->getTitleSection();
                $journalTitle = (empty($titleSection[0][0])) ? $driver->getTitle() : $driver->getTitle() . ' / ' . $titleSection[0][0];

                $this->infoData['fields']['JournalTitle'] = array_merge($this->dataFields['Title'], ['value' => $journalTitle]);

                if ($format == 'Serial Volume') {
                    $containingWorks = $driver->getContainingWork();
                    $volume = (!empty($this->params->fromPost('volume'))) ? $this->params->fromPost('volume') : $driver->getVolumeTitle();
                    $this->formData['fields']['Volume'] = array_merge($this->dataFields['Volume'], ['value' => $volume]);
                    $this->infoData['fields']['PublicationPlace'] = array_merge($this->dataFields['PublicationPlace'], ['value' => $containingWorks[0]['location']]);
                } else {
                    $publicationDetails = $driver->getPublicationDetailsFromMarc();
                    $this->formData['fields']['Volume'] = array_merge($this->dataFields['Volume'], ['value' => $this->params->fromPost('volume')]);
                    $this->formData['fields']['PublicationYear'] = array_merge($this->dataFields['PublicationYear'], ['value' => $this->params->fromPost('publication_year')]);
                    $publicationPlace = $publicationDetails[0]['location'];
                    $this->infoData['fields']['PublicationPlace'] = array_merge($this->dataFields['PublicationPlace'], ['value' => $publicationDetails[0]['location']]);
                }
            } else {
                $this->formData['title'] = 'Copy';
                $this->infoData['title'] = 'Book';

                $titleStatement = $driver->getTitleStatement();
                $title = (empty($titleStatement[0][0])) ? $driver->getTitle() : $driver->getTitle().' / '.implode(', ', $titleStatement[0]);
                $edition = $driver->getEdition();
                $universityNotes = $driver->getUniversityNotes();

                $this->infoData['fields']['Title'] = array_merge($this->dataFields['Title'], ['value' => $title]);
                if (!empty($edition[0][0])) {
                    $this->infoData['fields']['Issue'] = array_merge($this->dataFields['Issue'], ['value' => $driver->getEdition()]);
                }
                if (empty($universityNotes[0][0])) {
                    $publicationDetails = $driver->getPublicationDetailsFromMarc();
                    $publicationPlace = $publicationDetails[0]['location'];
                    $this->infoData['fields']['PubliicationPlace'] = array_merge($this->dataFields['PublicationPlace'], ['value' => $publicationDetails[0]['location']]);
                } else {
                    $this->infoData['fields']['UniversityNotes'] = array_merge($this->dataFields['UniversityNotes'], ['value' => $universityNotes[0][0]]);
                }

                $this->formData['fields']['Pages'] = array_merge($this->dataFields['Pages'], ['value' => $this->params->fromPost('pages')]);
            }
            $this->infoData['fields']['Signature'] = array_merge($this->dataFields['Signature'], ['value' => trim($signature)]);
            $this->formData['fields']['Comment'] = array_merge($this->dataFields['Comment'], ['value' => $this->params->fromPost('comment')]);
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
        return $this->errors;
    }
}

?>
