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
}

