<?php
namespace AvailabilityPlus\Resolver\Driver;

class JournalsOnlinePrintPrint extends JournalsOnlinePrint
{
    /**
     * Parse Links
     *
     * Parses an XML file returned by a link resolver
     * and converts it to a standardised format for display
     *
     * @param string $xmlstr Raw XML returned by resolver
     *
     * @return array         Array of values
     */
    public function parseLinks($data_org) {
        $escaper = new \Laminas\Escaper\Escaper('utf-8');
        $urls = []; // to check for duplicate urls
        $records = []; // array to return
        $data = @simplexml_load_string($data_org, 'SimpleXMLElement', LIBXML_COMPACT);
        $data = json_decode(json_encode($data), true);
        $signatureSearch = $this->resolverConfig['signature_search'];
        $titleSearch = $this->resolverConfig['title_search'];

        if (array_key_exists('@attributes', $data['Full']['PrintData']['ResultList']['Result'])) {
            $results = $data['Full']['PrintData']['ResultList'];
        } else {
            $results = $data['Full']['PrintData']['ResultList']['Result'];
        }

        foreach ($results as $result) {
            $record = [];
            switch ($result['@attributes']['state']) {
                case 2:
                case 3:
                    $level = 'PrintAccess';
                    $label = 'PrintAccess';

                    if (!empty($result['Signature'])) {
                        $url = $this->urlHelper->fromRoute('home') . 'Search/Results?lookfor=' . $escaper->escapeHtml($result['Signature']) . '&type=' . $signatureSearch;
                    } else {
                        $url = $this->urlHelper->fromRoute('home') . 'Search/Results?lookfor=' . $escaper->escapeHtml($result['Title']) . '&type=' . $titleSearch;
                    }

                    if (!in_array($url, $urls)) {
                        $record['score'] = 0;
                        $record['level'] = $level;
                        $record['label'] = $label;
                        $record['url'] = $url;
                        if (!empty($result['Signature'])) $record['signature'] = $result['Signature'];
                        if (!empty($result['Title'])) $record['title'] = $result['Title'];
                        if (!empty($result['Location'])) $record['location'] = $result['Location'];
                        if (!empty($result['Period'])) $record['period'] = $result['Period'];
                        if (!empty($result['Holding_comment'])) $record['Holding_comment'] = $result['Holding_comment'];
                        $records[] = $record;
                        $urls[] = $url;
                    }
                    break;
            }
        }

        $response['data'] = $data_org;
        $this->parsed_data = $records;
        $this->applyCustomChanges();

        uasort($this->parsed_data, function($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        $response['parsed_data'] = $this->parsed_data;

        return $response;
    }
}
