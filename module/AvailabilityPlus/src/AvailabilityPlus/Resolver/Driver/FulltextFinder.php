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
        $url = $this->baseUrl.urlencode($openUrl).$this->additionalParams;

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
        $headers = $this->httpClient->getRequest()->getHeaders();
        $headers->addHeaderLine('Accept', 'application/json');
        //$headers->addHeaderLine('password', 'ebs421');
        $feed = $this->httpClient->setUri($url)->send()->getBody();
        return $feed;
    }
}

