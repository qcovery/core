<?php
/**
 * Model for MARC records in Solr.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace RecordDriver\RecordDriver;

use VuFind\XSLT\Processor as XSLTProcessor;
use VuFind\Config\SearchSpecsReader;

/**
 * Model for MARC records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrMarc extends SolrDefault
{
    use \VuFind\RecordDriver\IlsAwareTrait;
    use \VuFind\RecordDriver\MarcReaderTrait;
    use \VuFind\RecordDriver\MarcAdvancedTrait;

    protected $solrMarcYaml = 'solrmarc.yaml';

    protected $solrMarcSpecs;

    protected $originalLetters;

    protected $category;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $mainConfig     VuFind main configuration (omit for
     * built-in defaults)
     * @param \Zend\Config\Config $recordConfig   Record-specific configuration file
     * (omit to use $mainConfig as $recordConfig)
     * @param \Zend\Config\Config $searchSettings Search-specific configuration file
     * @param string $marcYaml
     */
    public function __construct($mainConfig = null, $recordConfig = null,
        $searchSettings = null, $marcYaml = null
    ) {
        if (!empty($marcYaml)) {
            $this->$solrMarcYaml = $marcYaml;
        }
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
    }

    /**
     * Get text that can be displayed to represent this record in
     * breadcrumbs. Here for compatibility reasons
     *
     * @return string Breadcrumb text to represent this record.
     */
    public function getBreadcrumb()
    {
        $breadCrumbs = $this->getShortTitle();
        return (is_array($breadCrumbs)) ? $breadCrumbs[0] : $breadCrumbs;
    }

    /**
     * Get and parse SolrMarcSpecs config.
     *
     * @return array
     */
    private function getSolrMarcSpecs($item)
    {
        if (empty($this->solrMarcSpecs)) {
            $this->parseSolrMarcSpecs();
        }
        return $this->solrMarcSpecs[$item];
    }

    /**
     * Get keys of SolrMarcSpecs config.
     *
     *
     */
    public function getSolrMarcKeys($category = '')
    {
        if (empty($this->solrMarcSpecs)) {
            $this->parseSolrMarcSpecs();
        }
        $solrMarcKeys = array_keys($this->solrMarcSpecs);
        if (empty($category)) {
            return $solrMarcKeys;
        } else {
            if (is_array($this->category[$category])) {
                return array_intersect($this->category[$category], $solrMarcKeys);
            } else {
                return [];
            }
        }
    }

    /**
     * Parse SolrMarcSpecs config.
     *
     * @return array
     */
    private function parseSolrMarcSpecs()
    {
        $specsReader = new SearchSpecsReader();
        $rawSolrMarcSpecs = $specsReader->get($this->solrMarcYaml);
//print_r($rawSolrMarcSpecs);
        $solrMarcSpecs = array();
        foreach ($rawSolrMarcSpecs as $item => $solrMarcSpec) {
            $solrMarcSpecs[$item] = [];
            if (array_key_exists('category', $solrMarcSpec)) {
                $category = $solrMarcSpec['category'];
                unset($solrMarcSpec['category']);
            } else {
                $category = 'other';
            }
            if (empty($this->category[$category])) {
                $this->category[$category] = [];
            }
            $this->category[$category][] = $item;
            foreach ($solrMarcSpec as $marcField => $fieldSpec) {
                $solrMarcSpecs[$item][$marcField] = [];
                $conditions = $subfields = $parentMethods = $description = [];
                foreach ($fieldSpec as $subField => $subFieldSpec) {
                    if (is_array($subFieldSpec)) {
                        if ($subField == 'conditions') {
                            foreach ($subFieldSpec as $spec) {
                                $conditions[] = $spec;
                            }
                        } elseif ($subField == 'parent') {
                            foreach ($subFieldSpec as $spec) {
                                $parentMethods[] = $spec;
                            }
                        } elseif ($subField == 'description') {
                            $descriptions[] = $subFieldSpec;
                        } elseif ($subField == 'subfields') {
                            $specs = [];
                            foreach ($subFieldSpec as $index => $spec) {
                                if ($index != 0) {
                                    $specs[] = $spec;
                                }
                            }
                            foreach ($subFieldSpec[0] as $subField) {
                                $subfields[$subField] = $specs;
                            }
                        } else {
                            foreach ($subFieldSpec as $index => $spec) {
                                $subfields[$subField][$index] = $spec;
                            }
                        }
                    }
                    $solrMarcSpecs[$item][$marcField]['conditions'] = $conditions;
                    $solrMarcSpecs[$item][$marcField]['subfields'] = $subfields;
                    $solrMarcSpecs[$item][$marcField]['parent'] = $parentMethods;
                }
            }
            $solrMarcSpecs[$item]['title'] = (array_key_exists('title', $solrMarcSpec)) ? $solrMarcSpec['title'] : $item;
        }
        $this->solrMarcSpecs = $solrMarcSpecs;
        if (empty($this->originalLetters)) {
            $this->originalLetters = $this->getOriginalLetters();
        }
//print_r($solrMarcSpecs);
    }

    /**
     * Get MarcData according to config.
     *
     * @return array
     */
    public function getMarcData($dataName)
    {
        $solrMarcSpecs = $this->getSolrMarcSpecs($dataName);
        if (empty($solrMarcSpecs) && method_exists($this, 'get' . $dataName)) { 
            return call_user_func([$this, 'get' . $dataName]);
        }
        $title = $solrMarcSpecs['title'];
        unset($solrMarcSpecs['title']);
        foreach ($solrMarcSpecs as $field => $subFieldSpecs) {
            $indexData = array();
            if (!empty($subFieldSpecs['parent'])) {
                $tmpKey = '';
                $tmpData = array();
                foreach ($subFieldSpecs['parent'] as $subFieldSpec) {
                    if ($subFieldSpec[0] == 'method') {
                        $method = $subFieldSpec[1];
                        if (is_callable('parent::' . $method)) {
                            $indexValues = call_user_func([$this, 'parent::' . $method]);
                            if (is_array($indexValues)) {
                                foreach ($indexValues as $indexKey => $value) {
                                    $tmpData[$indexKey] = $value;
                                }
                            } else {
                                $tmpData[] = $value;
                            }   
                        }
                    } elseif ($subFieldSpec[0] == 'name') {
                        $tmpKey = $subFieldSpec[1];
                    }
                }
                if (!empty($tmpData)) {
                   if (!empty($tmpKey)) {
                        foreach ($tmpData as $value) {
                            $indexData[] = array($tmpKey => $value);
                        }
                    } else {
                        $indexData[] = $tmpData;
                    }
                }
            }
           foreach ($this->getMarcRecord()->getFields($field) as $fieldObject) {
                $data = $indexData;
                if (!empty($subFieldSpecs['conditions'])) {
                    foreach ($subFieldSpecs['conditions'] as $condition) {
                        if ($condition[0] == 'indicator') {
                            if ($fieldObject->getIndicator($condition[1]) != $condition[2]) {
                                continue 2;
                            }
                        } elseif ($condition[0] == 'field') {
                            $subField = $fieldObject->getSubfield($condition[1]);
                            if (!is_object($subField) || ($subField->getData() != $condition[2] && $condition[2] != '*')) {
                                continue 2;
                            }
                        }
                    }
                }
                if (!empty($subFieldSpecs['subfields'])) {
                    $subFieldList = array();
                    foreach ($subFieldSpecs['subfields'] as $subField => $specs) {
                        $subFieldList[$subField] = array();
                        if (!empty($specs)) {
                            foreach ($specs as $spec) {
                                if (isset($spec[0])) {
                                    if ($spec[0] == 'name') {
                                        $subFieldList[$subField]['name'] = $spec[1];
                                    } elseif ($spec[0] == 'match') {
                                        $subFieldList[$subField]['filter'] = $spec[1];
                                        $subFieldList[$subField]['match'] = intval($spec[2]);
                                    } elseif ($spec[0] == 'replace') {
                                        $subFieldList[$subField]['toReplace'] = $spec[1];
                                        $subFieldList[$subField]['replacement'] = $spec[2];
                                    } elseif ($spec[0] == 'function') {
                                        $subFieldList[$subField]['function'] = $spec[1];
                                    }
                                }
                            }
                        }
                    }
                    foreach ($subFieldList as $subfield => $properties) {
                        $fieldData = [];
                        if (strpos($subfield, 'indicator') !== false) {
                            $indicator = substr($subfield, 9, 1);
                            $fieldData[] = $fieldObject->getIndicator($indicator);
                        } else {
                            foreach ($fieldObject->getSubfields() as $subFieldObject) {
                                if ($subFieldObject->getCode() == $subfield) {
                                    $fieldData[] = $subFieldObject->getData();
                                }
                            }
                        }
                        if (!empty($fieldData)) {
                            foreach ($fieldData as $fieldDate) {
                                if (isset($properties['filter']) && isset($properties['match'])) {
                                    if (preg_match('/'.$properties['filter'].'/', $fieldDate, $matches)) {
                                        $fieldDate = $matches[$properties['match']];
                                    }
                                }
                                if (isset($properties['toReplace']) && isset($properties['replacement'])) {
                                    $fieldDate = preg_replace('/'.$properties['toReplace'].'/', $properties['replacement'], $fieldDate);
                                }
                                if (isset($properties['function'])) {
                                    $function = $properties['function'];
                                    $fieldDate = $function($fieldDate);
                                }
                                if (isset($properties['name'])) {
                                    $data[$properties['name']] = trim($fieldDate);
                                } else {
                                    $data[] = trim($fieldDate);
                                }
                            }
                        }

                    }
                }
                $returnData[] = $data;
            }
            if (empty($returnData)) {
                $returnData = $indexData;
            }
        }
        if (!empty($returnData)) {
            $returnData['title'] = $title;
        }
//print_r($returnData);
        return $returnData;
    }

    /**
     * Get title etc in original letters
     *
     * @return array
     */
    protected function getOriginalLetters()
    {
        $originalLetters = array();
        if ($fields = $this->getMarcRecord()->getFields('880')) {
            foreach ($fields as $field) {
                $subfields = $field->getSubfields();
                $letters = array();
                foreach ($subfields as $subfield) {
                    $code = $subfield->getCode();
                    if ($code == '6') {
                        $index = preg_replace('/-.*$/', '', $subfield->getData());
                    } elseif(!is_numeric($code)) {
                        $letters[$code] = $subfield->getData();
                    }
                }
                if (isset($originalLetters[$index])) {
                    $originalLetters[$index][] .= $letters;
                } else {
                    $originalLetters[$index] = array($letters);
                }
            }
        }
        return $originalLetters;
    }

    /**
     * Get the bibliographic level of the current record.
     *
     * @return string
     */
    public function getBibliographicLevel()
    {
        $leader = $this->getMarcRecord()->getLeader();
        $biblioLevel = strtoupper($leader[7]);

        switch ($biblioLevel) {
        case 'M': // Monograph
            return "Monograph";
        case 'S': // Serial
            return "Serial";
        case 'A': // Monograph Part
            return "MonographPart";
        case 'B': // Serial Part
            return "SerialPart";
        case 'C': // Collection
            return "Collection";
        case 'D': // Collection Part
            return "CollectionPart";
        default:
            return "Unknown";
        }
    }

    /**
     * Get a title for the record.
     *
     * @return string
     */
    public function getTitle()
    {
        $titles = parent::getTitle();
        if (is_array($titles)) {
            $title = array_shift($titles);
        } else {
            $title= $titles;
        }
        $formats = parent::getFormats();
        $indexSeries = parent::getSeries();

        if (!empty($indexSeries) && ($formats[0] == 'Journal' || $formats[0] == 'eJournal')) {
            $seriesArray = $this->getSeriesFromMARC(array('490' => array('a')));
            if (isset($seriesArray[0]['name'])) {
                $title = $seriesArray[0]['name'];
            }
            if (isset($seriesArray[0]['number'])) {
                $title = $title.' '.strval(' ('.$seriesArray[0]['number']).')';
            }
        }
        return $title;
    }

    /**
     * Get formal subjects
     *
     * @return array
     */
    public function getSubjects()
    {
        $subjects = array('subject' => array(), 'geographic' => array());
        $collectedSubjects = array();
        $chains = array();
        $chainCount = -1;

        $classFields = $this->getMarcRecord()->getFields('650');
        $source = null;
        foreach ($classFields as $classField) {
            if ($classField->getIndicator(2) == '7') {
                if (is_object($classField->getSubfield('a'))) {
                    $data = $classField->getSubfield('a')->getData();
                    if (in_array($data, $collectedSubjects) || strlen($data) <= 1) {
                        continue;
                    }
                    if (is_object($classField->getSubfield('9'))) {
                        $detail = preg_replace('/^.*:/', '', $classField->getSubfield('9')->getData());
                        $data .= ' / '.$detail;
                    }
                    foreach ($classField->getSubfields() as $subField) {
                        if (!is_numeric($subField->getCode()) && $subField->getCode() != 'a') {
    	                    $data = $data.' '.$subField->getData();
                        }
                    }
                    $newSource = (is_object($classField->getSubfield('2'))) ? strval($classField->getSubfield('2')->getData()) : '-';
                    if ($source == null || $newSource != $source) {
                        $source = $newSource;
                        $chains[++$chainCount] = array('source' => $source, 'data' => array());
                    }
                    $chains[$chainCount]['data'][] = $data;
                    $collectedSubjects[] = $data;
                }
            }
        }

        $classFields = $this->getMarcRecord()->getFields('600');
        $source = null;
        foreach ($classFields as $classField) {
            if ($classField->getIndicator(2) == '7') {
                if (is_object($classField->getSubfield('a'))) {
                    $data = $classField->getSubfield('a')->getData();
                    if (in_array($data, $collectedSubjects) || strlen($data) <= 1) {
                        continue;
                    }
                    $collectedSubjects[] = $data;
                    foreach ($classField->getSubfields() as $subField) {
                        if (!is_numeric($subField->getCode()) && $subField->getCode() != 'a') {
        	                $data = $data.' '.$subField->getData();
                        }
                    }
                    if (is_object($classField->getSubfield('9'))) {
                        $detail = preg_replace('/^.*:/', '', $classField->getSubfield('9')->getData());
                        $data .= ' / '.$detail;
                    }
                    $newSource = (is_object($classField->getSubfield('2'))) ? strval($classField->getSubfield('2')->getData()) : '-';
                    if ($source == null || $newSource != $source) {
                        $source = $newSource;
                        $chains[++$chainCount] = array('source' => $source, 'data' => array());
                    }
                    $chains[$chainCount]['data'][] = $data;
                }
            }
        }

        $classFields = $this->getMarcRecord()->getFields('689');
        $indicator = null;
        foreach ($classFields as $classField) {
            if (is_object($classField->getSubfield('a'))) {
                $data = $classField->getSubfield('a')->getData();
                if (in_array($data, $collectedSubjects) || strlen($data) <= 1) {
                    continue;
                }
                $collectedSubjects[] = $data;
                foreach ($classField->getSubfields() as $subField) {
                    if (!is_numeric($subField->getCode()) && $subField->getCode() != 'a') {
        	            $data = $data.' '.$subField->getData();
                    }
                }
                $newIndicator = $classField->getIndicator(1);
                if ($indicator == null || $newIndicator != $indicator) {
                    $indicator = $newIndicator;
                    $chains[++$chainCount] = array('source' => '', 'data' => array());
                }
                $chains[$chainCount]['data'][] = $data;
            }
        }

        $classFields = $this->getMarcRecord()->getFields('650');
        $source = null;
        foreach ($classFields as $classField) {
            if ($classField->getIndicator(2) != '7') {
                if (is_object($classField->getSubfield('a'))) {
                    $data = $classField->getSubfield('a')->getData();
                    if (in_array($data, $collectedSubjects) || strlen($data) <= 1) {
                        continue;
                    }
                    $collectedSubjects[] = $data;
                    foreach ($classField->getSubfields() as $subField) {
                    if (!is_numeric($subField->getCode()) && $subField->getCode() != 'a') {
        	                $data = $data.' '.$subField->getData();
                        }
                    }
                    $newSource = (is_object($classField->getSubfield('2'))) ? strval($classField->getSubfield('2')->getData()) : '-';
                    if ($source == null || $newSource != $source) {
                        $source = $newSource;
                        $chains[++$chainCount] = array('source' => $source, 'data' => array());
                    }
                    $chains[$chainCount]['data'][] = $data;
                }
            }
        }

        $classFields = $this->getMarcRecord()->getFields('600');
        $source = null;
        foreach ($classFields as $classField) {
            if ($classField->getIndicator(2) != '7') {
                if (is_object($classField->getSubfield('a'))) {
                   $data = $classField->getSubfield('a')->getData();
                    if (in_array($data, $collectedSubjects) || strlen($data) <= 1) {
                        continue;
                    }
                    $collectedSubjects[] = $data;
                    foreach ($classField->getSubfields() as $subField) {
                        if (!is_numeric($subField->getCode()) && $subField->getCode() != 'a') {
    	                    $data = $data.' '.$subField->getData();
                        }
                    }
                    if (is_object($classField->getSubfield('9'))) {
                        $detail = preg_replace('/^.*:/', '', $classField->getSubfield('9')->getData());
                        $data .= ' / '.$detail;
                    }
                    $newSource = (is_object($classField->getSubfield('2'))) ? strval($classField->getSubfield('2')->getData()) : '-';
                    if ($source == null || $newSource != $source) {
                        $source = $newSource;
                        $chains[++$chainCount] = array('source' => $source, 'data' => array());
                    }
                    $chains[$chainCount]['data'][] = $data;
                }
            }
        }

        $classFields = $this->getMarcRecord()->getFields('653');
        $source = null;
        foreach ($classFields as $classField) {
            if (is_object($classField->getSubfield('a'))) {
                $data = $classField->getSubfield('a')->getData();
                if (in_array($data, $collectedSubjects) || strlen($data) <= 1) {
                    continue;
                }
                $newSource = (is_object($classField->getSubfield('2'))) ? strval($classField->getSubfield('2')->getData()) : '';
                if ($source == null || $newSource != $source) {
                    $source = $newSource;
                    $chains[++$chainCount] = array('source' => $source, 'data' => array());
                }
                foreach ($classField->getSubfields('a') as $subField) {
                    $chains[$chainCount]['data'][] = $subField->getData();
                    $collectedSubjects[] = $subField->getData();
                }
            }
        }

        $subjects['subject'] = $chains;

        $collectedSubjects = array();
        $chains = array();
        $chainCount = -1;
        $classFields = $this->getMarcRecord()->getFields('651');
        $source = null;
        foreach ($classFields as $classField) {
            if (is_object($classField->getSubfield('a'))) {
                $data = $classField->getSubfield('a')->getData();
                if (in_array($data, $collectedSubjects) || strlen($data) <= 1) {
                    continue;
                }
                $collectedSubjects[] = $data;
                $newSource = (is_object($classField->getSubfield('2'))) ? strval($classField->getSubfield('2')->getData()) : '-';
                if ($source == null || $newSource != $source) {
                    $source = $newSource;
                    $chains[++$chainCount] = array('source' => $source, 'data' => array());
                }
                $chains[$chainCount]['data'][] = $data;
            }
        }

        $subjects['geographic'] = $chains;
        return $subjects;
    }

    /**
     * Get an HTML representation of the data in this record.
     *
     * @return html.
     */
    public function getStaffView()
    {
        return XSLTProcessor::process(
            'record-marc.xsl', trim($this->getMarcRecord()->toXML())
        );
    }

    /**
     * Get containing work infos of the record.
     *
     * @return array
     */
    public function getContainingWork()
    {
        $containingWorks = array();
        $containingWorkFields = $this->marcRecord->getFields('773');
        if (empty($containingWorkFields)) {
            $containingWorkFields = $this->marcRecord->getFields('800');
            if (empty($containingWorkFields)) {
                return array();
            }
        }

        foreach ($containingWorkFields as $containingWorkField) {
            $containingWork = array();
            if (is_object($containingWorkField->getSubfield('i'))) {
                $containingWork['prefix'] = $this->prepareData($containingWorkField->getSubfield('i')->getData());
            }
            if (is_object($containingWorkField->getSubfield('t'))) {
                $containingWork['title'] = $this->prepareData($containingWorkField->getSubfield('t')->getData());
            }
            if (is_object($containingWorkField->getSubfield('z'))) {
                $containingWork['isn'] = substr(strrchr($containingWorkField->getSubfield('z')->getData(), ')'), 1);
            }
            if (is_object($containingWorkField->getSubfield('x'))) {
                $containingWork['isn'] = substr(strrchr($containingWorkField->getSubfield('x')->getData(), ')'), 1);
            }
            if (is_object($containingWorkField->getSubfield('w'))) {
                $containingWork['ppn'] = substr(strrchr($containingWorkField->getSubfield('w')->getData(), ')'), 1);
            }
            if (is_object($containingWorkField->getSubfield('d'))) {
                $containingWork['location'] = $containingWorkField->getSubfield('d')->getData();
            }
            if (is_object($containingWorkField->getSubfield('g'))) {
                $containingWork['issue'] = $containingWorkField->getSubfield('g')->getData();
            }
            if (empty($containingWork['title'])) {
                $containingWork['title'] = 'Zur Gesamtaufnahme';
            }
            $containingWorks[] = $containingWork;
        }
        return $containingWorks;
    }
}

