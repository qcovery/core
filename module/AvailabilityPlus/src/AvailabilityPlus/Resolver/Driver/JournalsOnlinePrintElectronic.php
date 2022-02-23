<?php

namespace AvailabilityPlus\Resolver\Driver;

class JournalsOnlinePrintElectronic extends JournalsOnlinePrint
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
    public function parseLinks($data)
    {
        $urls = []; // to check for duplicate urls
        $records = []; // array to return
        $level_org = $level;

        $data = @simplexml_load_string($data, "SimpleXMLElement", LIBXML_COMPACT);
        foreach($data->Full->ElectronicData->ResultList->Result AS $result) {
            if(!empty($result->AccessURL)) {
                $level = '';
                $label = '';
                $url = $result->AccessURL->__toString();
                if(!in_array($url, $urls)) {
                    switch ($result['state']) {
                        case 0:
                            $level = $level_org." FreeAccess link_external";
                            $label = "FreeAccess";
                        case 2:
                            if($result['state'] != 0) {
                                $level = $level_org." LicensedAccess link_external";
                                $label = "LicensedAccess";
                            }
                            $level .= " ".$result->AccessLevel;
                            $label .= "_".$result->AccessLevel;
                            $urls[] = $url;
                            break;
                    }
                    if(!empty($level)) {
                        $record['level'] = $level;
                        $record['label'] = $label;
                        $record['url'] = $url;
                        $records[] = $record;
                    }
                }
            }
        }

        $response['data'] = $records;
        $response['parsed_data'] = $data;
        return $response;
    }
}

