<?php
namespace AvailabilityPlus\Resolver\Driver;

class JournalsOnlinePrint extends AvailabilityPlusResolver
{
    protected $doi;

    protected $urlParams;

    /**
     * Get Resolver Url
     *
     * Transform the OpenURL as needed to get a working link to the resolver.
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string Returns resolver specific url
     */
    public function getResolverUrl($openUrl) {
        parse_str($openUrl, $this->urlParams);
	    $this->doi = $this->urlParams['doi'];
        $url = "{$this->baseUrl}{$openUrl}{$this->additionalParams}";

        if (strpos($url, '&pid=client_ip=dynamic') !== false) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $url = str_replace('&pid=client_ip=dynamic', "&pid=client_ip={$ip}", $url);
        }

        $url = $this->fixIssnFormat($url);

        return $url;
    }

    public function addOpenUrlFormat($format) {
        // If we have multiple formats, Book, Journal and Article are most
        $params = '&genre=';

        if ($format == 'Article') {
            $params .= 'article';
        } else if ($format == 'electronic Article') {
            $params .= 'article';
        } else if ($format == 'eJournal') {
            $params .= 'journal';
        } else if ($format == 'Journal') {
            $params .= 'journal';
        } else {
            $params .= 'article';
        }

        return $params;
    }

    public function fixIssnFormat($url) {
        preg_match('/(?<=issn=).*?(?=&)/', $url, $issn); // The 'positive lookbehind' feature may not be supported in all browsers.  => issn=(.*?)(?=&)
        $currentIssn = $issn[0];

        if (!empty($currentIssn)) {
            $length = strlen($currentIssn);

            if ($length == 8) {
                $newIssn = substr($currentIssn, 0, 4) . '-' . substr($currentIssn, 4, $length - 4);
            } else {
                $newIssn = $currentIssn;
            }

            return str_replace($currentIssn, $newIssn, $url);
        } else {
            return $url;
        }
    }
}
