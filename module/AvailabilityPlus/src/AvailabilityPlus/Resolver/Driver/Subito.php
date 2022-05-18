<?php

namespace AvailabilityPlus\Resolver\Driver;

class Subito extends AvailabilityPlusResolver
{
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
        return $url;
    }

    /**
     * Parse Links
     *
     * Parses an XML file returned by a link resolver
     * and converts it to a standardised format for display
     *
     * @param string $url
     *
     * @return array         Array of values
     */
    public function parseLinks($data_org)
    {

        $urls = []; // to check for duplicate urls
        $records = []; // array to return

        $record['level'] = "subito_preorder_check";
        $record['label'] = "subito_preorder_check";
        $record['url'] = $data_org;
        $records[] = $record;

        $response['data'] = $data_org;
        $this->parsed_data = $records;
        $this->applyCustomChanges();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }
}

