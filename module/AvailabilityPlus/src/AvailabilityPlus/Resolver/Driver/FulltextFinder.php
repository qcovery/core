<?php

namespace AvailabilityPlus\Resolver\Driver;

class FulltextFinder extends AvailabilityPlusResolver
{
    protected $openUrl;
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
        $url = '';
        if(!empty($this->baseUrl)) {
            $url = $this->baseUrl.$openUrl;
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
     * @return string         json returned by resolver
     */
    public function fetchLinks($openUrl)
    {
        $this->openUrl = $openUrl;
        $url = $this->getResolverUrl($openUrl);
        $password = $this->resolverConfig->password;
        $headers = $this->httpClient->getRequest()->getHeaders();
        $headers->addHeaderLine('Accept', 'application/json');
        if(!empty($password)) $headers->addHeaderLine('password', $password);
        $feed = $this->httpClient->setUri($url)->send()->getBody();
        return $feed;
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
        $break = false;

        if (isset($data->contextObjects)) {
            foreach ($data->contextObjects as $contextObject) {
                if (isset($contextObject->targetLinks)) {
                    foreach ($contextObject->targetLinks as $targetLink) {
                        if ($targetLink->category == "FullText") {
                            $links[] = $targetLink;
                            $break = true;
                            break;
                        }
                    }
                }
                if($break) break;
            }
        }

        if(empty($links)) {
            $custid=substr($this->baseUrl, strpos($this->baseUrl,'ftfaccount/') +11, strlen($this->baseUrl) - strpos($this->baseUrl,'.main.ftf') - 9);
            $checkAvailabilityLink = new \stdClass();
            $checkAvailabilityLink->targetUrl = 'https://search.ebscohost.com/login.aspx?site=ftf-live&authtype=ip,guest&custid='.$custid.'&groupid=main&direct=true&'.$this->openUrl;
            $checkAvailabilityLink->linkText = 'Verfügbarkeit prüfen';
            $checkAvailabilityLink->linkName = 'Verfügbarkeit prüfen';
            $checkAvailabilityLink->category = 'CheckAvailability';
            $links[] = $checkAvailabilityLink;
        }

        $response['data'] = $data_org;
        $this->parsed_data = $links;
        $this->applyCustomChanges();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }
}

