<?php

namespace AvailabilityPlus\Resolver\Driver;

class JournalsOnlinePrint extends AvailabilityPlusResolver
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
        $url = $this->baseUrl.$openUrl.$this->additionalParams;
        if(strpos($url, "&pid=client_ip=dynamic") !== false) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $url = str_replace("&pid=client_ip=dynamic","&pid=client_ip=".$ip, $url);
        }
        return $url;
    }
}

