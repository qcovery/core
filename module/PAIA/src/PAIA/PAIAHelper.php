<?php

namespace PAIA;

use \Zend\View\Helper\AbstractHelper;
use Zend\View\HelperPluginManager as ServiceManager;
use Beluga\Search\Factory\PrimoBackendFactory;
use Beluga\Search\Factory\SolrDefaultBackendFactory;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManagerInterface;
use VuFindSearch\Query\Query;
use \SimpleXMLElement;
use PAIA\Config\PAIAConfigService;

class PAIAHelper extends AbstractHelper implements ServiceLocatorAwareInterface
{

   protected $serviceLocator;

   protected $paiaConfig;

   protected $paiaConfigService;
   
   public function __construct(ServiceManager $sm)
   {
        $this->paiaConfigService = new PAIAConfigService($sm->getServiceLocator()->get('VuFind\SessionManager'));
        $this->paiaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/PAIA.ini'), true);
   }

   /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator() {
        return $this->serviceLocator;
    }
    
	/**
     * Get Results from external DAIA Service
     *
     * @return Array
     */
    public function getDaiaResults($ppn, $list = 0, $language = 'en', $mediatype) {
		if(!empty($this->paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['isil'])) {
			$site = $this->paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['isil'];
		} else {
			$site = 'Default';
		}
		$url_path = $this->paiaConfig['DAIA_'.$this->paiaConfigService->getIsil()]['url'].'availability/'.$ppn.'?apikey='.$this->paiaConfig['DAIA_'.$this->paiaConfigService->getIsil()]['daiaplus_api_key'].'&format=json'.'&site='.$site.'&language='.$language.'&list='.$list.'&mediatype='.urlencode($mediatype);
        echo "<span style='display:none;'>".$url_path."</span>";

        $ch = curl_init();
        $timeout = 0;
        curl_setopt($ch, CURLOPT_URL, $url_path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $daia = curl_exec($ch);
        curl_close($ch);

        $daiaJson = json_decode($daia, true);
		
		if ($daiaJson['document'][0]['item']['daiaplus'] || $daiaJson['document'][0]['daiaplus_best_result']) {
			return $daiaJson;
		}

		$daiaJson = json_decode($daia);
        $results = array();
		$hrefs = array();
        $documentId = '';
        if (isset($daiaJson->document[0]->id)) {
            $documentId = $daiaJson->document[0]->id;
        }

        if (isset($daiaJson->document[0]->item)) {
            foreach ($daiaJson->document[0]->item as $item) {
                $result = array();
                $result['item'] = $item;
                $itemId = '';
                if (isset($item->id)) {
                    $itemId = $item->id;
                }
                
                $remote = NULL;
                $openaccess = NULL;
                $loan = NULL;
                $presentation = NULL;
				
				$include_item = TRUE;
                
                if (isset($item->available)) {
                    foreach ($item->available as $available) {
                        if ($available->service == 'remote') {
							if(!in_array($available->href, $hrefs, TRUE)) {
								$hrefs[] = $available->href;
								$remote = $available;
								$remote->available = true;
								break;
							} else {
								$include_item = FALSE;
							}
                        } else if ($available->service == 'openaccess') {
							if(!in_array($available->href, $hrefs, TRUE)) {
								$hrefs[] = $available->href;
								$openaccess = $available;
								$openaccess->available = true;
								break;
							} else {
								$include_item = FALSE;
							}
                        } else if ($available->service == 'loan') {
                            $loan = $available;
                            $loan->available = true;
                        } else if ($available->service == 'presentation') {
                            $presentation = $available;
                            $presentation->available = true;
                        }
                    }
                }
                if (isset($item->unavailable)) {
                    foreach ($item->unavailable as $unavailable) {
                        if ($unavailable->service == 'remote') {
                            $remote = $unavailable;
                            $remote->available = false;
							break;
                        } else if ($unavailable->service == 'openaccess') {
                            $openaccess = $unavailable;
                            $openaccess->available = false;
							break;
                        } else if ($unavailable->service == 'loan') {
                            $loan = $unavailable;
                            $loan->available = false;
                        } else if ($unavailable->service == 'presentation') {
                            $presentation = $unavailable;
                            $presentation->available = false;
                        }
                    }
                }
                
                $status = '';
                $status_class = '';
				$action = array();
                $score = 1000;
                $label = '';
                $department = '';
                $storage = array();
                $about = '';
                $queue = '';
                $listMoreLink = false;
                
                if ($item->label) {
                    $matches = array();
                    if (preg_match('~^[^:\s]+:~', $item->label, $matches)) {
                        if (isset($matches[0])) {
                            $label = str_ireplace($matches[0], '', $item->label);
                        }
                    } else {
                        $label = $item->label;
                    }
                    $label = $label;
                    $fulllabel = $item->label;
                }
   
                if ($item->department) {
                    if ($item->department->content) {
                        $department = $item->department->content;
                    }
                    if ($item->department->href) {
                        $department = '<a target="_blank" href="'.$item->department->href.'">'.$department.'</a>';
                    }
                }
                
                if ($item->storage) {
                    if ($item->storage->content) {
						$storage['content'] = $item->storage->content;
                    }
                    if ($item->storage->id) {
						$storage['id'] = $item->storage->id;
                    } 
					
					if ($item->storage->href) {
						$storage_additional_info['href'] = $item->storage->href;
						$storage_additional_info['content'] = $this->view->translate('location_hints');
                    } else {
						$storage_additional_info = array();
					}
                }
                
                if ($item->about) {
                    $about = $item->about;
                }
   
                if ($openaccess->available) {
                    $status = $this->view->translate('Get full text');
                    $status_class = 'daia_green';
                    $score = 0;
                    if ($openaccess->limitation) {
                        $status .= ' (';
                        if ($openaccess->limitation[0]->content != '') {
                            $status .= $this->view->translate($openaccess->limitation[0]->content);
                        }
                        if ($openaccess->limitation[0]->id != '') {
                            $idArray = explode('#', $openaccess->limitation[0]->id);
                            if (isset($idArray[1])) {
                                if ($openaccess->limitation[0]->content != '') {
                                    $status .= ' ';
                                }
                                $status .= $this->view->translate($idArray[1]);
                            }
                        }
                        $status .= ')';
                        $status_class = 'daia_orange';
						$score = $score + 5;
                    }
                    if ($openaccess->href) {
						$action['service'] = 'openaccess';
						$action['type'] = 'outlink';
						$action['link_status'] = 'article_access_level';
						$action['generic']['href'] = $openaccess->href;
						$action['generic']['label'] = $this->view->translate('Get full text');
						$action['beluga_core']['href'] = $openaccess->href;
						$action['beluga_core']['label'] = $this->view->translate('Get full text');
                    }
                } else if ($remote->available) {
                    $status = $this->view->translate('Get full text');
                    $status_class = 'daia_green';
                    $score = 10;
                    if ($remote->limitation) {
                        $status .= ' (';
                        if ($remote->limitation[0]->content != '') {
                            $status .= $this->view->translate($remote->limitation[0]->content);
                        }
                        if ($remote->limitation[0]->id != '') {
                            $idArray = explode('#', $remote->limitation[0]->id);
                            if (isset($idArray[1])) {
                                if ($remote->limitation[0]->content != '') {
                                    $status .= ' ';
                                }
                                $status .= $this->view->translate($idArray[1]);
                            }
                        }
                        $status .= ')';
                        $status_class = 'daia_orange';
                        $score = $score + 5;
                    }
                    if ($remote->href) {
						$action['service'] = 'remote';
						$action['type'] = 'outlink';
						$action['link_status'] = 'article_access_level';
						$action['generic']['href'] = $remote->href;
						$action['generic']['label'] = $this->view->translate('Get full text');
						$action['beluga_core']['href'] = $remote->href;
						$action['beluga_core']['label'] = $this->view->translate('Get full text');
                    }
                } else if ($loan->available) {
                    $status_class = 'daia_green';
                    $score = 20;
                    $status .= $this->view->translate('lendable');
					
					
                    if (isset($loan->limitation)) {
                        $status .= ' (';
                        if ($loan->limitation[0]->content != '') {
                            $status .= $this->view->translate($loan->limitation[0]->content);
                        }
                        if ($loan->limitation[0]->id != '') {
                            $idArray = explode('#', $loan->limitation[0]->id);
                            if (isset($idArray[1])) {
                                if ($loan->limitation[0]->content != '') {
                                    $status .= ' ';
                                }
                                $status .= $this->view->translate($idArray[1]);
                            }
                        }
                        $status .= ')';
                        $status_class = 'daia_orange';
                        $score = $score + 5;
                    }
                    if (isset($loan->href) && $loan->limitation[0]->id !== 'http://purl.org/ontology/dso#ApprovalRequired') {
                        if (stristr($loan->href, 'action=order')) {
							$action['service'] = 'loan';
							$action['type'] = 'order';
							$action['documentId'] = $documentId;
							$action['itemId'] = $itemId;
							$action['beluga_core']['href'] = '/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'&type=order';
							$action['beluga_core']['label'] = $this->view->translate('order');
                        } else {
							$action['service'] = 'loan';
							$action['type'] = 'shelf_pickup';
							$action['generic']['label'] = $this->view->translate('shelf_pickup');
							$action['beluga_core']['label'] = $this->view->translate('shelf_pickup');
                        }
                    } else if ($loan->limitation[0]->id === 'http://purl.org/ontology/dso#ApprovalRequired') {
						$action['service'] = 'loan';
						$action['type'] = 'inquiry_required_for_approval';
						$action['generic']['label'] = $this->view->translate('inquiry_required_for_approval');
						$action['beluga_core']['label'] = $this->view->translate('inquiry_required_for_approval');
					} else {
						$action['service'] = 'loan';
						$action['type'] = 'shelf_pickup';
						$action['generic']['label'] = $this->view->translate('shelf_pickup');
						$action['beluga_core']['label'] = $this->view->translate('shelf_pickup');
                    }
                } else if ($presentation->available) {
                    $status_class = 'daia_green';
                    $score = 30;
                    $status .= $this->view->translate('presentation');
                    
                    if (isset($presentation->href)) {
                        if (stristr($presentation->href, 'action=order')) {
							$action['service'] = 'presentaion';
							$action['type'] = 'order';
							$action['documentId'] = $documentId;
							$action['itemId'] = $itemId;
							$action['beluga_core']['href'] = '/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'&type=order';
							$action['beluga_core']['label'] = $this->view->translate('order');
                        } else {
							$status = $this->view->translate('Get full text');
							$action['service'] = 'presentation';
							$action['type'] = 'outlink';
							$action['link_status'] = 'article_access_level';
							$action['generic']['href'] = $presentation->href;
							$action['generic']['label'] = $this->view->translate('Get full text');
							$action['beluga_core']['href'] = $presentation->href;
							$action['beluga_core']['label'] = $this->view->translate('Get full text');
                        }
                    } else {
						$action['service'] = 'presentation';
						$action['type'] = 'shelf_pickup';
						$action['generic']['label'] = $this->view->translate('shelf_pickup');
						$action['beluga_core']['label'] = $this->view->translate('shelf_pickup');
                    }
                    
                    if (isset($presentation->limitation)) {
                        $status .= ' (';
                        if ($presentation->limitation[0]->content != '') {
                            $status .= $presentation->limitation[0]->content;
                        }
                        if ($presentation->limitation[0]->id != '') {
                            $idArray = explode('#', $presentation->limitation[0]->id);
                            if (isset($idArray[1])) {
                                if ($presentation->limitation[0]->content != '') {
                                    $status .= ' ';
                                }
                                $status .= $this->view->translate($idArray[1]);
                            }
                        }
                        $status .= ')';
                        $status_class = 'daia_orange';
                        $score = $score + 5;
                    }
                    
                } else {
                    if (!$loan->available) {
                        if (isset($loan->href)) {
                            if (isset($loan->expected)) {
                                $parsedExpected = date_parse($loan->expected);
                                $status = $this->view->translate('on_loan_until').' '.$parsedExpected['day'].'.'.$parsedExpected['month'].'.'.$parsedExpected['year'];
                            } else {
                                $status = $this->view->translate('on_loan');
                            }
                            $status_class = 'daia_orange';
                            $score = 40;
                            if (isset($loan->queue)) {
								$queue = $loan->queue.' '.$this->view->translate('Recall');
								if ($loan->queue != 1) {
									$queue = $loan->queue.' '.$this->view->translate('Recalls');
								}
                                $score = $score + $loan->queue;
                            }
                            if (stristr($loan->href, 'action=reserve')) {
								$action['service'] = 'loan';
								$action['type'] = 'recall';
								$action['documentId'] = $documentId;
								$action['itemId'] = $itemId;
								$action['beluga_core']['href'] = '/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'&type=recall';
								$action['beluga_core']['label'] = $this->view->translate('recall');
                            }
                            if (isset($loan->limitation)) {
                                $status .= ' (';
                                if ($loan->limitation[0]->content != '') {
                                    $status .= $loan->limitation[0]->content;
                                }
                                if ($loan->limitation[0]->id != '') {
                                    $idArray = explode('#', $loan->limitation[0]->id);
                                    if (isset($idArray[1])) {
                                        if ($loan->limitation[0]->content != '') {
                                            $status .= ' ';
                                        }
                                        $status .= $this->view->translate($idArray[1]);
                                    }
                                }
                                $status .= ')';
                                $status_class = 'daia_orange';
                                $score = $score + 5;
                            }
                        } else if (!$presentation->available && isset($presentation->href)) {
                            if (isset($presentation->expected)) {
                                $parsedExpected = date_parse($presentation->expected);
                                $status = $this->view->translate('on_loan_until').' '.$parsedExpected['day'].'.'.$parsedExpected['month'].'.'.$parsedExpected['year'];
                            } else {
                                $status = $this->view->translate('on_loan');
                            }
                            $status_class = 'daia_orange';
                            $score = 50;
                            if (isset($presentation->queue)) {
								$queue = $loan->queue.' '.$this->view->translate('Recall');
								if ($loan->queue != 1) {
									$queue = $loan->queue.' '.$this->view->translate('Recalls');
								}
                                $score = $score + $loan->queue;
                            }
                            if (stristr($presentation->href, 'action=reserve')) {
								$action['service'] = 'presentation';
								$action['type'] = 'recall';
								$action['documentId'] = $documentId;
								$action['itemId'] = $itemId;
								$action['beluga_core']['href'] = '/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'&type=recall';
								$action['beluga_core']['label'] = $this->view->translate('recall');
                            }
                            if (isset($presentation->limitation)) {
                                $status .= ' (';
                                if ($presentation->limitation[0]->content != '') {
                                    $status .= $presentation->limitation[0]->content;
                                }
                                if ($presentation->limitation[0]->id != '') {
                                    $idArray = explode('#', $presentation->limitation[0]->id);
                                    if (isset($idArray[1])) {
                                        if ($presentation->limitation[0]->content != '') {
                                            $status .= ' ';
                                        }
                                        $status .= $this->view->translate($idArray[1]);
                                    }
                                }
                                $status .= ')';
                                $status_class = 'daia_orange';
                                $score = $score + 5;
                            }
                        } else {
							if (isset($presentation->expected)) {
								$parsedExpected = date_parse($presentation->expected);
								$status = $this->view->translate('not_available_until').' '.$parsedExpected['day'].'.'.$parsedExpected['month'].'.'.$parsedExpected['year'];
								$score = 90;
							} else {
								$status = $this->view->translate('not_available');
								$score = 100;
							}
						
                            $status_class = 'daia_red';
                        }
                    } else {
						
						if (isset($loan->expected)) {
							$parsedExpected = date_parse($loan->expected);
							$status = $this->view->translate('not_available_until').' '.$parsedExpected['day'].'.'.$parsedExpected['month'].'.'.$parsedExpected['year'];
							$score = 90;
						} else {
							$status = $this->view->translate('not_available');
							$score = 100;
						}
						
                        $status_class = 'daia_red';
                    }
                }
				
				if(($marcField951aValue == 'MC' || $marcField951aValue == 'ST')&& $status_class == 'daia_red') {
					$status = $this->view->translate('please select a volume');
					$status_class = 'daia_orange';
				}
				
                $result['daiaplus']['status'] = $status;
				$result['daiaplus']['status_class'] = $status_class;
                $result['daiaplus']['action'] = $action;
                $result['daiaplus']['action_org'] = $action;
                $result['daiaplus']['score'] = $score;
                $result['daiaplus']['label'] = $label;
                $result['daiaplus']['fulllabel'] = $fulllabel;
                $result['daiaplus']['department'] = $department;
                $result['daiaplus']['storage'] = $storage;
                $result['daiaplus']['storage_org'] = $storage;
				$result['daiaplus']['storage_additional_info'] = $storage_additional_info;
                $result['daiaplus']['showDepartment'] = false;
                $result['daiaplus']['about'] = $about;
                $result['daiaplus']['about_org'] = $about;
                $result['daiaplus']['queue'] = $queue;
                $result['daiaplus']['listMoreLink'] = $listMoreLink;
				
				if ($include_item == TRUE) {
					$results[] = $result;
				}
            }
        }
        
        if (empty($results)) {
            if (isset($daiaJson->message[0]->content)) {
                $result = array();
                $result['daiaplus']['status'] = $this->view->translate('daiaNoResult');
                $result['daiaplus']['status_class'] = 'daia_red';
                $result['daiaplus']['action'] = '';
                $result['daiaplus']['score'] = 3;
                $result['daiaplus']['label'] = '';
                $result['daiaplus']['fulllabel'] = '';
                $result['daiaplus']['department'] = '';
                $result['daiaplus']['storage'] = '';
                $result['daiaplus']['storage_org'] = '';
				$result['daiaplus']['storage_additional_info'] = array();
                $result['daiaplus']['showDepartment'] = false;
                $result['daiaplus']['about'] = '';
                $result['daiaplus']['queue'] = '';
                $result['daiaplus']['listMoreLink'] = false;
                $results[] = $result;
            }
        }
		
	if ($list) {
		$bestResult;
		foreach ($results as $result) {
			if (!$bestResult) {
				$bestResult = $result;
			} else {
				if ($result['daiaplus']['score'] < $bestResult['daiaplus']['score']) {
					$bestResult = $result;
				}
			}
		}
		if (sizeof($results) > 1) {
			$bestResult['daiaplus']['list_more_link'] = true;
		}
		$results = $this->makeDaiaConform($results, $daiaJson, $bestResult);
		$results = json_decode($results, true);
		return $results;
	} else {
		
		$results = $this->makeDaiaConform($results, json_decode($daia, true)); // convert again to json, as makeDaiaConform() can not use object representation of DAIA results.
		$results = json_decode($results, true);
		return $results;
	}
    }
	
	public function makeDaiaConform ($results, $daiaJson, $bestResult = array()) {
		$daia_conform_results = array ();

		if (is_array($daiaJson)) {
            foreach ($daiaJson as $key => $value) {
                if ($key == "document") {
                    if (!empty($value)) {
                        foreach ($value as $document_key => $document_item) {
                            if ($document_key == 0) {
                                foreach ($document_item as $document_item_key => $document_item_value) {
                                    if ($document_item_key == "item") {
                                        $results = json_decode(json_encode($results), true);
                                        foreach ($results as $result) {
                                            $result_item = $result['item'];
                                            $result_item['daiaplus'] = $result['daiaplus'];
                                            $daia_conform_results[$key][$document_key][$document_item_key][] = $result_item;
                                        }
                                    } else {
                                        $daia_conform_results[$key][$document_key][$document_item_key] = $document_item_value;
                                    }
                                }
                                if ($bestResult) {
                                    $bestResult = json_decode(json_encode($bestResult), true);
                                    $bestResult_item = $bestResult['item'];
                                    $bestResult_item['daiaplus'] = $bestResult['daiaplus'];
                                    $daia_conform_results[$key][$document_key]['daiaplus_best_result'] = $bestResult_item;
                                }
                            } else {
                                $daia_conform_results[$key][$document_key] = $document_item;
                            }
                        }
                    } else {
                        $daia_conform_results[$key] = $value;
                    }
                } else {
                    $daia_conform_results[$key] = $value;
                }
            }
        }
		
		return  json_encode($daia_conform_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
    
	/**
     * Get Results from external E-Availability Service
     *
     * @return Array
     */
	//TODO: Complete function once external service is available
	public function getElectronicAvailability($ppn, $openUrl, $url_access, $url_access_level, $first_matching_issn, $GVKlink, $doi, $list, $mediatype, $language) {
		if(!empty($this->paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['isil'])) {
			$site = $this->paiaConfig[$this->paiaConfigService->getPaiaGlobalKey()]['isil'];
		} else {
			$site = 'Default';
		}
        $url_path = $this->paiaConfig['DAIA_'.$this->paiaConfigService->getIsil()]['url'].'electronicavailability/'.$ppn.'?apikey='.$this->paiaConfig['DAIA_'.$this->paiaConfigService->getIsil()]['daiaplus_api_key'].'&openurl='.urlencode($openUrl).'&url_access='.$url_access.'&url_access_level='.$url_access_level.'&first_matching_issn='.$first_matching_issn.'&GVKlink='.$GVKlink.'&doi='.urlencode($doi).'&list='.$list.'&mediatype='.urlencode($mediatype).'&language='.$language.'&site='.$site.'&format=json';

        echo "<span style='display:none;'>".$url_path."</span>";

        $ch = curl_init();
        $timeout = 0;
        curl_setopt($ch, CURLOPT_URL, $url_path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $e_availability = curl_exec($ch);
        curl_close($ch);

        $e_availability = json_decode($e_availability, true);
		
		return $e_availability;
	}
	
	public function getContext($driver){
		
		$formats = "";
		$collection_details = array();
		$ppnlink = "";
		$marcField951aValue = "";
		$context_array = array();
		$doi = "";
		
		if (method_exists($driver, 'getFormats')) {
			$formats = $driver->tryMethod('getFormats');
		}
		
		if (method_exists($driver, 'getCollectionDetails')) {
			$collection_details = $driver->tryMethod('getCollectionDetails');
		}
		
		if (method_exists($driver, 'getPPNLink')) {
			$ppnlink = $driver->tryMethod('getPPNLink');
		}

		if (method_exists($driver, 'getMarcRecord')) {
			
			$field_values = array();
			$fields = $driver->getMarcRecord()->getFields('912');
			if(!empty($fields) && empty($collection_details)) {
				foreach($fields as $subfield) {
					foreach($subfield->getSubfields('a') as $subfield_content) {
						$field_values[] = $subfield_content->getData();
					}
				}
			}
			$collection_details = $field_values;
			
			$field_values = array();
			$fields = $driver->getMarcRecord()->getFields('773');
			if(!empty($fields) && empty($ppnlink)) {
				foreach($fields as $subfield) {
					foreach($subfield->getSubfields('w') as $subfield_content) {
						$field_values[] = $subfield_content->getData();
						break;
					}
				}
				$ppnlink = (empty($field_values[0])) ? '' : $field_values[0];
				$ppnlink = str_replace("(DE-601)","",$ppnlink);
			}
			
			$marcField951aValue = "";
			$marcField951 = $driver->getMarcRecord()->getField('951');
			if(!empty($marcField951)) {
				$marcField951aValue = $marcField951->getSubfield('a')->getData();
			}
				
			$field_values = array();
			$fields = $driver->getMarcRecord()->getFields('024');
			if(!empty($fields) && empty($doi)) {
				foreach($fields as $field) {
					if(strpos($field,"_2doi") == true) {
						$doi = $field->getSubfield('a')->getData();
						break;
					}
				}
			}
		}
			
		$daiaPPN = '';
		if ($marcField951aValue == 'MC' || $marcField951aValue == 'ST') {
			$daiaPPN = '';
		} else if ($marcField951aValue == 'AI' && !empty($ppnlink)) {
			$daiaPPN = $ppnlink;
		} else if (empty($daiaPPN) && in_array('Article', $formats, TRUE)) {
			$daiaPPN = "";
		} else 	{
			$daiaPPN = $driver->getUniqueID();
		}
		
		$context_array['daiaPPN'] = $daiaPPN;
		$context_array['formats'] = $formats;
		$context_array['collection_details'] = $collection_details;
		$context_array['ppnlink'] = $ppnlink;
		$context_array['marcField951aValue'] = $marcField951aValue;
		$context_array['doi'] = $doi;
		
		return $context_array;
	}

	public function hasMultipleLoginSources() {
	    return $this->paiaConfigService->hasMultipleLoginSources();
    }

    public function getMultipleLoginSources() {
	    return $this->paiaConfigService->getMultipleLoginSources();
    }

    public function getPaiaGlobalKey() {
	    return $this->paiaConfigService->getPaiaGlobalKey();
    }
}

?>
