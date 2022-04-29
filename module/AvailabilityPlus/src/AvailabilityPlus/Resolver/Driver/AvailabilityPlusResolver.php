<?php

namespace AvailabilityPlus\Resolver\Driver;

use VuFind\Config\SearchSpecsReader;
use VuFind\Crypt\HMAC;
use VuFind\Record\Loader;

class AvailabilityPlusResolver extends \VuFind\Resolver\Driver\AbstractBase
{
    /**
     * HTTP client
     *
     * @var \Zend\Http\Client
     */
    protected $httpClient;

    protected $additionalParams;

    protected $rules;

    protected $hmac;

    protected $parsed_data;

    /**
     * Constructor
     *
     * @param string            $baseUrl    Base URL for link resolver
     * @param \Zend\Http\Client $httpClient HTTP client
     */
    public function __construct($baseUrl, \Zend\Http\Client $httpClient, $additionalParams, $rules, HMAC $hmac)
    {
        parent::__construct($baseUrl);
        $this->httpClient = $httpClient;
        $this->additionalParams = $additionalParams;
        $this->rules = $rules;
        $this->hmac = $hmac;
    }

    /**
     * Get Resolver Url
     *
     * Transform the OpenURL as needed to get a working link to the resolver.
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string Returns resolver specific url
     */
    public function getResolverUrl($openUrl)
    {
        $url = '';
        if(!empty($this->baseUrl)) {
            $url = $this->baseUrl.$openUrl.$this->additionalParams;
        }
        return $url;
    }

    /**
     * Fetch Links
     *
     * Fetches a set of links corresponding to an OpenURL
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string         raw XML returned by resolver
     */
    public function fetchLinks($openUrl)
    {
        $url = $this->getResolverUrl($openUrl);
        $feed = $this->httpClient->setUri($url)->send()->getBody();
        return $feed;
    }

    /**
     * Parse Links
     *
     * Parses an XML file returned by a link resolver
     * and converts it to a standardised format for display
     *
     * @param string $xmlstr Raw XML returned by resolver
     *
     * @return array         Array of values
     */
    public function parseLinks($data_org)
    {
        $this->parsed_data = $data_org;
        $response['data'] = $data_org;
        $this->applyCustomChanges();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }

    public function getRulesFile() {
        return $this->rules;
    }

    protected function applyCustomChanges() {

        $specsReader = new SearchSpecsReader();
        $rules = $specsReader->get($this->rules);
        foreach($this->parsed_data as $key => $item) {
            $rules_applied = [];
            foreach($rules as $rule) {
                $rule_applies = false;
                foreach($rule['conditions'] as $condition) {
                    $match_array = [];
                    $field_content = $item[$condition['field']];
                    preg_match('|'.$condition['content'].'|',$field_content,$match_array);
                    if(!empty($match_array)){
                        $rule_applies = true;
                    } else {
                        $rule_applies = false;
                        break;
                    }
                }

                if($rule_applies){
                    foreach($rule['actions'] as $action)
                    {
                        $content_old = $item[$action['field']];
                        $content_new = $content_old;
                        if(!empty($action['pattern'])) {
                            $content_preg =  $item[$action['content_field']];
                            $content_new = preg_replace('|'.$action['pattern'].'|', $action['replacement'], $content_preg);
                            $this->parsed_data[$key][$action['field'].'_org'] = $content_old;
                            $this->parsed_data[$key][$action['field']] = $content_new;
                        } else if(isset($action['content'])){
                            $content_new = preg_replace('|(.*)|', '$0', $action['content']);
                            $this->parsed_data[$key][$action['field'].'_org'] = $content_old;
                            $this->parsed_data[$key][$action['field']] = $content_new;
                        } else if(!empty($action['function'])) {
                            switch($action['function']) {
                                case 'removeItem' :
                                    //unset($this->parsed_data[$key]);
                                    foreach($this->parsed_data[$key] AS $item_key => $item_value) {
                                        $this->parsed_data[$key][$item_key.'_org'] = $item_value;
                                        unset($this->parsed_data[$key][$item_key]);
                                    }
                                    break;
                            }
                        }

                    }

                    $rules_applied[] = $rule;
                }
            }
            if(!empty($rules_applied)) {
                $this->parsed_data[$key]['rules_applied'] = $rules_applied;
            }
        }
    }

    protected function getObjectPathValue($item, $path) {
        $content = '';
        switch(count($path)) {
            case 1 :
                $content = $item[$path[0]];
                break;
            case 2 :
                $content = $item[$path[0]][$path[1]];
                break;
            case 3 :
                $content = $item[$path[0]][$path[1]][$path[2]];
                break;
            case 4 :
                $content = $item[$path[0]][$path[1]][$path[2]][$path[3]];
                break;
            case 5 :
                $content = $item[$path[0]][$path[1]][$path[2]][$path[3]][$path[4]];
                break;
        }
        return $content;
    }

    protected function setObjectPathValue($key, $path, $value) {
        switch(count($path)) {
            case 1 :
                $this->parsed_data->item[$key][$path[0]] = $value;
                break;
            case 2 :
                $this->parsed_data->item[$key][$path[0]][$path[1]] = $value;
                break;
            case 3 :
                $this->parsed_data->item[$key][$path[0]][$path[1]][$path[2]] = $value;
                break;
            case 4 :
                $this->parsed_data->item[$key][$path[0]][$path[1]][$path[2]][$path[3]] = $value;
                break;
            case 5 :
                $this->parsed_data->item[$key][$path[0]][$path[1]][$path[2]][$path[3]][$path[4]] = $value;
                break;
        }
    }
}

