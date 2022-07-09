<?php

namespace AvailabilityPlus\Resolver\Driver;

class FulltextFinder extends AvailabilityPlusResolver
{
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
        parse_str($openUrl, $openUrl_arr);
        $password = $openUrl_arr['password'];
        //unset($openUrl_arr['password']);
        $openUrl=http_build_query($openUrl_arr);
        $url = $this->getResolverUrl($openUrl);
        $headers = $this->httpClient->getRequest()->getHeaders();
        $headers->addHeaderLine('Accept', 'application/json');
        if(!empty($password)) $headers->addHeaderLine('password', $password);
        $feed = $this->httpClient->setUri($url)->send()->getBody();
        return $feed;
    }
}

