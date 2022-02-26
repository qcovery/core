<?php

namespace AvailabilityPlus\Resolver\Driver;

class DAIA extends AvailabilityPlusResolver
{
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
        $records = []; // array to return

        $data = json_decode($data);

        foreach($data->document[0]->item as $item) {

            $item_services['available']['openaccess'] = [];
            $item_services['available']['remote'] = [];
            $item_services['available']['loan'] = [];
            $item_services['available']['presentation'] = [];
            $item_services['available']['fallback'] = 'fallback';

            foreach($item->available as $service) {
                $item_services['available'][$service->service] = $service;
            }

            foreach($item->unavailable as $service) {
                if(count(get_object_vars($service)) > 1) {
                    $item_services['available'][$service->service] = $service;
                }
            }

            $break = false;
            foreach($item_services['available'] as $service_key=>$service_content) {
                $level='';
                $label='';
                $url='';
                $limitation='';
                $storage  = [];
                $callnumber = '';
                $about = '';
                if(!empty($service_content)) {
                    switch($service_key) {
                        case 'openaccess':
                            $level = 'FreeAccess link_external';
                            $label = 'FreeAccess';
                            $url = $service_content->href;
                            break;
                        case 'remote':
                            $level = 'LicensedAccess link_external';
                            $label = 'LicensedAccess';
                            $url = $service_content->href;
                            break;
                        case 'loan':
                        case 'presentation':
                            if(!empty($item->storage->id)){
                                $storage['$level'] = 'link_external';
                                $storage['label'] = $item->storage->content;
                                $storage['url'] = $item->storage->id;
                            } else {
                                $storage['label'] = 'unknown_location';
                            }
                            if(!empty($item->label)) $callnumber = $item->label;
                            if(!empty($service_content->limitation[0]->id)) {
                                $limitation = substr($service_content->limitation[0]->id, strpos($service_content->limitation[0]->id, "#") + 1);
                                $level = $limitation;
                                $label = $service_content->service.$limitation;
                            } elseif(!empty($service_content->limitation[0]->content)) {
                                $limitation = $service_content->limitation[0]->content;
                                $level = $limitation;
                                $label = $service_content->service.$limitation;
                            } elseif(!empty($service_content->expected)) {
                                $level = "daia_orange";
                                $date = date_create($service_content->expected);
                                $label = $this->translate('on_loan_until').' '.date_format($date,"d.m.Y");
                            } else {
                                $level = "daia_green";
                                $label = $service_content->service;
                            }
                            if(!empty($service_content->href)) {
                                $url = $service_content->href;
                                $level = 'internal_link';
                                $url_components = parse_url($url);
                                parse_str($url_components['query'], $params);
                                $label = $params['action'];
                            }
                            if(isset($service_content->queue)) {
                                $label = 'Recalls';
                                if($service_content->queue == 1) $label = 'Recall';
                            }
                            if(!empty($item->about)) {
                                $about = $item->about;
                            }
                            break;
                        case 'fallback':
                            if(!empty($item->storage->id)){
                                $storage['$level'] = 'link_external';
                                $storage['label'] = $item->storage->content;
                                $storage['url'] = $item->storage->id;
                            } else {
                                $storage['label'] = 'unknown_location';
                            }
                            if(!empty($item->label)) $callnumber = $item->label;
                            break;
                    }
                    if(!empty($level)) {
                        $record['level'] = $level;
                        $record['label'] = $label;
                        $record['url'] = $url;
                        $record['limitation'] = $limitation;
                        $record['storage'] = $storage;
                        $record['callnumber'] = $callnumber;
                        $record['about'] = $about;
                        $records[] = $record;
                    }
                    break;
                }
            }
        }

        $response['data'] = $records;
        $response['parsed_data'] = $data_org;
        return $response;
    }
}

