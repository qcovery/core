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
}

