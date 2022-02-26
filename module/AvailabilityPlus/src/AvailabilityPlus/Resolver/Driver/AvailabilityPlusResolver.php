<?php

namespace AvailabilityPlus\Resolver\Driver;

use VuFind\Crypt\HMAC;

class AvailabilityPlusResolver extends \VuFind\Resolver\Driver\AbstractBase
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;
    /**
     * HTTP client
     *
     * @var \Zend\Http\Client
     */
    protected $httpClient;

    protected $additionalParams;

    protected $options;

    protected $hmac;

    /**
     * Constructor
     *
     * @param string            $baseUrl    Base URL for link resolver
     * @param \Zend\Http\Client $httpClient HTTP client
     */
    public function __construct($baseUrl, \Zend\Http\Client $httpClient, $additionalParams, $options, HMAC $hmac)
    {
        parent::__construct($baseUrl);
        $this->httpClient = $httpClient;
        $this->additionalParams = $additionalParams;
        $this->options = $options;
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
    public function parseLinks($data)
    {
        $response['data'] = $data;
        $response['parsed_data'] = $data;
        return $response;
    }

    public function test(){
        return $this->hmac->generate([1,2,3],[1,2,3]);
    }
}

