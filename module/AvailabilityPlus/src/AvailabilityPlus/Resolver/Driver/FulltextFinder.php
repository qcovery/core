<?php

namespace AvailabilityPlus\Resolver\Driver;

class FulltextFinder extends AvailabilityPlusResolver
{
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
            $url = $this->baseUrl.$openUrl;
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
     * @return string         json returned by resolver
     */
    public function fetchLinks($openUrl)
    {
        $url = $this->getResolverUrl($openUrl);
        $password = $this->additionalParams;
        $headers = $this->httpClient->getRequest()->getHeaders();
        $headers->addHeaderLine('Accept', 'application/json');
        if(!empty($password)) $headers->addHeaderLine('password', $password);
        $feed = $this->httpClient->setUri($url)->send()->getBody();
        return $feed;
    }
}

