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
                        echo '<div class="delimiter"></div>';
                        echo '<a href="' . $url . '" class="' . $level . ' link_external" title="' . $label . '" target="_blank">' . $this->translate($label) . ' ' . $result->Location . '</a><br/>';
                        $urls[] = $url;
                    }
                    break;
            }
        }
        $response['data'] = $data_org;
        $response['parsed_data'] = $records;
        return $response;
    }
}