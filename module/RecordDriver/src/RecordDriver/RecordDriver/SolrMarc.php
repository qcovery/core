<?php
/**
 * Module RecordDriver: SolrMarc Parser
 *
 * PHP version 7
 *
 * Copyright (C) Staats- und Universitätsbibliothek Hamburg 2018.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/beluga-core
 */
namespace RecordDriver\RecordDriver;

use VuFind\XSLT\Processor as XSLTProcessor;
use VuFind\Config\SearchSpecsReader;
use VuFind\RecordDriver\SolrDefault;

/**
 * Model for MARC records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class SolrMarc extends SolrDefault
{
    use \VuFind\RecordDriver\IlsAwareTrait;
    use \VuFind\RecordDriver\MarcReaderTrait;

    /**
     * actual Configuration (yaml)
     *
     * @var string
     */
    protected $solrMarcYamls = [];

    /**
     * Specifications to use
     *
     * @var array
     */
    protected $solrMarcSpecs = [];

    /**
     * Data in original letters
     *
     * @var array
     */
    protected $originalLetters;

    /**
     * Keys of SolrMarc Specifications
     *
     * @var array
     */
    protected $solrMarcKeys;

    /**
     * Constructor
     *
     * @param \Laminas\Config\Config $mainConfig     VuFind main configuration (omit for
     * built-in defaults)
     * @param \Laminas\Config\Config $recordConfig   Record-specific configuration file
     * (omit to use $mainConfig as $recordConfig)
     * @param \Laminas\Config\Config $searchSettings Search-specific configuration file
     * @param string $marcYaml
     */
    public function __construct($mainConfig = null, $recordConfig = null,
        $searchSettings = null, $solrMarcYaml = null
    ) {
        $this->addSolrMarcYaml($solrMarcYaml, false);
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
    }

    /**
     * Set and parse SolrMarcSpecs config.
     *
     * @return void
     */
    public function addSolrMarcYaml($solrMarcYaml, $parse = true)
    {
        if (!in_array($solrMarcYaml, $this->solrMarcYamls)) {
            $this->solrMarcYamls[] = $solrMarcYaml;
            if ($parse) {
                $this->parseSolrMarcSpecs($solrMarcYaml);
            }
        }
    }

    /**
     * Get and parse SolrMarcSpecs config.
     *
     * @return array
     */
    private function getSolrMarcSpecs($item)
    {
        if (empty($this->solrMarcSpecs)) {
            foreach ($this->solrMarcYamls as $solrMarcYaml) {
                $this->parseSolrMarcSpecs($solrMarcYaml);
            }
        }
        return $this->solrMarcSpecs[$item] ?? [];
    }

    /**
     * Get keys of SolrMarcSpecs config.
     *
     * @return array
     */
    public function getSolrMarcKeys($category = '', $others = false)
    {
        if (empty($this->solrMarcSpecs)) {
            foreach ($this->solrMarcYamls as $solrMarcYaml) {
                $this->parseSolrMarcSpecs($solrMarcYaml);
            }
        }
        $specKeys = array_keys($this->solrMarcSpecs);
        if (empty($category)) {
            if (!$others) {
                if (($key = array_search('other', $specKeys)) !== false) {
                    unset($specKeys[$key]);
                }
            }
            return $specKeys;
        } else {
            if (is_array($this->solrMarcKeys[$category])) {
                return array_intersect($this->solrMarcKeys[$category], $specKeys);
            } else {
                return [];
            }
        }
    }

    /**
     * Parse SolrMarcSpecs config.
     *
     * @return null
     */
    private function parseSolrMarcSpecs($solrMarcYaml)
    {
        $specsReader = new SearchSpecsReader();
        $rawSolrMarcSpecs = $specsReader->get($solrMarcYaml);
        $solrMarcSpecs = $this->solrMarcSpecs;
        foreach ($rawSolrMarcSpecs as $item => $solrMarcSpec) {
            $solrMarcSpecs[$item] = [];
            if (array_key_exists('category', $solrMarcSpec)) {
                $category = $solrMarcSpec['category'];
                unset($solrMarcSpec['category']);
            } else {
                $category = 'other';
            }
            if (empty($this->solrMarcKeys[$category])) {
                $this->solrMarcKeys[$category] = [];
            }
            $this->solrMarcKeys[$category][] = $item;
            if (!empty($solrMarcSpec['originalletters']) && $solrMarcSpec['originalletters'] == 'no') {
                $solrMarcSpecs[$item]['originalletters'] = 'no';
            }
	    unset($solrMarcSpec['originalletters']);
	    if (!empty($solrMarcSpec['mandatory-field'])) {
                $solrMarcSpecs[$item]['mandatory-field'] = $solrMarcSpec['mandatory-field'];
            }
            unset($solrMarcSpec['mandatory-field']);
            $solrMarcSpecs[$item]['view-method'] = 'default';
	    if (!empty($solrMarcSpec['view-method'])) {
                $solrMarcSpecs[$item]['view-method'] = $solrMarcSpec['view-method'];
            }
            unset($solrMarcSpec['view-method']);
            $solrMarcSpecs[$item]['match-key'] = '';
	    if (!empty($solrMarcSpec['match-key'])) {
                $solrMarcSpecs[$item]['match-key'] = $solrMarcSpec['match-key'];
            }
            unset($solrMarcSpec['match-key']);
            $conditions = $subfields = $parentMethods = $description = [];
	    foreach ($solrMarcSpec as $marcField => $fieldSpec) {
                if (!empty($fieldSpec)) {
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
                    }
                }
                $solrMarcSpecs[$item][$marcField]['conditions'] = $conditions;
                $solrMarcSpecs[$item][$marcField]['subfields'] = $subfields;
                $solrMarcSpecs[$item][$marcField]['parent'] = $parentMethods;
            }
        }
        $this->solrMarcSpecs = $solrMarcSpecs;
        if (empty($this->originalLetters)) {
            $this->originalLetters = $this->getOriginalLetters();
        }
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
        $title = $solrMarcSpecs['title'] ?? '';
        unset($solrMarcSpecs['title']);
        $mandatoryField = $solrMarcSpecs['mandatory-field'] ?? '';
        unset($solrMarcSpecs['mandatory-field']);
        $mandatoryFieldSet = (empty($mandatoryField));
        if (is_array($solrMarcSpecs)) {
            foreach ($solrMarcSpecs as $field => $subFieldSpecs) {
                $indexData = [];
                if (!empty($subFieldSpecs['parent'])) {
                    $tmpKey = '';
                    $tmpData = [];
                    foreach ($subFieldSpecs['parent'] as $subFieldSpec) {
                        if ($subFieldSpec[0] == 'method') {
                            $method = $subFieldSpec[1];
                            if (is_callable('parent::' . $method)) {
                                $indexValues = call_user_func([$this, 'parent::' . $method]);
                                if (is_array($indexValues)) {
                                    foreach ($indexValues as $indexKey => $value) {
                                        $tmpData[$indexKey] = ['data'=> [$value]];
                                    }
                                } else {
                                    $tmpData = $indexValues;
                                }
                            }
                        } elseif ($subFieldSpec[0] == 'name') {
                            $tmpKey = $subFieldSpec[1];
                        }
                    }
                    if (!empty($tmpData)) {
                        if (is_array($tmpData)) {
                            $indexData = [$tmpData];
                        } elseif (!empty($tmpKey)) {
                            $indexData[] = [$tmpKey => ['data' => [$tmpData]]];
                        } else {
                            $indexData[] = [['data' => [$tmpData]]];
                        }
                    }
                }
                foreach ($this->getMarcRecord()->getFields($field) as $index => $fieldObject) {
                    $data = $indexData[0] ?? [];
                    $conditionForcedValue = [];
                    if (!empty($subFieldSpecs['conditions'])) {
                        foreach ($subFieldSpecs['conditions'] as $condition) {
                            list($type, $key, $val) = $condition;
                            $val = strval($val);
                            if (substr($val, 0, 1) == '!') {
                                $val = substr($val, 1);
                                if ($type == 'indicator') {
                                    $indicator = $fieldObject->getIndicator($key);
                                    if (!empty($indicator) && ($val == '*' || preg_match('/'.$val.'/', $indicator))) {
                                        continue 2;
                                    }
                                } elseif ($type == 'field') {
                                    foreach ($fieldObject->getSubfields() as $subFieldObject) {
                                        if ($subFieldObject->getCode() == $key) {
                                            if ($val == '*' || preg_match('/'.$val.'/', $subFieldObject->getData())) {
                                                continue 3;
                                            }
                                        }
                                    }
                                }
                            } else {
                                if ($type == 'indicator') {
                                    $indicator = $fieldObject->getIndicator($key);
                                    if (!isset($indicator) || $val != '*' && !preg_match('/'.$val.'/', $indicator)) {
                                        continue 2;
                                    }
                                } elseif ($type == 'field') {
                                    $fieldExists = false;
                                    $subfieldCheckPassed = false;
                                    foreach ($fieldObject->getSubfields() as $subFieldObject) {
                                        if ($subFieldObject->getCode() == $key) {
                                            $fieldExists = true;
                                            if ($val == '*' || preg_match('/'.$val.'/', $subFieldObject->getData())) {
                                                $subfieldCheckPassed = true;
                                                $conditionForcedValue[$key] = $subFieldObject->getData();
                                                break;
                                            }
                                        }
                                    }
                                    if (!$subfieldCheckPassed || !$fieldExists) {
                                        continue 2;
                                    }
                                }
                            }
                        }
                    }
                    if (!empty($subFieldSpecs['subfields'])) {
                        $subFieldList = [];
                        foreach ($subFieldSpecs['subfields'] as $subField => $specs) {
                            $subFieldList[$subField] = [];
                            if (!empty($specs)) {
                                foreach ($specs as $spec) {
                                    if (isset($spec[0])) {
                                        if ($spec[0] == 'name') {
                                            if (!empty($subFieldList[$subField]['name'])) {
                                                $subFieldList[$subField]['name'] .= '#' . $spec[1];
                                            } else {
                                                $subFieldList[$subField]['name'] = $spec[1];
                                            }
                                        } elseif ($spec[0] == 'match') {
                                            $subFieldList[$subField]['filter'] = $spec[1];
                                            $subFieldList[$subField]['match'] = intval($spec[2]);
                                        } elseif ($spec[0] == 'replace') {
                                            if (!isset($subFieldList[$subField]['toReplace'])) {
                                                $subFieldList[$subField]['toReplace'] = [];
                                                $subFieldList[$subField]['replacement'] = [];
                                            }
                                            $subFieldList[$subField]['toReplace'][] = $spec[1];
                                            $subFieldList[$subField]['replacement'][] = $spec[2];
                                        } elseif ($spec[0] == 'function') {
                                            if (!isset($subFieldList[$subField]['function'])) {
                                                $subFieldList[$subField]['function'] = [];
                                                $subFieldList[$subField]['parameter'] = [];
                                            }
                                            $subFieldList[$subField]['function'][] = $spec[1];
                                            $subFieldList[$subField]['parameter'][] = $spec[2];
                                        }
                                    }
                                }
                            }
                        }
                        $dataIndex = 0;
                        foreach ($subFieldList as $subfield => $properties) {
                            $fieldData = [];
                            if (strpos($subfield, 'indicator') !== false) {
                                $indicator = substr($subfield, 9, 1);
                                $fieldData[] = $fieldObject->getIndicator($indicator);
                            } else {
                                foreach ($fieldObject->getSubfields() as $subFieldObject) {
                                    if ($subFieldObject->getCode() == $subfield) {
                                        if (!empty($conditionForcedValue[$subfield])) {
                                            if ($subFieldObject->getData() == $conditionForcedValue[$subfield]) {
                                                $fieldData[] = $conditionForcedValue[$subfield];
                                            }
                                        } else {
                                            $fieldData[] = $subFieldObject->getData();
                                        }
                                    }
                                }
                            }
                            if (!empty($fieldData)) {
                                foreach ($fieldData as $fieldDate) {
                                    if (isset($properties['filter']) && isset($properties['match'])) {
                                        if (preg_match('/' . $properties['filter'] . '/', $fieldDate, $matches)) {
                                            $fieldDate = $matches[$properties['match']];
                                        } else {
                                            $fieldDate = '';
                                        }
                                    }
                                    if (isset($properties['function'])) {
                                        for ($i = 0; $i < count($properties['function']); $i++) {
                                            $function = $properties['function'][$i];
                                            if (!empty($properties['parameter'][$i])) {
                                                $fieldDate = $function($fieldDate, $properties['parameter'][$i]);
                                                #$fieldDate = $function($fieldDate, MB_CASE_TITLE);
                                            } else {
                                                $fieldDate = $function($fieldDate);
                                            }
                                        }
                                    }
                                    if (isset($properties['toReplace']) && isset($properties['replacement'])) {
                                        for ($i = 0; $i < count($properties['toReplace']); $i++) {
                                            $fieldDate = preg_replace('/' . $properties['toReplace'][$i] . '/', $properties['replacement'][$i], $fieldDate);
                                        }
                                    }
                                    $fieldDate = trim($fieldDate);
                                    if (empty($fieldDate) && $fieldDate !== '0' && $fieldDate !== 0) {
                                        continue;
                                    }
                                    $tmpName = $properties['name'] ?? $dataIndex++;
                                    $names = explode('#', $tmpName);
                                    foreach ($names as $name) {
                                        if (!isset($data[$name]['data'])) {
                                            $data[$name]['data'] = [];
                                        }
                                        $data[$name]['data'][] = $fieldDate;
                                        if (empty($solrMarcSpecs['originalletters']) || $solrMarcSpecs['originalletters'] != 'no') {
                                            if (!empty($this->originalLetters[$field][$index][$subfield])) {
                                                $data[$name]['originalLetters'] = $this->originalLetters[$field][$index][$subfield];
                                            }
                                        }
                                        if ($name == $mandatoryField) {
                                            $mandatoryFieldSet = true;
                                        }
                                    }
                                }
                            }

                        }
                    }
                    if (!empty($data)) {
                        $returnData[] = $data;
                    }
                }
                if (empty($returnData)) {
                    $returnData = $indexData;
                }
            }
        }
        if (!empty($returnData)) {
            $returnData['view-method'] = $solrMarcSpecs['view-method'];
            $returnData['match-key'] = $solrMarcSpecs['match-key'];
	}
        if (!$mandatoryFieldSet) {
            return [];
        }
        return $returnData ?? [];
    }

    /**
     * Get title etc in original letters
     *
     * @return array
     */
    protected function getOriginalLetters()
    {
        $originalLetters = [];
        if ($fields = $this->getMarcRecord()->getFields('880')) {
            foreach ($fields as $field) {
                $subfields = $field->getSubfields();
                $letters = [];
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
                    $originalLetters[$index] = [$letters];
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
     * Get the multipart resource record level level of the current record.
     *
     * @return string
     */
    public function getMultipartResourceRecordLevel()
    {
        $leader = $this->getMarcRecord()->getLeader();
        $mrrLevel = strtoupper($leader[19]);

        switch ($mrrLevel) {
            case 'A': // Set
                return "Set";
            case 'B': // Part with independent title
                return "Part with independent title";
            case 'C': // Part with dependent title
                return "Part with dependent title";
            default:
                return "Unknown";
        }
    }

    /**
     * Return an XML representation of the record using the specified format.
     * Return false if the format is unsupported.
     *
     * @param string     $format     Name of format to use (corresponds with OAI-PMH
     * metadataPrefix parameter).
     * @param string     $baseUrl    Base URL of host containing VuFind (optional;
     * may be used to inject record URLs into XML when appropriate).
     * @param RecordLink $recordLink Record link helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return mixed         XML, or false if format unsupported.
     */
    public function getXML($format, $baseUrl = null, $recordLink = null)
    {
        // Special case for MARC:
        if ($format == 'marc21') {
            $xml = $this->getMarcRecord()->toXML();
            $xml = str_replace(
                [chr(27), chr(28), chr(29), chr(30), chr(31)], ' ', $xml
            );
            $xml = simplexml_load_string($xml);
            if (!$xml || !isset($xml->record)) {
                return false;
            }

            // Set up proper namespacing and extract just the <record> tag:
            $xml->record->addAttribute('xmlns', "http://www.loc.gov/MARC21/slim");
            $xml->record->addAttribute(
                'xsi:schemaLocation',
                'http://www.loc.gov/MARC21/slim ' .
                'http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd',
                'http://www.w3.org/2001/XMLSchema-instance'
            );
            $xml->record->addAttribute('type', 'Bibliographic');
            return $xml->record->asXML();
        }

        // Try the parent method:
        return parent::getXML($format, $baseUrl, $recordLink);
    }

    /**
     * Get the OpenURL parameters to represent this record (useful for the
     * title attribute of a COinS span tag).
     *
     * @param bool $overrideSupportsOpenUrl Flag to override checking
     * supportsOpenUrl() (default is false)
     *
     * @return string OpenURL parameters.
     */
    public function getOpenUrl($overrideSupportsOpenUrl = false)
    {
        return parent::getOpenUrl($overrideSupportsOpenUrl);
    }

    /**
     * Get OpenURL parameters for an article.
     *
     * @return array
     */
    protected function getArticleOpenUrlParams()
    {
        $params = parent::getArticleOpenUrlParams();
        $pages = $this->getMarcData('Pages');
        if (isset($pagesData[0]['pages']['data'][0])) {
            $pages = $pagesData[0]['pages']['data'][0];
            if (strpos($pages, '-') !== false) { 
                list($spage, $epage) = explode('-', $pages[0]['pages']['data'][0]);
                $params['rft.epage'] = $epage;
            }
        }
        return $params;
    }

}
