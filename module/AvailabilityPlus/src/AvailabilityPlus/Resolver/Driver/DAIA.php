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

        $data = json_decode($data_org);

        foreach($data->document[0]->item as $item) {
            $record =  [];

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

            foreach($item_services['available'] as $service_key=>$service_content) {
                 if(!empty($service_content)) {
                    switch($service_key) {
                        case 'openaccess':
                            $record['level'] = 'FreeAccess link_external';
                            $record['label'] = $this->translate('FreeAccess');
                            $record['url'] = $service_content->href;
                            break;
                        case 'remote':
                            $record['level'] = 'LicensedAccess link_external';
                            $record['label'] = $this->translate('LicensedAccess');
                            $record['url'] = $service_content->href;
                            break;
                        case 'loan':
                        case 'presentation':
                            if(!empty($item->storage->id)){
                                $record['storage']['level'] = 'link_external';
                                $record['storage']['label'] = $this->translate($item->storage->content);
                                $record['storage']['url'] = $item->storage->id;
                            } else {
                                $record['storage']['label'] = 'unknown_location';
                            }
                            if(!empty($item->label)) $record['callnumber'] = $item->label;
                            if(!empty($service_content->limitation[0]->id)) {
                                $limitation = substr($service_content->limitation[0]->id, strpos($service_content->limitation[0]->id, "#") + 1);
                                $record['daia_hint']['level'] = $limitation;
                                $record['daia_hint']['label'] = $this->translate($service_content->service.$limitation);
                            } elseif(!empty($service_content->limitation[0]->content)) {
                                $limitation = $service_content->limitation[0]->content;
                                $record['daia_hint']['level'] = $limitation;
                                $record['daia_hint']['label'] = $this->translate($service_content->service.$limitation);
                            } elseif(!empty($service_content->expected)) {
                                $record['daia_hint']['level'] = "daia_orange";
                                $date = date_create($service_content->expected);
                                $record['daia_hint']['label'] = $this->translate('on_loan_until').' '.date_format($date,"d.m.Y");
                            } else {
                                $record['daia_hint']['level'] = "daia_green";
                                $record['daia_hint']['label'] = $this->translate($service_content->service);
                            }
                            if(!empty($service_content->href)) {
                                $record['daia_action']['url'] = $service_content->href;
                                $record['daia_action']['level'] = 'internal_link';
                                $url_components = parse_url($record['daia_action']['url']);
                                parse_str($url_components['query'], $params);
                                $record['daia_action']['label'] = $params['action'];
                            }
                            if(isset($service_content->queue)) {
                                $record['queue'] = $service_content->queue;
                                if($service_content->queue == 1) {
                                    $record['queue'] .=  ' '.$this->translate('Recall');
                                } else {
                                    $record['queue'] .=  ' '.$this->translate('Recalls');
                                }
                            }
                            if(!empty($item->about)) {
                                $record['about'] = $item->about;
                            }
                            break;
                        case 'fallback':
                            if(!empty($item->storage->id)){
                                $record['storage']['level'] = 'link_external';
                                $record['storage']['label'] = $item->storage->content;
                                $record['storage']['url'] = $item->storage->id;
                            } else {
                                $record['storage']['label'] = 'unknown_location';
                            }
                            if(!empty($item->label)) $record['callnumber'] = $item->label;
                            if(!empty($item->about)) {
                                $record['about'] = $item->about;
                            }
                            break;
                    }
                    $records[] = $record;
                    break;
                }
            }
        }

        $response['data'] = $records;
        $response['parsed_data'] = $data_org;
        return $response;
    }
}

