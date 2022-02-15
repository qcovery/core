<?php

namespace AvailabilityPlus\Resolver\Driver;

class AvailabilityPlusResolver extends \VuFind\Resolver\Driver\AbstractBase
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
    public function getResolverUrl($data)
    {
        return 'URL Dummy AP Resolver + '.$data;
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
    public function fetchLinks($openURL)
    {
        // Make the call to SFX and load results
        $url = $this->baseUrl .
            '?sfx.response_type=multi_obj_detailed_xml&svc.fulltext=yes&' . $openURL;
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
    public function parseLinks($xmlstr)
    {
        $records = []; // array to return
        return $records;
    }

}

