<?php

namespace AvailabilityPlus\Resolver\Driver;

class JournalsOnlinePrint extends AvailabilityPlusResolver
{
    /**
     * Adjust Resolver Url
     *
     * Adjusts the generated url as needed to get a working link to the resolver.
     *
     * @param string $url URL (url-encoded)
     *
     * @return string Returns resolver specific url
     */
    private function adjustResolverUrl($url)
    {
        if(strpos($url, "&pid=client_ip=dynamic") !== false) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $url = str_replace("&pid=client_ip=dynamic","&pid=client_ip=".$ip, $url);
        }

        return $url;
    }

}

