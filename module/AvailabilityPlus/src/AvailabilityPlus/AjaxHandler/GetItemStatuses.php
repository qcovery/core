<?php

namespace AvailabilityPlus\AjaxHandler;

use VuFind\Record\Loader;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use Zend\Config\Config;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\View\Renderer\RendererInterface;
use VuFind\Resolver\Driver\PluginManager as ResolverManager;
use VuFind\Resolver\Connection;

/**
 * "Get Item Status" AJAX handler
 *
 * This is responsible for printing the holdings information for a
 * collection of records in JSON format.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Kristof Kessler <mail@kristofkessler.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetItemStatuses extends \VuFind\AjaxHandler\GetItemStatuses implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    protected $recordLoader;

    protected $config;

    protected $resolverConfig;

    protected $checks;

    protected $checkRoute;

    protected $source;

    protected $driver;

    protected $current_mode;

    protected $renderer;

    protected $default_template;

    protected $debug;

    protected $list;

    protected $id;

    protected $mediatype;

    protected $language;

    /**
     * Resolver driver plugin manager
     *
     * @var ResolverManager
     */
    protected $resolverManager;

    /**
     * Constructor
     *
     * @param Loader            $loader    For loading record data via driver
     * @param Config            $config    Top-level configuration
     * @param RendererInterface $renderer  View renderer
     */
    public function __construct(Loader $loader, Config $config, RendererInterface $renderer, ResolverManager $rm, Config $resolverConfig) {
        $this->recordLoader = $loader;
        $this->config = $config->toArray();
        $this->resolverConfig = $resolverConfig->toArray();
        $this->checks = $this->config['RecordView'];
        $this->renderer = $renderer;
        $this->default_template = 'ajax/default.phtml';
        $this->resolverManager = $rm;
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
        $this->list = ($params->fromPost('list', $params->fromQuery('list', 'false')) === 'true') ? 1 : 0;
        $this->debug = $params->fromPost('debug', $params->fromQuery('debug', ''));
        $this->language = 'en';
        if(!empty(params->fromPost('debug', $params->fromQuery('language', '')))) $this->language = params->fromPost('debug', $params->fromQuery('language', ''));
        if (!empty($ids) && !empty($this->source)) {
            foreach ($ids as $id) {
                $this->id = $id;
                $this->checks = [];
                $check_mode = 'continue';
                try {
                    $this->driver = $this->recordLoader->load($id, $this->source);
                    $mediatype = $params->fromPost('mediatype', $params->fromQuery('mediatype', ''));
                    if(empty($mediatype)) {
                        $formats = $this->driver->getFormats();
                        if (isset($formats[0])) {
                            $mediatype = $formats[0];
                        }
                    }
                    $this->mediatype = $mediatype;
                    $this->setChecks($mediatype);
                    $this->driver->addSolrMarcYaml($this->config['General']['availabilityplus_yaml'], false);
                    $responses = [];
                    $response = [];
                    foreach($this->checks as $check => $this->current_mode) {
                        if(in_array($check_mode,array('continue','break_next','break_on_first_next')) || in_array($this->current_mode,array('always', 'always_break_on_first'))) {
                            $results = $this->performAvailabilityCheck($check);
                            foreach($results as $result) {
                                if(!empty($result)) {
                                    if(!empty($result['html'])) $check_mode = $this->current_mode;
                                    if($this->debug) {
                                        $result['html'] = $this->applyTemplate('ajax/debug.phtml', [ 'debug' => $result ]);
                                    }
                                    $result['id'] = $id;
                                    $response[] = $result;
                                }
                            }
                        }
                        if(in_array($check_mode,array('break_next','break_on_first_next'))) $check_mode = $this->current_mode;
                    }
                    $response['id'] = $id;
                    $response['mediatype'] = $mediatype;
                    $response['checkRoute'] = $this->checkRoute;
                    $response['checks'] = $this->checks;
                    $responses[] = $response;
                } catch (\Exception $e) {
                    $result['check'] = 'Exception';
                    $result['error'] = $e;
                    if($this->debug) {
                        $result['html'] = $this->applyTemplate('ajax/debug.phtml', [ 'debug' => $result ]);
                    }
                    $response[] = $result;
                    $response['id'] = $id;
                    $mediatype = 'cannot be determined due to exception';
                    $this->checkRoute = 'cannot be determined due to exception';
                    $responses[] = $response;
                }

            }
        }

        if($this->debug) {
            $debug_info = [];
            $debug_info['check'] = 'Debug Info';
            $debug_info['mediatype'] = $mediatype;
            $debug_info['AvailabilityPlusConfig'] = 'availabilityplus.ini';
            $debug_info['SolrMarcYml'] = 'solrmarc.yml';
            $debug_info['SolrMarcYmlAvailabilityPlus'] = $this->config['General']['availabilityplus_yaml'];
            $debug_info['checkRoute_in_AvailabilityPlusConfig'] = $this->checkRoute;
            $debug_info['checks'] = $this->checks;
            $responses[0][0]['html'] = $this->applyTemplate('ajax/debug.phtml', [ 'debug' => $debug_info ]).$responses[0][0]['html'];
        }

        return $this->formatResponse(['statuses' => $responses]);
    }

    private function setChecks($mediatype = '') {
        $list = $this->list;
        $mediatype = str_replace(array(' ', '+'),array('',''), $mediatype);
        $checks = 'RecordView';
        if($list) $checks = 'ResultList';
        if(!empty($this->config[$this->source.$checks.'-'.$mediatype])) {
            $this->checks = $this->config[$this->source.$checks.'-'.$mediatype];
            $this->checkRoute = $this->source.$checks.'-'.$mediatype;
        } else if(!empty($this->config[$checks.'-'.$mediatype])) {
            $this->checks = $this->config[$checks.'-'.$mediatype];
            $this->checkRoute = $checks.'-'.$mediatype;
        } else if(!empty($this->config[$this->source.$checks])) {
            $this->checks = $this->config[$this->source.$checks];
            $this->checkRoute =$this->source.$checks;
        } else {
            $this->checks = $this->config[$checks];
            $this->checkRoute = $checks;
        }
    }

    /**
     * Determines which check to run, based on keyword in configuration. Determination in this order depending on match between name and logic:
     * 1) function available with name in this class
     * 2) DAIA and other resolvers
     * 3) MarcKey defined in availabilityplus_yaml
     * 4) MarcCategory defined in availabilityplus_yaml
     *
     * @check name of check to run
     *
     * @return array [response data for check]
     */
    private function performAvailabilityCheck($check) {

        if(method_exists($this, $check)){
            $responses = $this->{$check}();
        } elseif(in_array($check,$this->driver->getSolrMarcKeys('resolver'))) {
            $responses = $this->getResolverResponse($check);
        } elseif (!empty($this->driver->getSolrMarcSpecs($check))) {
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
        array_unique($solrMarcKeys);
        $check_type = 'MARC';
        $urls = [];
        $break = false;
        foreach ($solrMarcKeys as $solrMarcKey) {
            $data = $this->driver->getMarcData($solrMarcKey);
            $level = $this->getLevel($data[0], $check, $solrMarcKey);
            $label = $this->getLabel($data[0], $check);
            if(!empty($data) && $this->checkConditions($data)) {
                $template = $this->getTemplate($data);
                foreach ($data as $date) {
                    if (!empty($date['url']['data'][0])) {
                        foreach ($date['url']['data'] as $url) {
                            if(!in_array($url, $urls)) {
                                $level = $this->getLevel($date, $level, $solrMarcKey);
                                $label = $this->getLabel($date, $label);
                                $urls[] = $url;
                                $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $url, true, $check_type);
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
                    $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data, '', true, $check_type);
                    $response['html'] = $this->applyTemplate($template, $response);
                    $responses[] = $response;
                    if($this->current_mode == 'break_on_first') {
                        $break = true;
                        break;
                    }
                }
            } else {
                $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $url, false, $check_type);
                $responses[] = $response;
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

    private function getLevel($date, $level, $solrMarcKey) {
        if($level != $solrMarcKey) $level = $level.' '.$solrMarcKey;
            if(!empty($date['level']['data'][0])) $level = $date['level']['data'][0];
        return $level;
    }

    private function getLabel($date, $label) {
        if(!empty($date['label']['data'][0])) $label = $date['label']['data'][0];
        return $label;
    }

    private function generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $url = '', $status_bool = false, $check_type){
        if($status_bool) {
            $status['level'] = 'successful_check';
            $status['label'] = 'Check found a match!';
        } else {
            $status['level'] = 'unsuccessful_check';
            $status['label'] = 'Check did not find a match!';
        }
        $response = [
            'id' => $this->id,
            'mediatype' => $this->mediatype,
            'check' => $check,
            'check_type' => $check_type,
            'SolrMarcKey' => $solrMarcKey,
            'SolrMarcSpecs' => $this->driver->getSolrMarcSpecs($solrMarcKey),
            'SolrMarcData' => $this->driver->getMarcData($solrMarcKey),
            'status' => $status,
            'mode' => $this->current_mode,
            'list' => $this->list,
            'url' => $url,
            'level' => $level,
            'label' => $label,
            'label_translated' => $this->translate($label),
            'template' => $template,
            'data' => $data,
            'SolrMarcSupportData' => $this->driver->getMarcData('SupportData')
        ];
        return array_filter($response);
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
        $check = 'checkParentWorkILNSolr';
        $check_type = 'function';
        $template = 'ajax/link-parent.phtml';
        $responses = [];
        $parentData = $this->driver->getMarcData('ArticleParentId');
        $response = $this->generateResponse($check, 'ArticleParentId', '', '', $template, '', '', false, $check_type);
        foreach ($parentData as $parentDate) {
            if (!empty(($parentDate['id']['data'][0]))) {
                $parentId = $parentDate['id']['data'][0];
                break;
            }
        }
        $response['parentId'] = $parentId;

        if (!empty($parentId)) {
            try {
                $parentDriver = $this->recordLoader->load($parentId, 'Solr');
                $ilnMarcSpecs = $parentDriver->getSolrMarcSpecs('ILN');
                $ilnMatch = $parentDriver->getMarcData('ILN');
                if(empty($ilnMatch)) {
                    $ilnMarcSpecs = $this->driver->getSolrMarcSpecs('ILN');
                    $ilnMatch = $this->driver->getMarcData('ILN');
                }

                if (!empty($ilnMatch[0]['iln']['data'][0])) {
                    $url = '/vufind/Record/' . $parentId;
                }
            } catch (\Exception $e) {
                $url = '';
            }
        }
        if (!empty($url)) {
            $level = 'ParentWorkILNSolr';
            $label = 'Go to parent work (local holding)';
            $response = $this->generateResponse($check, 'ArticleParentId', $level, $label, $template, $parentData, $url, true, $check_type);
            $response['html'] = $this->renderer->render($template , $response);
        }
        $response['ilnMarcSpecs'] = $ilnMarcSpecs;
        $response['ilnMatch'] = $ilnMatch;
        $responses[] = $response;
        return $responses;
    }

    private function getResolverResponse($resolver) {
        $check_type = 'Resolver';
        $resolverType = $resolver;
        if (!$this->resolverManager->has($resolverType)) {
            return $this->formatResponse(
                $this->translate("Could not load driver for $resolverType"),
                self::STATUS_HTTP_ERROR
            );
        }
        $resolverHandler = new Connection($this->resolverManager->get($resolverType));
        $resolverHandler->setLanguage($this->language);
        $marc_data = $this->driver->getMarcData($resolver);
        $params = $this->prepareResolverParams($marc_data);
        $resolver_url = $resolverHandler->getResolverUrl($params);
        $template = $this->getTemplate($marc_data);
        if(!empty($resolver_url) && !empty($marc_data)) {
            try {
                $resolver_data = $resolverHandler->fetchLinks($params);
                $response = $this->generateResponse($resolver, $resolver, $resolver, $resolver, $template, $resolver_data['parsed_data'], $resolver_url, true, $check_type);
                $response['resolverConfig'] = $this->resolverConfig[$resolver];
                $response['html'] = $this->applyTemplate($template, $response);
                if(empty($response['html'])) {
                    $response['status']['level'] = 'unsuccessful_check';
                    $response['status']['label'] = 'Check did not find a match!';
                }
                $response['resolver_data'] = $resolver_data['data'];
                $response['resolver_rule_file'] = $resolverHandler->getRulesFile();
            } catch (\Exception $e) {
                $template = 'ajax/default.phtml';
                $response = $this->generateResponse($resolver, $resolver, 'resolver_error', 'resolver_error', $template, $resolver_data['parsed_data'], $resolver_url, false, 'Resolver-EXCEPTION');
                $response['status']['label'] = 'EXCEPTION occured during processing';
                $response['url'] = '';
                $response['html'] = $this->applyTemplate($template, $response);
                $response['resolver_rule_file'] = $resolverHandler->getRulesFile();
                $response = array('error' => $e) + $response;
                $responses[] = $response;
                return $responses;
            }

        } else {
            $response = $this->generateResponse($resolver, $resolver, $resolver, $resolver, $template, '', $resolver_url, false, $check_type);
        }

        $responses[] = $response;

        return $responses;
    }

    /**
     * Custom method to check for a MultiVolumeWork (indicated by MARC Leader Positon 19 = A
     *
     * @return array [response data (arrays)]
     */
    private function checkMultiVolumeWork() {
        $check = 'MultiVolumeWork';
        $check_type = 'function';
        $template = 'ajax/default.phtml';
        $responses = [];
        if($this->driver->getMultipartResourceRecordLevel() == "Set") {
            $level = 'MultiVolumeWork';
            $label = 'MultiVolumeWork';
            if($this->list) {
                if($this->source == "Search2") {
                    $url = "/vufind/Search2Record/".$this->id;
                } else {
                    $url = "/vufind/Record/".$this->id;
                }
            }
            $response = $this->generateResponse($check, '', $level, $label, $template, $parentData, $url, true, $check_type);
            $response['html'] = $this->renderer->render($template, $response);
            $responses[] = $response;
        }
        return $responses;
    }

    private function prepareResolverParams($resolverData) {
        $used_params = [];
        $params = '';
        if(!empty($resolverData)) {
            if(is_array($resolverData)) {
                foreach ($resolverData as $resolverDate) {
                    if (is_array($resolverDate)) {
                        foreach ($resolverDate as $key => $value) {
                            if (!in_array($key, $used_params)) {
                                if (empty($params)) {
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
        }
        return $params;
    }
}