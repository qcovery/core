<?php
namespace AvailabilityPlus\AjaxHandler;

use VuFind\Record\Loader;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use Laminas\Config\Config;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Resolver\Driver\PluginManager as ResolverManager;
use VuFind\Resolver\Connection;
use Laminas\Mvc\Controller\Plugin\Url;

/**
 * 'Get Item Status' AJAX handler
 *
 * This is responsible for printing the holdings information for a
 * collection of records in JSON format.
 *
 * @category VuFind
 * @package  AJAX
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetItemStatuses extends \VuFind\AjaxHandler\GetItemStatuses implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    protected $checks;

    protected $checkRoute;

    protected $source;

    protected $driver;

    protected $current_mode;

    protected $default_template;

    protected $debug;

    protected $list;

    protected $id;

    protected $mediatype;

    protected $language;

    /**
     * Constructor
     *
     * @param Loader            $loader    For loading record data via driver
     * @param Config            $config    Top-level configuration
     * @param RendererInterface $renderer  View renderer
     */
    public function __construct(
        protected Loader $recordLoader,
        protected Config $config,
        protected RendererInterface $renderer,
        protected ResolverManager $pluginManager,
        protected Config $resolverConfig,
        protected Url $urlHelper
    ) {
        $this->checks = $this->config['RecordView'];
        $this->default_template = 'ajax/default.phtml';
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params) {
        $responses = [];
        $id = $params->fromPost('id', $params->fromQuery('id', ''));
        $solrData = $params->fromPost('full', $params->fromQuery('full', ''));
        $solrData = json_decode(base64_decode($solrData), true);

        $this->source = $params->fromPost('source', $params->fromQuery('source', ''));
        $this->list = ($params->fromPost('list', $params->fromQuery('list', 'false')) === 'true') ? 1 : 0;
        $this->debug = $params->fromPost('debug', $params->fromQuery('debug', ''));
        $this->testcase = ($params->fromPost('testcase', $params->fromQuery('testcase', '')) === 'true') ? true : false;
        $this->language = $params->fromPost('language', $params->fromQuery('language', 'en'));

        if (!empty($id) && !empty($this->source)) {
            $this->id = $id;
            $this->checks = [];
            $check_mode = 'continue';

            try {
                // do not make another solr request except for testcases
                if (!empty($solrData)) {
                    $this->driver = $this->recordLoader->load($id, $this->source, false, null, true, $solrData);
                } else {
                    $this->driver = $this->recordLoader->load($id, $this->source);
                }

                $mediatype = $params->fromPost('mediatype', $params->fromQuery('mediatype', ''));
                if (empty($mediatype)) {
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
                    if (
                        in_array($check_mode,array('continue','break_next','break_on_first_next'))
                        || in_array($this->current_mode,array('always', 'always_break_on_first'))
                    ) {
                        $results = $this->performAvailabilityCheck($check);

                        foreach ($results as $result) {
                            if (!empty($result)) {
                                if (!empty($result['html'])) $check_mode = $this->current_mode;
                                if ($this->debug) {
                                    $result['html'] = $this->applyTemplate('ajax/debug.phtml', ['debug' => $result]);
                                }
                                $result['id'] = $id;
                                $response[] = $result;
                            }
                        }
                    }

                    if (in_array($check_mode,array('break_next','break_on_first_next'))) $check_mode = $this->current_mode;
                }

                $response['id'] = $id;
                $response['mediatype'] = $mediatype;
                $response['checkRoute'] = $this->checkRoute;
                $response['checks'] = $this->checks;
                $responses[] = $response;
            } catch (\Exception $e) {
                $result['check'] = 'Exception';
                $result['error'] = $e;
                if ($this->debug) {
                    $result['html'] = $this->applyTemplate('ajax/debug.phtml', ['debug' => $result]);
                }
                $response[] = $result;
                $response['id'] = $id;
                $mediatype = 'cannot be determined due to exception';
                $this->checkRoute = 'cannot be determined due to exception';
                $responses[] = $response;
            }
        }

        if ($this->debug) {
            $debug_info = [];
            $debug_info['check'] = 'Debug Info';
            $debug_info['mediatype'] = $mediatype;
            $debug_info['AvailabilityPlusConfig'] = 'availabilityplus.ini';
            $debug_info['SolrMarcYml'] = 'solrmarc.yml';
            $debug_info['SolrMarcYmlAvailabilityPlus'] = $this->config['General']['availabilityplus_yaml'];
            $debug_info['checkRoute_in_AvailabilityPlusConfig'] = $this->checkRoute;
            $debug_info['checks'] = $this->checks;
            $debug_info['availableSolrFields'] = $this->driver ? $this->driver->getAvailableSolrFields() : 'no driver available';
            $responses[0]['debug'] = $debug_info;
            $responses[0][0]['html'] = $this->applyTemplate('ajax/debug.phtml', ['debug' => $debug_info]) . $responses[0][0]['html'];
        }

        return $this->formatResponse(['statuses' => $responses]);
    }

    protected function setChecks($mediatype = '') {
        $list = $this->list;
        $mediatype = str_replace([' ', '+'], ['', ''], $mediatype);
        $checks = $list ? 'ResultList' : 'RecordView';
        $configKeys = array_keys($this->config->toArray());
        $format = $mediatype;

        // go through all 'check groups' (availabilityplus.ini config keys)
        // e. g. [SolrResultList] or [SolrRecordView-Book]
        foreach ($configKeys as $configKey) {
            if (str_contains($configKey, $checks) && str_contains($configKey, $mediatype)) {
                [$configView, $configFormats] = explode('-', $configKey);

                // something after '-' exsists e. g. SolrResultList-Book
                if (!empty($configFormats)) {
                    $splittedFormats = explode('|', $configFormats);
                }

                // set $format with the first configFormats that maches the mediatype
                // e. g. either Book|Journal or just Book
                if (!empty($splittedFormats) && in_array($mediatype, $splittedFormats)) {
                    if (str_starts_with($configView, $this->source) || str_starts_with($configView, $checks)) {
                        $format = $configFormats;

                        break;
                    }
                }
            }
        }

        if (!empty($this->config["{$this->source}{$checks}-{$format}"])) {
            $this->checks = $this->config["{$this->source}{$checks}-{$format}"];
            $this->checkRoute = "{$this->source}{$checks}-{$format}";
        } else if (!empty($this->config["{$checks}-{$format}"])) {
            $this->checks = $this->config["{$checks}-{$format}"];
            $this->checkRoute = "{$checks}-{$format}";
        } else if (!empty($this->config["{$this->source}{$checks}"])) {
            $this->checks = $this->config["{$this->source}{$checks}"];
            $this->checkRoute = "{$this->source}{$checks}";
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
    protected function performAvailabilityCheck($check) {
        if (method_exists($this, $check)) {
            $responses = $this->{$check}();
        } elseif (in_array($check, $this->driver->getSolrMarcKeys('resolver'))) {
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
    protected function checkSolrMarcData($solrMarcKeys, $check) {
        sort($solrMarcKeys);
        array_unique($solrMarcKeys);
        $check_type = 'MARC';
        $urls = [];
        $break = false;

        foreach ($solrMarcKeys as $solrMarcKey) {
            $specs = $this->driver->getSolrMarcSpecs($solrMarcKey);
            $data = $this->driver->getMarcData($solrMarcKey);

            if (!empty($data) && $this->checkConditions($data)) {
                $level = $this->getLevel($data[0], $check, $solrMarcKey);
                $label = $this->getLabel($data[0], $check);
                $template = $this->getTemplate($data);

                foreach ($data as $date) {
                    if (!empty($date['url']['data'][0])) {
                        foreach ($date['url']['data'] as $url) {
                            $url = str_replace('[path]', $this->urlHelper->fromRoute('home'), $url);

                            if (
                                !in_array($url, $urls)
                                || (!empty($specs['allow-url-duplications']) && $specs['allow-url-duplications'])
                            ) {
                                $level = $this->getLevel($date, $level, $solrMarcKey);
                                $label = $this->getLabel($date, $label);
                                $urls[] = $url;
                                $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $date, $check_type, $url, true);
                                $response['html'] = $this->applyTemplate($template, $response);
                                if (empty($response['html'])) {
                                    $response['status']['level'] = 'unsuccessful_check';
                                    $response['status']['label'] = 'Check did not find a match!';
                                }
                                $responses[] = $response;

                                if ($this->current_mode == 'break_on_first') {
                                    $break = true;
                                    break;
                                }
                            }
                        }
                    }

                    if ($break) break;
                }

                if (empty($urls)) {
                    $response = $this->generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $check_type, '', true);
                    $response['html'] = $this->applyTemplate($template, $response);
                    if (empty($response['html'])) {
                        $response['status']['level'] = 'unsuccessful_check';
                        $response['status']['label'] = 'Check did not find a match!';
                    }
                    $responses[] = $response;

                    if ($this->current_mode == 'break_on_first') {
                        $break = true;
                        break;
                    }
                }
            } else {
                $response = $this->generateResponse($check, $solrMarcKey, 'no data', 'no data', 'no data', $data, $check_type, 'no data', false);
                $responses[] = $response;
            }

            if ($break) break;
        }

        return $responses;
    }

    protected function checkConditions($data) {
        $check = true;
        $requirednumberofconditions = 0;
        $numberofconditions = 0;

        foreach ($data as $date) {
            if (!empty($date['requirednumberofconditions']['data'][0])) {
                $requirednumberofconditions = $date['requirednumberofconditions']['data'][0];
            }
        }

        foreach ($data as $date) {
            if (!empty($date['condition_true']['data'][0])) {
                if ($date['condition_true']['data'][0] != 'true') $check = false;
                $numberofconditions += 1;
            } elseif (!empty($date['condition_false']['data'][0])) {
                if ($date['condition_false']['data'][0] != 'false') $check = false;
                $numberofconditions += 1;
            }
        }

        if ($requirednumberofconditions != $numberofconditions) $check = false;

        return $check;
    }

    protected function getLevel($date, $level, $solrMarcKey) {
        if ($level != $solrMarcKey) $level = "{$level} {$solrMarcKey}";
        if (!empty($date['level']['data'][0])) $level = $date['level']['data'][0];
        return $level;
    }

    protected function getLabel($date, $label) {
        if (!empty($date['label']['data'][0])) $label = $date['label']['data'][0];
        return $label;
    }

    protected function generateResponse($check, $solrMarcKey, $level, $label, $template, $data, $check_type, $url = '', $status_bool = false) {
        if ($status_bool) {
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
            'label_translated' => $this->translate("AvailabilityPlus::{$label}"),
            'template' => $template,
            'data' => $data,
            'SolrMarcSupportData' => $this->driver->getMarcData('SupportData'),
            'language' => $this->language
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
    protected function getTemplate($data) {
        $template = $this->default_template;
        if (!empty($data['view-method'])) $template = $data['view-method'];
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
    protected function applyTemplate($template, $response) {
        return trim($this->renderer->render($template, $response));
    }

    /**
     * Custom method to check for a parent work that is a holding of the library
     *
     * @return array [response data (arrays)]
     */
    protected function checkParentWorkILNSolr() {
        $check = 'checkParentWorkILNSolr';
        $check_type = 'function';
        $template = 'ajax/link-parent.phtml';
        $responses = [];
        $parentData = $this->driver->getMarcData('ArticleParentId');
        $response = $this->generateResponse($check, 'ArticleParentId', '', '', $template, '', $check_type, '', false);

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

                if (empty($ilnMatch)) {
                    $ilnMarcSpecs = $this->driver->getSolrMarcSpecs('ILN');
                    $ilnMatch = $this->driver->getMarcData('ILN');
                }

                if (!empty($ilnMatch[0]['iln']['data'][0])) {
                    $url = "/Record/{$parentId}";
                }
            } catch (\Exception $e) {
                $url = '';
            }
        }

        if (!empty($url)) {
            $level = 'ParentWorkILNSolr';
            $label = 'Go to parent work (local holding)';
            $response = $this->generateResponse($check, 'ArticleParentId', $level, $label, $template, $parentData, $check_type, $url, true);
            $response['html'] = $this->renderer->render($template , $response);
        }

        $response['ilnMarcSpecs'] = $ilnMarcSpecs;
        $response['ilnMatch'] = $ilnMatch;
        $responses[] = $response;

        return $responses;
    }

    protected function getResolverResponse($resolver) {
        $start_date_time = date('Y-m-d H:i:s');
        $curTime = microtime(true);
        $check_type = 'Resolver';
        $resolverType = $resolver;

        if (!$this->pluginManager->has($resolverType)) {
            return $this->formatResponse(
                $this->translate("Could not load driver for {$resolverType}"),
                self::STATUS_HTTP_ERROR
            );
        }

        $resolverHandler = new Connection($this->pluginManager->get($resolverType));
        $resolverHandler->setLanguage($this->language);
        $marc_data = $this->driver->getMarcData($resolver);
        $params = $this->prepareResolverParams($marc_data);
        $resolver_url = $resolverHandler->getResolverUrl($params);
        $format = $this->driver->getFormats()[0];

        if (str_starts_with($resolverType, 'JournalsOnlinePrint')) {
            $resolver_url .= $resolverHandler->addOpenUrlFormat($format);
            $params .= $resolverHandler->addOpenUrlFormat($format);
        }

        $template = $this->getTemplate($marc_data);

        if (!empty($resolver_url) && !empty($marc_data)) {
            try {
                $resolver_data = $resolverHandler->fetchLinks($params);
                $response = $this->generateResponse($resolver, $resolver, $resolver, $resolver, $template, $resolver_data['parsed_data'] ?? [], $check_type, $resolver_url, true);
                $response['resolverConfig'] = $this->resolverConfig[$resolver];
                $response['html'] = $this->applyTemplate($template, $response);
                if (empty($response['html'])) {
                    $response['status']['level'] = 'unsuccessful_check';
                    $response['status']['label'] = 'Check did not find a match!';
                }
                $response['resolver_data'] = $resolver_data['data'] ?? [];
                $response['resolver_rule_file'] = $resolverHandler->getRulesFile();
            } catch (\Exception $e) {
                $template = 'ajax/default.phtml';
                $response = $this->generateResponse($resolver, $resolver, 'resolver_error', 'resolver_error', $template, 'resolver_error', 'Resolver-EXCEPTION', $resolver_url, false);
                $response['status']['label'] = 'EXCEPTION occured during processing';
                $response['url'] = '';
                $response['list'] = $this->list;
                $response['html'] = $this->applyTemplate($template, $response);
                $response['resolver_rule_file'] = $resolverHandler->getRulesFile();
                $response = ['error' => $e] + $response;
                $responses[] = $response;

                return $responses;
            }
        } else {
            $response = $this->generateResponse($resolver, $resolver, $resolver, $resolver, $template, '', $check_type, $resolver_url, false);
        }

        $response['start'] = $start_date_time;
        $response['end'] = date('Y-m-d H:i:s');
        $timeConsumed = round(microtime(true) - $curTime,3)*1000;
        $response['duration_in_miliseconds'] = $timeConsumed;
        $responses[] = $response;

        return $responses;
    }

    /**
     * Custom method to check for a MultiVolumeWork (indicated by MARC Leader Positon 19 = A
     *
     * @return array [response data (arrays)]
     */
    protected function checkMultiVolumeWork() {
        $check = 'MultiVolumeWork';
        $check_type = 'function';
        $template = 'ajax/default.phtml';
        $responses = [];

        if ($this->driver->getMultipartResourceRecordLevel() == 'Set') {
            $level = 'MultiVolumeWork';
            $label = 'MultiVolumeWork';

            if ($this->list) {
                if ($this->source == 'Search2') {
                    $url = "/Search2Record/{$this->id}";
                } else {
                    $url = "/Record/{$this->id}";
                }
            }

            $response = $this->generateResponse($check, '', $level, $label, $template, '', $check_type, $url, true);
            $response['html'] = $this->renderer->render($template, $response);
            $responses[] = $response;
        }

        return $responses;
    }

    protected function prepareResolverParams($resolverData) {
        $used_params = [];
        $params = '';

        if (!empty($resolverData)) {
            if (is_array($resolverData)) {
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
