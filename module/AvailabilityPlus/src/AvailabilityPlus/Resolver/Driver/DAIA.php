<?php

namespace AvailabilityPlus\Resolver\Driver;

use VuFind\Config\SearchSpecsReader;

class DAIA extends AvailabilityPlusResolver
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;
    /**
     * Fetch Links
     *
     * Fetches a set of links corresponding to an OpenURL
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string         raw XML returned by resolver
     */
    public function fetchLinks($openUrl)
    {
        $url = $this->getResolverUrl($openUrl);
        $headers = $this->httpClient->getRequest()->getHeaders();
        $headers->addHeaderLine('Accept-Language', $this->getTranslatorLocale());
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

        $data = json_decode($data_org);
        $this->parsed_data = $data;

        foreach($data->document[0]->item as $key => $item) {

            $item_services['available']['openaccess'] = [];
            $item_services['available']['remote'] = [];
            $item_services['available']['loan'] = [];
            $item_services['available']['presentation'] = [];
            $item_services['available']['fallback'][] = 'fallback';

            foreach($item->available as $service) {
                $item_services['available'][$service->service][] = $service;
                $item_services['available']['fallback'] = [];
            }

            foreach($item->unavailable as $service) {
                if(count(get_object_vars($service)) > 1) {
                    $item_services['available'][$service->service][] = $service;
                }
            }

            $break = false;
            foreach($item_services['available'] as $service_key=>$service_group) {
                foreach($service_group as $service_content) {
                    $record =  (object)[];
                    if(!empty($service_content)) {
                        $record->id = $data->document[0]->id;
                        $record->ppn = substr($data->document[0]->id, strrpos($data->document[0]->id, ":") + 1);
                        if($item->id && strpos($item->id, "epn:") !== false) {
                            $record->epn_id = $item->id;
                            $record->epn = substr($item->id, strrpos($item->id, ":") + 1);
                        } elseif ($item->{'temporary-hack-do-not-use'}) {
                            $record->epn = $item->{'temporary-hack-do-not-use'};
                        }
                        if($item->id && strpos($item->id, ":bar:") !== false) {
                            $record->barcode_id = $item->id;
                            $record->barcode = substr($item->id, strrpos($item->id, "$") + 1);
                        } elseif($service_content->href) {
                            $query = parse_url($service_content->href,PHP_URL_QUERY);
                            $query_array = array();
                            parse_str($query, $query_array);
                            $record->barcode = $query_array['bar'];
                        }
                        $record->service = $service_key;
                        switch($service_key) {
                            case 'openaccess':
                                if(!in_array($service_content->href, $urls) || !$this->resolverConfig->hide_url_duplicates) {
                                    $record->daia_action->level = 'FreeAccess link_external';
                                    if (!empty($service_content->title)) $record->daia_action->title = $service_content->title;
                                    $record->daia_action->label = 'FreeAccess';
                                    $record->daia_action->url = $service_content->href;
                                    $urls[] = $record->daia_action->url;
                                    if (!empty($item->about)) {
                                        $record->about = $item->about;
                                    }
                                    if (!empty($item->chronology->about)) {
                                        $record->chronology = $item->chronology->about;
                                    }
                                    $record->score = 0;
                                    if (empty($this->parsed_data->document[0]->item[$key]->availabilityplus)) {
                                        $this->parsed_data->document[0]->item[$key]->availabilityplus = $record;
                                    }
                                    $this->parsed_data->document[0]->item[$key]->availabilityplus->daia_action_array[] = $record->daia_action;
                                }
                            case 'remote':
                                if(!in_array($service_content->href, $urls) || !$this->resolverConfig->hide_url_duplicates) {
                                    $record->daia_action->level = 'LicensedAccess link_external';
                                    if (!empty($service_content->title)) $record->daia_action->title = $service_content->title;
                                    $record->daia_action->label = 'LicensedAccess';
                                    $record->daia_action->url = $service_content->href;
                                    $urls[] = $record->daia_action->url;
                                    if (!empty($item->about)) {
                                        $record->about = $item->about;
                                    }
                                    if (!empty($item->chronology->about)) {
                                        $record->chronology = $item->chronology->about;
                                    }
                                    $record->score = 10;
                                    if (empty($this->parsed_data->document[0]->item[$key]->availabilityplus)) {
                                        $this->parsed_data->document[0]->item[$key]->availabilityplus = $record;
                                    }
                                    $this->parsed_data->document[0]->item[$key]->availabilityplus->daia_action_array[] = $record->daia_action;
                                }
                            case 'loan':
                            case 'presentation':
                                if(empty($item_services['available']['openaccess']) && empty($item_services['available']['remote'])) {
                                    if($service_key == 'loan') {
                                        $record->score = 20;
                                    } else {
                                        $record->score = 30;
                                    }
                                    if(!empty($item->storage->href)){
                                        $record->storage->level = 'link_external';
                                        $record->storage->label = $item->storage->content;
                                        $record->storage->url = $item->storage->href;
                                    } elseif(!empty($item->storage->id)){
                                        $record->storage->level = 'link_external';
                                        $record->storage->label = $item->storage->content;
                                        $record->storage->url = $item->storage->id;
                                    } else {
                                        $record->storage->label = 'unknown_location';
                                    }
                                    if(!empty($item->label)) $record->callnumber = $item->label;
                                    if(!empty($service_content->limitation[0]->id)) {
                                        $limitation = substr($service_content->limitation[0]->id, strpos($service_content->limitation[0]->id, "#") + 1);
                                        $record->daia_hint->level = $limitation;
                                        $record->daia_hint->label = $service_content->service.$limitation;
                                        $record->score += 5;
                                    } elseif(!empty($service_content->limitation[0]->content)) {
                                        $limitation = $service_content->limitation[0]->content;
                                        $record->daia_hint->level = $limitation;
                                        $record->daia_hint->label = $service_content->service.$limitation;
                                        $record->score += 5;
                                    } elseif(!empty($service_content->expected)) {
                                        $record->daia_hint->level = "daia_orange";
                                        $date = date_create($service_content->expected);
                                        $record->daia_hint->label = 'on_loan_until';
                                        $record->daia_hint->label_date = date_format($date,"d.m.Y");
                                        $record->score += 20;
                                    } else {
                                        $record->daia_hint->level = "daia_green";
                                        $record->daia_hint->label = $service_content->service;
                                    }
                                    if(!empty($service_content->href)) {
                                        $record->daia_action->level = 'internal_link';
                                        $url_components = parse_url($service_content->href);
                                        parse_str($url_components['query'], $params);
                                        $action = $params['action'];
                                        if ($action == 'reserve') $action = 'recall';
                                        $record->daia_action->label = $action;
                                        $record->daia_action->url = $this->generateOrderLink($action, $data->document[0]->id, $item->id, $item->storage->id);
                                    } else {
                                        $record->daia_action->label = $service_content->service.'_default_action'.$limitation;
                                    }
                                    if(isset($service_content->queue)) {
                                        $record->queue->length = $service_content->queue;
                                        if($service_content->queue == 1) {
                                            $record->queue->label .=  'Recall';
                                        } else {
                                            $record->queue->label .=  'Recalls';
                                        }
                                        $record->score += $service_content->queue;
                                    }
                                    if(!empty($item->about)) {
                                        $record->about = $item->about;
                                    }
                                    if(!empty($item->chronology->about)) {
                                        $record->chronology = $item->chronology->about;
                                    }
                                    $this->parsed_data->document[0]->item[$key]->availabilityplus = $record;
                                    $break = true;
                                    break;
                                }
                            case 'fallback':
                                if(empty($item_services['available']['openaccess']) && empty($item_services['available']['remote']) && empty($item_services['available']['loan']) && empty($item_services['available']['presentation'])) {
                                    if(!empty($item->storage->id)){
                                        $record->storage->level = 'link_external';
                                        $record->storage->label = $item->storage->content;
                                        $record->storage->url = $item->storage->id;
                                    } else {
                                        $record->storage->label = 'unknown_location';
                                    }
                                    if(!empty($item->label)) $record->callnumber = $item->label;
                                    $record->daia_hint->level = 'daia_red';
                                    $record->daia_hint->label = 'not_available';
                                    if(!empty($item->about)) {
                                        $record->about = $item->about;
                                    }
                                    if(!empty($item->chronology->about)) {
                                        $record->chronology = $item->chronology->about;
                                    }
                                    $record->score = 100;
                                    $this->parsed_data->document[0]->item[$key]->availabilityplus = $record;
                                    $break = true;
                                    break;

                                }
                        }
                        if($break) break;
                    }
                }
                if($break) break;
            }
        }

        $response['data'] = $data_org;
        $this->applyCustomChanges();
        $this->determineBestItem();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }

    protected function applyCustomChanges() {

        $specsReader = new SearchSpecsReader();
        $rules = $specsReader->get($this->rules);

        foreach($this->parsed_data->document[0]->item as $key => $item) {
            $rules_applied = [];
            foreach($rules as $rule_key => $rule) {
                $rule_applies = false;
                foreach($rule['conditions'] as $condition) {
                    $match_array = [];
                    $field_content = $this->getObjectPathValue($item, explode('->',$condition['field']));
                    preg_match('|'.$condition['content'].'|',$field_content,$match_array);
                    if(!empty($match_array)){
                        $rule_applies = true;
                    } else {
                        $rule_applies = false;
                        break;
                    }
                }

                if($rule_applies){
                    foreach($rule['actions'] as $action)
                    {
                        $content_old = $this->getObjectPathValue($item, explode('->',$action['field']));
                        $content_new = $content_old;
                        if(!empty($action['pattern'])) {
                            $content_preg =  $this->getObjectPathValue($item, explode('->',$action['content_field']));
                            $content_new = preg_replace('|'.$action['pattern'].'|', $action['replacement'], $content_preg);
                            $this->setObjectPathValue($key, explode('->',$action['field'].'_org'), $content_old);
                            $this->setObjectPathValue($key, explode('->',$action['field']), $content_new);
                        } else if(isset($action['content'])){
                            $content_new = preg_replace('|(.*)|', '$0', $action['content']);
                            $this->setObjectPathValue($key, explode('->',$action['field'].'_org'), $content_old);
                            $this->setObjectPathValue($key, explode('->',$action['field']), $content_new);
                        } else if(!empty($action['function'])) {
                            switch($action['function']) {
                                case 'removeItem' :
                                    $this->parsed_data->document[0]->item[$key]->availabilityplus_org = $this->parsed_data->document[0]->item[$key]->availabilityplus;
                                    unset($this->parsed_data->document[0]->item[$key]->availabilityplus);
                                    break;
                                case 'adjustScore' :
                                    if ($action['score']) {
                                        $this->parsed_data->document[0]->item[$key]->availabilityplus->score_org = $this->parsed_data->document[0]->item[$key]->availabilityplus->score;
                                        $this->parsed_data->document[0]->item[$key]->availabilityplus->score += $action['score'];
                                    }
                                    break;
                            }
                        }
                    }

                    $rules_applied[$rule_key] = $rule;
                }
            }
            if(!empty($rules_applied)) {
                if(!empty($this->parsed_data->document[0]->item[$key]->availabilityplus)) {
                    $this->parsed_data->document[0]->item[$key]->availabilityplus->rules_applied = $rules_applied;
                } else if(!empty($this->parsed_data->document[0]->item[$key]->availabilityplus_org)) {
                    $this->parsed_data->document[0]->item[$key]->availabilityplus_org->rules_applied = $rules_applied;
                }

            }
        }
    }

    protected function getObjectPathValue($item, $path) {
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

    protected function setObjectPathValue($key, $path, $value) {
        switch(count($path)) {
            case 1 :
                $this->parsed_data->document[0]->item[$key]->{$path[0]} = $value;
                break;
            case 2 :
                $this->parsed_data->document[0]->item[$key]->{$path[0]}->{$path[1]} = $value;
                break;
            case 3 :
                $this->parsed_data->document[0]->item[$key]->{$path[0]}->{$path[1]}->{$path[2]} = $value;
                break;
            case 4 :
                $this->parsed_data->document[0]->item[$key]->{$path[0]}->{$path[1]}->{$path[2]}->{$path[3]} = $value;
                break;
            case 5 :
                $this->parsed_data->document[0]->item[$key]->{$path[0]}->{$path[1]}->{$path[2]}->{$path[3]}->{$path[4]} = $value;
                break;
            case 6 :
                $this->parsed_data->document[0]->item[$key]->{$path[0]}->{$path[1]}->{$path[2]}->{$path[3]}->{$path[4]}->{$path[5]} = $value;
                break;
        }
    }

    protected function determineBestItem(){
        foreach($this->parsed_data->document[0]->item as $key => $item) {
            if(empty($this->parsed_data->best_item) || (!empty($item->availabilityplus->score) && $item->availabilityplus->score < $this->parsed_data->best_item->availabilityplus->score)) {
                $this->parsed_data->best_item = $this->parsed_data->document[0]->item[$key];
            }
        }

    }

    protected function generateOrderLink ($action, $doc_id, $item_id, $storage_id) {
        $id = substr($doc_id, strrpos($doc_id, ":") + 1);
        $hmacKeys = explode(':','id:item_id:doc_id');
        $hmacPairs = [
            'id' => $id,
            'doc_id' => $doc_id,
            'item_id' => $item_id
        ];
        return '/vufind/Record/'.$id.'/Hold?doc_id='.urlencode($doc_id).'&item_id='.urlencode($item_id).'&type='.$action.'&storage_id='.urlencode($storage_id).'&hashKey='.$this->hmac->generate($hmacKeys,$hmacPairs);
    }
}

