<?php

namespace AvailabilityPlus\Resolver\Driver;

class Subito extends AvailabilityPlusResolver
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

        $subito_base_url = 'https://www.subito-doc.de/preorder?';
        if(strpos($openUrl,"isbn") !== false) {
            $url = $subito_base_url.'SB='.$isbn.'&ND='.$ppn;
        } else if(strpos($openUrl,"issn") !== false) {
            $url = $subito_base_url.'ATI='.$atitle.'&JT='.$title.'&SS='.$issn.'&VOL='.$volume.'&APY='.$date.'&PG='.$pages.'&CAT=ZDB';
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

        $record['level'] = "Subito";
        $record['label'] = "Subito";
        $record['url'] = $data_org;
        $records[] = $record;

        $response['data'] = $data_org;
        $this->parsed_data = $records;
        $this->applyCustomChanges();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }
}

