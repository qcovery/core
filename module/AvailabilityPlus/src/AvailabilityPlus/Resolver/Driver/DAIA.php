<?php

namespace AvailabilityPlus\Resolver\Driver;

use VuFind\Config\SearchSpecsReader;

class DAIA extends AvailabilityPlusResolver
{
    protected $parsed_data;
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

        $data = json_decode($data_org);
        $this->parsed_data = $data;

        foreach($data->document[0]->item as $key => $item) {
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
                            if(!in_array($service_content->href, $urls)) {
                                $record['daia_action']['level'] = 'FreeAccess link_external';
                                $record['daia_action']['label'] = 'FreeAccess';
                                $record['daia_action']['url'] = $service_content->href;
                                $urls[] = $record['daia_action']['url'];
                            }
                            break;
                        case 'remote':
                            if(!in_array($service_content->href, $urls)) {
                                $record['daia_action']['level'] = 'LicensedAccess link_external';
                                $record['daia_action']['label'] = 'LicensedAccess';
                                $record['daia_action']['url'] = $service_content->href;
                                $urls[] = $record['daia_action']['url'];
                            }
                            break;
                        case 'loan':
                        case 'presentation':
                            if(!empty($item->storage->id)){
                                $record['storage']['level'] = 'link_external';
                                $record['storage']['label'] = $item->storage->content;
                                $record['storage']['url'] = $item->storage->id;
                            } else {
                                $record['storage']['label'] = 'unknown_location';
                            }
                            if(!empty($item->label)) $record['callnumber'] = $item->label;
                            if(!empty($service_content->limitation[0]->id)) {
                                $limitation = substr($service_content->limitation[0]->id, strpos($service_content->limitation[0]->id, "#") + 1);
                                $record['daia_hint']['level'] = $limitation;
                                $record['daia_hint']['label'] = $service_content->service.$limitation;
                            } elseif(!empty($service_content->limitation[0]->content)) {
                                $limitation = $service_content->limitation[0]->content;
                                $record['daia_hint']['level'] = $limitation;
                                $record['daia_hint']['label'] = $service_content->service.$limitation;
                            } elseif(!empty($service_content->expected)) {
                                $record['daia_hint']['level'] = "daia_orange";
                                $date = date_create($service_content->expected);
                                $record['daia_hint']['label'] = 'on_loan_until';
                                $record['daia_hint']['label_date'] = date_format($date,"d.m.Y");
                            } else {
                                $record['daia_hint']['level'] = "daia_green";
                                $record['daia_hint']['label'] = $service_content->service;
                            }
                            if(!empty($service_content->href)) {
                                $record['daia_action']['level'] = 'internal_link';
                                $url_components = parse_url($service_content->href);
                                parse_str($url_components['query'], $params);
                                $record['daia_action']['label'] = $params['action'];
                                $record['daia_action']['url'] = $this->generateOrderLink($params['action'], $data->document[0]->id, $item->id, $item->storage->id);
                            } else {
                                $record['daia_action']['label'] = $service_content->service.'_default_action'.$limitation;
                            }
                            if(isset($service_content->queue)) {
                                $record['queue']['length'] = $service_content->queue;
                                if($service_content->queue == 1) {
                                    $record['queue']['label'] .=  'Recall';
                                } else {
                                    $record['queue']['label'] .=  'Recalls';
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
                            $record['daia_hint']['level'] = 'daia_red';
                            $record['daia_hint']['label'] = 'not_available';
                            if(!empty($item->about)) {
                                $record['about'] = $item->about;
                            }
                            break;
                    }
                    $this->parsed_data->document[0]->item[$key]->availabilityplus = $record;
                    break;
                }
            }
        }

        $response['data'] = $data_org;
        $this->applyCustomChanges();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }

    private function generateOrderLink ($action, $doc_id, $item_id, $storage_id) {
        if ($action == 'reserve') $action = 'recall';
        $id = substr($doc_id, strrpos($doc_id, ":") + 1);
        $hmacKeys = explode(':','id:item_id:doc_id');
        $hmacPairs = [
            'id' => $id,
            'doc_id' => $doc_id,
            'item_id' => $item_id
        ];
        return $id.'/Hold?doc_id='.urlencode($doc_id).'&item_id='.urlencode($item_id).'&type='.$action.'&storage_id='.urlencode($storage_id).'&hashKey='.$this->hmac->generate($hmacKeys,$hmacPairs);
    }

    private function applyCustomChanges() {

        $specsReader = new SearchSpecsReader();
        $rules = $specsReader->get('availabilityplus-daia.yaml');

        foreach($this->parsed_data->document[0]->item as $key => $item) {
            foreach($rules as $rule) {
                $rule_applies = false;
                foreach($rule['conditions'] as $condition) {
                    $this->parsed_data->test = $item->department->id;
                    $this->parsed_data->test2 = $condition['field'];
                    $field_array = explode('->',$condition['field']);
                    $this->parsed_dataa->test3 = $field_array;
                    $field_content = $this->getObjectPathValue($item, $field_array);
                    $this->parsed_data->test4 = $field_content;
                    $this->parsed_data->test5 = $condition['content'];
                    if ($field_content == $condition['content']) {
                        $rule_applies = true;
                    } else {
                        $rule_applies = false;
                    }
                }
                $this->parsed_data->test6 = $rule_applies;
                if($rule_applies){
                    foreach($rule['actions'] as $action)
                    {
                        $this->parsed_data->test7 = explode('->',$action['field']);
                        $this->parsed_data->test8 = $action['content'];
                        $this->setObjectPathValue($key, explode('->',$action['field']), $action['content']);
                    }
                }
            }
        }
        $this->parsed_data->rules = $rules;
    }

    private function getObjectPathValue($item, $path) {
        $content = '';
        switch(count($path)) {
            case 1 :
                $content = $item->{$path[0]};
                break;
            case 2 :
                $content = $item->{$path[0]}->{$path[1]};
                break;
            case 3 :
                $content = $item->{$path[0]}->{$path[1]}->{$path[2]};
                break;
            case 4 :
                $content = $item->{$path[0]}->{$path[1]}->{$path[2]}->{$path[3]};
                break;
            case 5 :
                $content = $item->{$path[0]}->{$path[1]}->{$path[2]}->{$path[3]}->{$path[4]};
                break;
        }
        return $content;
    }

    private function setObjectPathValue($key, $path, $value) {
        switch(count($path)) {
            case 1 :
                $this->parsed_data->document[0]->item[$key]->availabilityplus->{$path[0]} = $value;
                $this->parsed_data->test9 = $value;
                break;
            case 2 :
                $this->parsed_data->document[0]->item[$key]->availabilityplus->{$path[0]}->{$path[1]} = $value;
                $this->parsed_data->test9 = $value;
                break;
            case 3 :
                $this->parsed_data->document[0]->item[$key]->availabilityplus->{$path[0]}->{$path[1]}->{$path[2]} = $value;
                $this->parsed_data->test9 = $value;
                break;
            case 4 :
                $this->parsed_data->document[0]->item[$key]->availabilityplus->{$path[0]}->{$path[1]}->{$path[2]}->{$path[3]} = $value;
                $this->parsed_data->test9 = $value;
                break;
            case 5 :
                $this->parsed_data->document[0]->item[$key]->availabilityplus->{$path[0]}->{$path[1]}->{$path[2]}->{$path[3]}->{$path[4]} = $value;
                $this->parsed_data->test9 = $value;
                break;
        }
    }
}

