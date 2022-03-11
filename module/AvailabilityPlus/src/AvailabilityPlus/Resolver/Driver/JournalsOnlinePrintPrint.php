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
    public function parseLinks($data)
    {
        $data_org = $data;
        $urls = []; // to check for duplicate urls
        $records = []; // array to return
        foreach($data->Full->PrintData->ResultList->Result AS $result) {
            $level = $level_org;
            switch ($result['state']) {
                case 2:
                case 3:
                    $level .= " PrintAccess";
                    $label = "PrintAccess";
                    if(!empty($result->Signature)) {
                        $url = '/vufind/Search/Results?lookfor='.$result->Signature.'&type=Signature';
                    } else {
                        $url = '/vufind/Search/Results?lookfor='.$result->Title.'&type=Title';;
                    }
                    if(!in_array($url, $urls)) {
                        $record['level'] = $level;
                        $record['label'] = $label;
                        $record['url'] = $url;
                        $records[] = $record;                        $urls[] = $url;
                    }
                    break;
            }
        }
        $response['data'] = $data_org;
        $response['parsed_data'] = $records;
        return $response;
    }
}