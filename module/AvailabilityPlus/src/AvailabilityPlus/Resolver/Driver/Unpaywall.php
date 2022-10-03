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
        parse_str($params, $param_arr);
        $doi = '';
        if(!empty($param_arr['doi'] && !is_array($params['doi']))) {
            $doi = $param_arr['doi'];
        } elseif(!empty($param_arr['doi'][0] && !is_array($param_arr['doi']))) {
            $doi = $param_arr['doi'][0];
        }
        if($doi)  $url = $this->baseUrl.'/'.$doi.'/'.$this->additionalParams.'&is_oa=boolean';
        return $url;
    }
}

