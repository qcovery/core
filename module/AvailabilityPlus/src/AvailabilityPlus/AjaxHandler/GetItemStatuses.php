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

    protected $checks;

    protected $checkRoute;

    protected $source;

    protected $driver;

    protected $current_mode;

    protected $renderer;

    protected $default_template;

    protected $debug;

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
    public function __construct(Loader $loader, Config $config, RendererInterface $renderer, ResolverManager $rm) {
        $this->recordLoader = $loader;
        $this->config = $config->toArray();
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
        $list = ($params->fromPost('list', $params->fromQuery('list', 'false')) === 'true') ? 1 : 0;
        $this->debug = $params->fromPost('debug', $params->fromQuery('debug', ''));
        if (!empty($ids) && !empty($this->source)) {
            foreach ($ids as $id) {
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
                    $this->setChecks($list, $mediatype);
                    $this->driver->addSolrMarcYaml($this->config['General']['availabilityplus_yaml'], false);
                    $responses = [];
                    $response = [];
                    foreach($this->checks as $check => $this->current_mode) {
                        if(in_array($check_mode,array('continue')) || in_array($this->current_mode,array('always'))) {
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
            $debug_info['checkRoute'] = $this->checkRoute;
            $debug_info['checks'] = $this->checks;
            $responses[0][0]['html'] = $this->applyTemplate('ajax/debug.phtml', [ 'debug' => $debug_info ]).$responses[0][0]['html'];
        }

        return $this->formatResponse(['statuses' => $responses]);
    }

    private function setChecks($list, $mediatype = '') {
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
                                $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $url, true);
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
                    $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data, '', true);
                    $response['html'] = $this->applyTemplate($template, $response);
                    $responses[] = $response;
                    if($this->current_mode == 'break_on_first') {
                        $break = true;
                        break;
                    }
                }
            } else {
                $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $url);
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

    private function generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $url = '', $status = false){
        if($status) {
            $status['level'] = 'successful_check';
            $status['label'] = 'Check found a match!';
        } else {
            $status['level'] = 'unsuccessful_check';
            $status['label'] = 'Check did not find a match!';
        }
        $response = [
            'check' => $check,
            'SolrMarcKey' => $solrMarcKey,
            'status' => $status,
            'mode' => $this->current_mode,
            'url' => $url,
            'level' => $level,
            'label' => $label,
            'label_translated' => $this->translate($label),
            'template' => $template,
            'data' => $data
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
        $check = 'function checkParentWork';
        $template = 'ajax/link-internal.phtml';
        $responses = [];
        $parentData = $this->driver->getMarcData('ArticleParentId');
        $response = $this->generateResponse($check, '', '', '', $template, '', '', false);
        /*$response = [
            'check' => 'function checkParentWork',
            'parentData' => $parentData
        ]; */
        foreach ($parentData as $parentDate) {
            if (!empty(($parentDate['id']['data'][0]))) {
                $parentId = $parentDate['id']['data'][0];
                break;
            }
        }
        if (!empty($parentId)) {
            try {
                $parentDriver = $this->recordLoader->load($parentId, 'Solr');
                $ilnMatch = $parentDriver->getMarcData('ILN');
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
            /*$response = [
                'check' => 'function checkParentWork',
                'url' => $url,
                'level' => $level,
                'label' => $label,
                'label_translated' => $this->translate($label),
                'parentData' => $parentData
            ];*/
            $response = $this->generateResponse($check, '', $level, $label, $template, $parentData, $url, true);
            $response['html'] = $this->renderer->render('ajax/link-internal.phtml', $response);
        }
        $responses[] = $response;
        return $responses;
    }

    private function getResolverResponse($resolver) {
        $resolverType = $resolver;
        if (!$this->resolverManager->has($resolverType)) {
            return $this->formatResponse(
                $this->translate("Could not load driver for $resolverType"),
                self::STATUS_HTTP_ERROR
            );
        }
        $resolverHandler = new Connection($this->resolverManager->get($resolverType));
        $marc_data = $this->driver->getMarcData($resolver);
        $params = $this->prepareResolverParams($marc_data);
        $resolver_url = $resolverHandler->getResolverUrl($params);
        $template = $this->getTemplate($marc_data);
        $response = [
            'mode' => $this->current_mode,
            'check' => $resolver,
            'url' => $resolver_url,
            'level' => $resolver,
            'label' => $resolver,
            'label_translated' => $this->translate($resolver),
            'marc_data' => $marc_data,
            'params' => $params
        ];
        $response['data'] = '';
        if(!empty($resolver_url) && !empty($marc_data)) {
            $resolver_data = $resolverHandler->fetchLinks($params);
            $response = $this->generateResponse($resolver, '', $resolver, $resolver, $template, $resolver_data['parsed_data'], $resolver_url, true);
            $response['marc_data'] = $marc_data;
            //$response['data'] = $resolver_data['parsed_data'];
            $response['resolver_data'] = $resolver_data['data'];
            $response['resolver_options'] = $resolverHandler->getRulesFile();
            $response['html'] = $this->applyTemplate($template, $response);
        } else {
            $response = $this->generateResponse($resolver, '', $resolver, $resolver, $template, '', $resolver_url, false);
        }

        $responses[] = $response;

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