<?php

namespace AvailabilityPlus\Resolver\Driver;

use stdClass;
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
        $headers->addHeaderLine('Accept-Language', $this->language);
        return $this->httpClient->setUri($url)->send()->getBody();
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
        if (isset($data->document[0]->item) && is_iterable($data->document[0]->item)) {
            foreach($data->document[0]->item as $key => $item) {

                $item_services['available']['openaccess'] = [];
                $item_services['available']['remote'] = [];
                $item_services['available']['loan'] = [];
                $item_services['available']['presentation'] = [];
                $item_services['available']['fallback'][] = 'fallback';

                if (isset($item->available) && is_iterable($item->available)) {
                    foreach($item->available as $service) {
                        if (isset($service->service)) {
                            $item_services['available'][$service->service][] = $service;
                            $item_services['available']['fallback'] = [];
                        }
                    }
                }

                if (isset($item->unavailable) && is_iterable($item->unavailable)) {
                    foreach($item->unavailable as $service) {
                        if(count(get_object_vars($service)) > 1 && isset($service->service)) {
                            $item_services['available'][$service->service][] = $service;
                        }
                    }
                }

                $break = false;
                foreach($item_services['available'] as $service_key => $service_group) {
                    foreach($service_group as $service_content) {
                        $record = (object)[];
                        if(!empty($service_content)) {
                            $record->id = $data->document[0]->id ?? '';
                            $record->ppn = substr($record->id, strrpos($record->id, ":") + 1);
                            if(isset($item->id) && str_contains($item->id, "epn:")) {
                                $record->epn_id = $item->id;
                                $record->epn = substr($item->id, strrpos($item->id, ":") + 1);
                            } elseif (isset($item->{'temporary-hack-do-not-use'})) {
                                $record->epn = $item->{'temporary-hack-do-not-use'};
                            }
                            if(isset($item->id) && str_contains($item->id, ":bar:")) {
                                $record->barcode_id = $item->id;
                                $record->barcode = substr($item->id, strrpos($item->id, "$") + 1);
                            } elseif(isset($service_content->href)) {
                                $query = parse_url($service_content->href, PHP_URL_QUERY);
                                $query_array = [];
                                parse_str($query, $query_array);
                                $record->barcode = $query_array['bar'] ?? '';
                            }
                            $record->service = $service_key;
                            if (!isset($record->daia_action)) {
                                $record->daia_action = new stdClass;
                            }
                            switch($service_key) {
                                case 'openaccess':
                                case 'remote':
                                    if((isset($service_content->href) && !in_array($service_content->href, $urls)) || (isset($this->resolverConfig->hide_url_duplicates) && !$this->resolverConfig->hide_url_duplicates)) {
                                        $record->daia_action->level = ($service_key === 'openaccess' ? 'FreeAccess link_external' : 'LicensedAccess link_external');
                                        if (isset($service_content->title) && !empty($service_content->title)) $record->daia_action->title = $service_content->title;
                                        $record->daia_action->label = ($service_key === 'openaccess' ? 'FreeAccess' : 'LicensedAccess');
                                        $record->daia_action->url = $service_content->href ?? '';
                                        $urls[] = $record->daia_action->url;
                                        if (isset($item->about) && !empty($item->about)) {
                                            $record->about = $item->about;
                                        }
                                        if (isset($item->chronology->about) && !empty($item->chronology->about)) {
                                            $record->chronology = $item->chronology->about;
                                        }
                                        $record->score = ($service_key === 'openaccess' ? 0 : 10);
                                        if ($service_key === 'remote') {
                                            if(isset($service_content->limitation[0]->content) && !empty($service_content->limitation[0]->content)) {
                                                $record->daia_action->limitation = $service_content->limitation[0]->content;
                                                $record->daia_action->limitation_label = str_replace(" ", "", $service_content->limitation[0]->content);
                                                $record->score = 15;
                                            }
                                        }
                                        if (!isset($this->parsed_data->document[0]->item[$key]->availabilityplus) || empty($this->parsed_data->document[0]->item[$key]->availabilityplus)) {
                                            $this->parsed_data->document[0]->item[$key]->availabilityplus = $record;
                                        }
                                        $this->parsed_data->document[0]->item[$key]->availabilityplus->daia_action_array[] = $record->daia_action;
                                    }
                                case 'loan':
                                case 'presentation':
                                    if(empty($item_services['available']['openaccess']) && empty($item_services['available']['remote'])) {
                                        $record->score = ($service_key === 'loan' ? 20 : 30);
                                        if(isset($item->storage->href) && !empty($item->storage->href)){
                                            $this->setStorage($record, 'link_external', $item->storage->content ?? '', $item->storage->href);
                                        } elseif(isset($item->storage->id) && !empty($item->storage->id)){
                                            $this->setStorage($record, 'link_external', $item->storage->content ?? '', $item->storage->id);
                                        } else {
                                            $this->setStorage($record, null, 'unknown_location', null);
                                        }
                                        if(isset($item->label) && !empty($item->label)) $record->callnumber = $item->label;
                                        if(isset($service_content->limitation[0]->id) && !empty($service_content->limitation[0]->id)) {
                                            $limitation = str_replace(' ', '', substr($service_content->limitation[0]->id, strpos($service_content->limitation[0]->id, "#") + 1));
                                            $this->setDaiaHint($record, $limitation, ($service_content->service ?? '').$limitation, null);
                                            $record->score += 5;
                                        } elseif(isset($service_content->limitation[0]->content) && !empty($service_content->limitation[0]->content)) {
                                            $limitation = str_replace(' ', '', $service_content->limitation[0]->content);
                                            $this->setDaiaHint($record, $limitation, ($service_content->service ?? '').$limitation, null);
                                            $record->score += 5;
                                        } elseif(isset($service_content->expected) && !empty($service_content->expected)) {
                                            $date = date_create($service_content->expected);
                                            $this->setDaiaHint($record, "daia_orange", 'on_loan_until', date_format($date, "d.m.Y"));
                                            $record->score += 20;
                                        } elseif(isset($service_content->queue)) {
                                            $this->setDaiaHint($record, "daia_orange", 'on_loan', null);
                                            $record->score += 20;
                                        }  else {
                                            $this->setDaiaHint($record, "daia_green", $service_content->service ?? '', null);
                                        }
                                        if(isset($service_content->href) && !empty($service_content->href)) {
                                            $record->daia_action->level = 'internal_link';
                                            $url_components = parse_url($service_content->href);
                                            parse_str($url_components['query'] ?? '', $params);
                                            $action = $params['action'] ?? '';
                                            if ($action == 'reserve') $action = 'recall';
                                            $record->daia_action->label = $action;
                                            $record->daia_action->url = $this->generateOrderLink($action, $data->document[0]->id ?? '', $item->id ?? '', $item->storage->id ?? '');
                                        } else {
                                            $record->daia_action->label = ($service_content->service ?? '').'_default_action'.($limitation ?? '');
                                        }
                                        if(isset($service_content->queue)) {
                                            if (!isset($record->queue)) {
                                                $record->queue = new stdClass;
                                            }
                                            $record->queue->length = $service_content->queue;
                                            $record->queue->label = 'Recall';
                                            if($service_content->queue != 1) {
                                                $record->queue->label .= 's';
                                            }
                                            $record->score += $service_content->queue;
                                        }
                                        if(isset($item->about) && !empty($item->about)) {
                                            $record->about = $item->about;
                                        }
                                        if(isset($item->chronology->about) && !empty($item->chronology->about)) {
                                            $record->chronology = $item->chronology->about;
                                        }
                                        $this->parsed_data->document[0]->item[$key]->availabilityplus = $record;
                                        $break = true;
                                        break;
                                    }
                                case 'fallback':
                                    if(empty($item_services['available']['openaccess']) && empty($item_services['available']['remote']) && empty($item_services['available']['loan']) && empty($item_services['available']['presentation'])) {
                                        if(isset($item->storage->id) && !empty($item->storage->id)){
                                            $this->setStorage($record, 'link_external', $item->storage->content ?? '', $item->storage->id);
                                        } else {
                                            $this->setStorage($record, null, 'unknown_location', null);
                                        }
                                        if(isset($item->label) && !empty($item->label)) $record->callnumber = $item->label;
                                        $this->setDaiaHint($record, 'daia_red', 'not_available', null);
                                        if(isset($item->about) && !empty($item->about)) {
                                            $record->about = $item->about;
                                        }
                                        if(isset($item->chronology->about) && !empty($item->chronology->about)) {
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
        }

        $response['data'] = $data_org;
        $this->applyCustomChanges();
        $this->determineBestItem();
        $response['parsed_data'] = $this->parsed_data;
        return $response;
    }

    /**
     * Add `level`, `label` and `url` parameters to the `storage` property on the given object.
     * Creates a new object and `storage` property if not present.
     * `level`, `label` and `url` are only added to the object when the variables are set and not null, otherwise
     * they are ignored.
     * @param $obj stdClass The object that the storage will be added to.
     * @param $level string
     * @param $label string
     * @param $url string
     * @return void
     */
    private function setStorage($obj, $level, $label, $url): void
    {
        if (!isset($obj)) {
            $obj = new stdClass;
        }
        if (!isset($obj->storage)) {
            $obj->storage = new stdClass;
        }
        if (isset($level)) {
            $obj->storage->level = $level;
        }
        if (isset($label)) {
            $obj->storage->label = $label;
        }
        if (isset($url)) {
            $obj->storage->url = $url;
        }
    }

    /**
     * Add `level`, `label` and `label_date` parameters to the `daia_hint` property on the given object.
     * Creates a new object and `daia_hint` property if not present.
     * `level`, `label` and `label_date` are only added to the object when the variables are set and not null, otherwise
     * they are ignored.
     * @param $obj stdClass The object that the storage will be added to.
     * @param $level string
     * @param $label string
     * @param $label_date string
     * @return void
     */
    private function setDaiaHint($obj, $level, $label, $label_date): void
    {
        if (!isset($obj)) {
            $obj = new stdClass;
        }
        if (!isset($obj->daia_hint)) {
            $obj->daia_hint = new stdClass;
        }
        if (isset($level)) {
            $obj->daia_hint->level = $level;
        }
        if (isset($label)) {
            $obj->daia_hint->label = $label;
        }
        if (isset($label_date)) {
            $obj->daia_hint->label_date = $label_date;
        }
    }

    protected function applyCustomChanges() {

        $specsReader = new SearchSpecsReader();
        $rules = $specsReader->get($this->rules);

        if (isset($this->parsed_data->document[0]->item) && is_iterable($this->parsed_data->document[0]->item)) {
            foreach($this->parsed_data->document[0]->item as $key => $item) {
                $rules_applied = [];
                foreach($rules as $rule_key => $rule) {
                    $rule_applies = false;
                    if (array_key_exists('conditions', $rule)) {
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
                    }

                    if($rule_applies){
                        foreach($rule['actions'] as $action)
                        {
                            $content_old = $this->getObjectPathValue($item, explode('->',$action['field']));
                            $content_new = $content_old;
                            if(isset($action['pattern']) && !empty($action['pattern'])) {
                                $content_preg =  $this->getObjectPathValue($item, explode('->',$action['content_field']));
                                $content_new = preg_replace('|'.$action['pattern'].'|', $action['replacement'], $content_preg);
                                $this->setObjectPathValue($key, explode('->',$action['field'].'_org'), $content_old);
                                $this->setObjectPathValue($key, explode('->',$action['field']), $content_new);
                            } else if(isset($action['content'])){
                                $content_new = preg_replace('|(.*)|', '$0', $action['content']);
                                $this->setObjectPathValue($key, explode('->',$action['field'].'_org'), $content_old);
                                $this->setObjectPathValue($key, explode('->',$action['field']), $content_new);
                            } else if(isset($action['function']) && !empty($action['function'])) {
                                switch($action['function']) {
                                    case 'removeItem' :
                                        $this->parsed_data->document[0]->item[$key]->availabilityplus_org = $this->parsed_data->document[0]->item[$key]->availabilityplus ?? null;
                                        unset($this->parsed_data->document[0]->item[$key]->availabilityplus);
                                        break;
                                    case 'adjustScore' :
                                        if (isset($action['score'])) {
                                            $this->parsed_data->document[0]->item[$key]->availabilityplus->score_org = $this->parsed_data->document[0]->item[$key]->availabilityplus->score ?? 0;
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
                    if(isset($this->parsed_data->document[0]->item[$key]->availabilityplus) && !empty($this->parsed_data->document[0]->item[$key]->availabilityplus)) {
                        $this->parsed_data->document[0]->item[$key]->availabilityplus->rules_applied = $rules_applied;
                    } else if(isset($this->parsed_data->document[0]->item[$key]->availabilityplus_org) && !empty($this->parsed_data->document[0]->item[$key]->availabilityplus_org)) {
                        $this->parsed_data->document[0]->item[$key]->availabilityplus_org->rules_applied = $rules_applied;
                    }

                }
            }
        }
    }

    protected function getObjectPathValue($item, $path) {
        while ($property = array_shift($path)) {
            $item = $item->$property ?? null;
        }
        return $item ?? '';
    }

    protected function setObjectPathValue($key, $path, $value) {
        $item = $this->parsed_data->document[0]->item[$key];
        while ($property = array_shift($path)) {
            if ((!isset($item->$property) || !is_object($item->$property)) && count($path) > 0) {
                $item->$property = new stdClass;
            }
            if (count($path) > 0) {
                $item = $item->$property;
            } else {
                $item->$property = $value;
            }
        }
    }

    protected function determineBestItem(){
        if (isset($this->parsed_data->document[0]->item) && is_iterable($this->parsed_data->document[0]->item)) {
            foreach($this->parsed_data->document[0]->item as $key => $item) {
                if(empty($this->parsed_data->best_item) || (!empty($item->availabilityplus->score) && $item->availabilityplus->score < $this->parsed_data->best_item->availabilityplus->score)) {
                    $this->parsed_data->best_item = $this->parsed_data->document[0]->item[$key];
                }
            }
        }
    }

    protected function generateOrderLink($action, $doc_id, $item_id, $storage_id) {
        $id = substr($doc_id, strrpos($doc_id, ":") + 1);
        $hmacKeys = explode(':','id:item_id:doc_id');
        $hmacPairs = [
            'id' => $id,
            'doc_id' => $doc_id,
            'item_id' => $item_id
        ];
        return '/vufind/Record/'.$id.'/Hold?doc_id='.urlencode($doc_id).'&item_id='.urlencode($item_id).'&type='.$action.'&storage_id='.urlencode($storage_id).'&hashKey='.$this->hmac->generate($hmacKeys, $hmacPairs);
    }
}

