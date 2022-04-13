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
    public function parseLinks($data_org)
    {
        $urls = []; // to check for duplicate urls
        $records = []; // array to return
        $data = @simplexml_load_string($data_org, "SimpleXMLElement", LIBXML_COMPACT);
        foreach($data->Full->PrintData->ResultList->Result AS $result) {
            $record = [];
            switch ($result['state']) {
                case 2:
                case 3:
                    $level = "PrintAccess";
                    $label = "PrintAccess";
                    if(!empty($result->Signature)) {
                        $url = '/vufind/Search/Results?lookfor='.$result->Signature.'&type=Signature';
                    } else {
                        $url = '/vufind/Search/Results?lookfor='.$result->Title.'&type=Title';
                    }

                    if(!in_array($url, $urls)) {
                        $record['level'] = $level;
                        $record['label'] = $label;
                        $record['url'] = $url;
                        if(!empty($result->Signature)) $record['signature'] = (string)$result->Signature;
                        if(!empty($result->Title)) $record['title'] = (string)$result->Title;
                        if(!empty($result->Location)) $record['location'] = (string)$result->Location;
                        if(!empty($result->Period)) $record['period'] = (string)$result->Period;
                        if(!empty($result->Holding_comment)) $record['Holding_comment'] = (string)$result->Holding_comment;
                        $records[] = $record;
                        $urls[] = $url;
                    }
                    break;
            }
        }
        $response['data'] = $data_org;
        $this->parsed_data = $records;
        $this->applyCustomChanges();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }
}