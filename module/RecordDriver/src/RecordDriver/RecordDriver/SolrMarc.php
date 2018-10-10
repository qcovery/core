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
     * Configuration (yaml)
     *
     * @var string
     */
    protected $solrMarcYaml = 'solrmarc.yaml';

    /**
     * Specifications to use
     *
     * @var array
     */
    protected $solrMarcSpecs;

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
     * @return array
     */
    public function getSolrMarcKeys($category = '', $others = true)
    {
        if (empty($this->solrMarcSpecs)) {
            $this->parseSolrMarcSpecs();
        }
        $specKeys = array_keys($this->solrMarcSpecs);
        if (empty($category)) {
            if ($others) {
                return $specKeys;
            } else {
                $solrMarcKeys = $this->solrMarcKeys;
                unset($solrMarcKeys['other']);
                $keys = array_reduce($solrMarcKeys, 'array_merge', []);
                return array_intersect($keys, $specKeys);
            }
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
    private function parseSolrMarcSpecs()
    {
        $specsReader = new SearchSpecsReader();
        $rawSolrMarcSpecs = $specsReader->get($this->solrMarcYaml);
        $solrMarcSpecs = [];
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
                            $indexData[] = [$tmpKey => $value];
                        }
                    } else {
                        $indexData[] = $tmpData;
                    }
                }
            }
            foreach ($this->getMarcRecord()->getFields($field) as $index => $fieldObject) {
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
                    $subFieldList = [];
                    foreach ($subFieldSpecs['subfields'] as $subField => $specs) {
                        $subFieldList[$subField] = [];
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
                            foreach ($fieldData as $dataIndex => $fieldDate) {
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
                                $name = $properties['name'] ?? $dataIndex;
                                $data[$name] = ['data'=>trim($fieldDate)];
                                if (empty($solrMarcSpecs['originalletters']) || $solrMarcSpecs['originalletters'] != 'no') {
                                    if (!empty($this->originalLetters[$field][$index][$subfield])) {
                                        $data[$name]['originalLetters'] = $this->originalLetters[$field][$index][$subfield];
                                    }
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
        return $returnData;
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
}
