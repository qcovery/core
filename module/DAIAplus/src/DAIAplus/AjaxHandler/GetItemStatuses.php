<?php
/**
 * "Get Item Status" AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
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
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace DAIAplus\AjaxHandler;

use VuFind\Record\Loader;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use Zend\Config\Config;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\View\Renderer\RendererInterface;
use VuFind\Crypt\HMAC;
use VuFind\Resolver\Driver\PluginManager as ResolverManager;

/**
 * "Get Item Status" AJAX handler
 *
 * This is responsible for printing the holdings information for a
 * collection of records in JSON format.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetItemStatuses extends \VuFind\AjaxHandler\GetItemStatuses implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    protected $recordLoader;

    protected $config;

    protected $configResolver;

    protected $checks;

    protected $source;

    protected $driver;

    protected $current_mode;

    protected $renderer;

    protected $default_template;

    protected $hmac;

    /**
     * Resolver driver plugin manager
     *
     * @var ResolverManager
     */
    protected $pluginManager;

    /**
     * Constructor
     *
     * @param Loader            $loader    For loading record data via driver
     * @param Config            $config    Top-level configuration
     * @param RendererInterface $renderer  View renderer
     */
    public function __construct(Loader $loader, Config $config, Config $configResolver, RendererInterface $renderer, HMAC $hmac) {
        $this->recordLoader = $loader;
        $this->config = $config->toArray();
        $this->configResolver = $configResolver->toArray();
        $this->checks = $this->config['RecordView'];
        $this->renderer = $renderer;
        $this->default_template = 'ajax/default.phtml';
        $this->hmac = $hmac;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $responses = [];
        $ids = $params->fromPost('id', $params->fromQuery('id', ''));
        $this->source = $params->fromPost('source', $params->fromQuery('source', ''));

        $list = ($params->fromPost('list', $params->fromQuery('list', 'false')) === 'true') ? 1 : 0;
        $mediatype = $params->fromPost('mediatype', $params->fromQuery('mediatype', ''));
        $this->setChecks($list, $mediatype);

        if (!empty($ids) && !empty($this->source)) {
            foreach ($ids as $id) {
                $check_mode = 'continue';
                $this->driver = $this->recordLoader->load($id, $this->source);
                $this->driver->addSolrMarcYaml($this->config['General']['availabilityplus_yaml'], false);
                $responses = [];
                $response = [];
                foreach($this->checks as $check => $this->current_mode) {
                    if(in_array($check_mode,array('continue')) || in_array($this->current_mode,array('always'))) {
                        $results = $this->performAvailabilityCheck($check);
                        foreach($results as $result) {
                            if(!empty($result)) {
                                $response[] = $result;
                                if(!empty($result['html'])) $check_mode = $this->current_mode;
                            }
                        }
                    }
                }
                $response['id'] = $id;
                $responses[] = $response;
            }
        }
        return $this->formatResponse(['statuses' => $responses]);
    }

    private function setChecks($list, $mediatype = '') {
        $mediatype = str_replace(array(' ', '+'),array('',''), $mediatype);
        $checks = 'RecordView';
        if($list) $checks = 'ResultList';
        if(!empty($this->config[$this->source.$checks.'-'.$mediatype])) {
            $this->checks = $this->config[$this->source.$checks.'-'.$mediatype];
        } else if(!empty($this->config[$checks.'-'.$mediatype])) {
            $this->checks = $this->config[$checks.'-'.$mediatype];
        } else if(!empty($this->config[$this->source.$checks])) {
            $this->checks = $this->config[$this->source.$checks];
        } else {
            $this->checks = $this->config[$checks];
        }
    }

    /**
     * Determines which check to run, based on keyword in configuration. Determination in this order depending on match between name and logic:
     * 1) function available with name in this class
     * 2) DAIA and other resolver
     * 3) MarcKey defined in availabilityplus_yaml
     * 4) MarcCategory defined in availabilityplus_yaml
     * TODO: Add checks for resolver and DAIA
     *
     * @check name of check to run
     *
     * @return array [response data for check]
     */
    //TODO: Add checks for resolver and DAIA
    private function performAvailabilityCheck($check) {

        if(method_exists($this, $check)){
            $responses = $this->{$check}();
        } elseif(in_array($check,$this->driver->getSolrMarcKeys('resolver'))) {
            $responses = $this->getResolverResponse($check);
        } elseif (!empty($this->driver->getMarcData($check))) {
            $responses = $this->checkSolrMarcData(array($check), $check);
        } elseif (!empty($this->driver->getSolrMarcKeys($check))) {
            $responses = $this->checkSolrMarcData($this->driver->getSolrMarcKeys($check), $check);
        } else {
            $response['check'] = $check;
            $response['message'] = 'no MARC configuration or function for check exists';
            $responses[] = $response;
        }

        return $responses;
    }

    /**
     * Perform check based on provided MarcKeys
     *
     * @solrMarcKeys array of MarcKeys to check
     * @check name of check in availabilityplus_yaml
     *
     * @return array [response data (arrays)]
     */
    private function checkSolrMarcData($solrMarcKeys, $check) {
        sort($solrMarcKeys);
        $responses = [];
        $urls = [];
        $break = false;
        foreach ($solrMarcKeys as $solrMarcKey) {
            $data = $this->driver->getMarcData($solrMarcKey);
            if(!empty($data) && $this->checkConditions($data)) {
                $template = $this->getTemplate($data);
                $level = $this->getLevel($data, $check, $solrMarcKey);
                $label = $this->getLabel($data, $check);
                foreach ($data as $date) {
                    if (!empty($date['url']['data'][0])) {
                        foreach ($date['url']['data'] as $url) {
                            if(!in_array($url, $urls)) {
                                $urls[] = $url;
                                $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $url);
                                $response['html'] = $this->applyTemplate($template, $response);
                                $responses[] = $response;
                                if($this->current_mode == 'break_on_first') {
                                    $break = true;
                                    break;
                                }
                            }
                        }
                    }
                    if($break) break;
                }

                if(empty($urls)) {
                    $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data);
                    $response['html'] = $this->applyTemplate($template, $response);
                    $responses[] = $response;
                    if($this->current_mode == 'break_on_first') {
                        $break = true;
                        break;
                    }
                }
            }
            if($break) break;
        }
        return $responses;
    }

    private function checkConditions($data){
        $check = true;
        $requirednumberofconditions = 0;
        $numberofconditions = 0;

        foreach($data as $date) {
            if(!empty($date['requirednumberofconditions']['data'][0])) {
                $requirednumberofconditions = $date['requirednumberofconditions']['data'][0];
            }
        }

        foreach($data as $date) {
            if(!empty($date['condition_true']['data'][0])) {
                if($date['condition_true']['data'][0] != 'true') $check = false;
                $numberofconditions += 1;
            } elseif(!empty($date['condition_false']['data'][0])) {
                if($date['condition_false']['data'][0] != 'false') $check = false;
                $numberofconditions += 1;
            }
        }

        if($requirednumberofconditions != $numberofconditions) $check = false;

        return $check;
    }

    private function getLevel($data, $level, $solrMarcKey) {
        if($level != $solrMarcKey) $level = $level.' '.$solrMarcKey;
        foreach ($data as $date) {
            if(!empty($date['level']['data'][0])) $level = $date['level']['data'][0];
        }
        return $level;
    }

    private function getLabel($data, $label) {
        foreach ($data as $date) {
            if(!empty($date['label']['data'][0])) $label = $date['label']['data'][0];
        }
        return $label;
    }

    private function generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $url = ''){
        $response = [
            'mode' => $this->current_mode,
            'check' => $check,
            'SolrMarcKey' => $solrMarcKey,
            'url' => $url,
            'level' => $level,
            'label' => $label,
            'label_translated' => $this->translate($label),
            'template' => $template,
            'data' => $data
        ];
        return $response;
    }

    /**
     * Support method to determine if a view-method, i.e. a name of template file has been defined, if not then the default_template is used
     *
     * @data data provided via parsing of availabilityplus_yaml
     *
     * @return string
     */
    private function getTemplate($data) {
        $template = $this->default_template;
        if(!empty($data['view-method'])) $template = $data['view-method'];
        return $template;
    }

    /**
     * Support method to apply template
     *
     * @template name of template file
     * @response response data
     *
     * @return string (html code)
     */
    private function applyTemplate($template, $response) {
        return $this->renderer->render($template, $response);
    }

    /**
     * Custom method to check for a parent work that is a holding of the library
     *
     * @return array [response data (arrays)]
     */
    private function checkParentWorkILNSolr() {
        $responses = [];
        $parentData = $this->driver->getMarcData('ArticleParentId');
        $response = [
            'check' => 'function checkParentWork',
            'parentData' => $parentData
        ];
        foreach ($parentData as $parentDate) {
            if (!empty(($parentDate['id']['data'][0]))) {
                $parentId = $parentDate['id']['data'][0];
                break;
            }
        }
        if (!empty($parentId)) {
            $parentDriver = $this->recordLoader->load($parentId, 'Solr');
            $ilnMatch = $parentDriver->getMarcData('ILN');
            if (!empty($ilnMatch[0]['iln']['data'][0])) {
                $url = '/vufind/Record/' . $parentId;
            }
        }
        if (!empty($url)) {
            $level = 'ParentWorkILNSolr';
            $label = 'Go to parent work (local holding)';
            $response = [
                'check' => 'function checkParentWork',
                'url' => $url,
                'level' => $level,
                'label' => $label,
                'label_translated' => $this->translate($label),
                'parentData' => $parentData
            ];
            $response['html'] = $this->renderer->render('ajax/link-internal.phtml', $response);
        }

        $responses[] = $response;
        return $responses;
    }

    private function getResolverResponse($resolver) {
        $data = $this->driver->getMarcData($resolver);
        $resolver_url = $this->prepareUrl($resolver);
        $template = $this->getTemplate($data);
        $response = [
            'mode' => $this->current_mode,
            'check' => $resolver,
            'url' => $resolver_url,
            'level' => $resolver,
            'label' => $resolver,
            'label_translated' => $this->translate($resolver),
            'resolver_url' => $resolver_url,
            'marc_data' => $data,
        ];
        $response['data'] = '';
        if(!empty($data)) {
            $response['itemStatusesHandler'] = $this;
            $response['data'] = $this->makeRequest($resolver_url);
            $response['html'] = $this->applyTemplate($template, $response);
        }

        $responses[] = $response;

        return $responses;
    }

    private function prepareUrl($resolver) {
        $resolverData = $this->driver->getMarcData($resolver);
        if(!empty($resolverData) && !empty($this->config['ResolverBaseURL'][$resolver])) {
            $baseUrl = $this->config['ResolverBaseURL'][$resolver];
            $used_params = [];
            $params = '';

            if (is_array($resolverData)) {
                foreach ($resolverData as $resolverDate) {
                    if (is_array($resolverDate)) {
                        foreach ($resolverDate as $key => $value) {
                            if(!in_array($key, $used_params)) {
                                if(empty($params)) {
                                    $params .= '?' . $key . '=' . urlencode($value['data'][0]);
                                } else {
                                    $params .= '&' . $key . '=' . urlencode($value['data'][0]);
                                }
                                $used_params[] = $key;
                            }
                        }
                    }
                }
            }

            if(!empty($this->config['ResolverExtraParams'][$resolver])) {

                // Specific for Journals Online & Print
                if (strpos($resolver, 'JournalsOnlinePrint') !== false) {
                    if(strpos($this->config['ResolverExtraParams'][$resolver], "&pid=client_ip=dynamic") !== false) {
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $params .= str_replace("&pid=client_ip=dynamic","&pid=client_ip=".$ip,$this->config['ResolverExtraParams'][$resolver]);
                    } else {
                        $params .= $this->config['ResolverExtraParams'][$resolver];
                    }
                } else {
                    $params .= $this->config['ResolverExtraParams'][$resolver];
                }
            }

            return $baseUrl.$params;
        }
        return '';
    }

    private function makeRequest($url) {
        $req = curl_init();
        curl_setopt($req, CURLOPT_URL, $url);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($req, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($req);
        curl_close($req);
        return $result;
    }

    public function generateDAIAOrderLink() {
        //TODO: see here https://github.com/qcovery/core/blob/develop-5/module/DAIAplus/src/DAIAplus/AjaxHandler/GetItemStatuses.php#L180
        $HMACKeys = "id:item_id:doc_id";
        return 'test';
    }
}

