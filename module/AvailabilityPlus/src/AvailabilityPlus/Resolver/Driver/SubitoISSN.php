<?php

namespace AvailabilityPlus\Resolver\Driver;

class SubitoISSN extends Subito
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
        $url = $subito_base_url.$params.'CAT=ZDB';
        return $url;
    }
}

