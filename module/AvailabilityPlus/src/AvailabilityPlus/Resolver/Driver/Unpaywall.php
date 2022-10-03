<?php

namespace AvailabilityPlus\Resolver\Driver;

class Unpaywall extends AvailabilityPlusResolver
{
    /**
     * Get Resolver Url
     *
     * Transform the OpenURL as needed to get a working link to the resolver.
     *
     * @param string $params parameter (url-encoded)
     *
     * @return string Returns resolver specific url
     */
    public function getResolverUrl($params)
    {
        $doi = str_replace('?doi=','',$params);
        if($doi)  $url = $this->baseUrl.'/'.$doi.'?is_oa=boolean'.$this->additionalParams;
        return $url;
    }

    /**
     * Parse Links
     *
     * Parses an XML file returned by a link resolver
     * and converts it to a standardised format for display
     *
     * @param string $data_org JSON returned by resolver
     *
     * @return array         Array of values
     */
    public function parseLinks($data_org)
    {
        $urls = []; // to check for duplicate urls
        $links = []; // array to return
        $data = json_decode($data_org);

        if(isset($data->journal_is_oa) && $data->journal_is_oa == true && isset($data->is_oa) && $data->is_oa == true){
            $links[0]['url'] = $data->best_oa_location->url;
            $links[0]['label'] = 'FreeAccess';
            $links[0]['level'] = 'FreeAccess Unpaywall';
        }

        $response['data'] = $data_org;
        $this->parsed_data = $links;
        $this->applyCustomChanges();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }
}

