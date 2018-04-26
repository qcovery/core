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

class PAIAHelper extends AbstractHelper implements ServiceLocatorAwareInterface
{

   protected $serviceLocator;

   protected $paiaConfig;
   protected $ftcheckConfig;
   
   public function __construct()
   {
        $this->paiaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/PAIA.ini'), true);
		$this->ftcheckConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/fulltext-availability-check.ini'), true);
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
	//TODO: Currently only working with DAIAplus Service - DAIAplus Service needs to be set up to conform to DAIA request specifications
    public function getDaiaResults($ppn, $list = false, $language = 'en') {
		if(!empty($this->paiaConfig['Global']['isil'])) {
			$site = $this->paiaConfig['Global']['isil'];
		} else {
			$site = 'Default';
		}
		$url_path = $this->paiaConfig['DAIA']['url'].'?id=ppn:'.$ppn.'&format=json'.'&site='.$site.'&language='.$language.'&list='.$list;
		$daia = file_get_contents($url_path);
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
                $status_style = '';
                $info = '';
                $score = 1000;
                $label = '';
                $department = '';
                $storage = '';
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
                        $storage = $item->storage->content;
                    }
                    if ($item->storage->id) {
                        $storage = '<a target="_blank" href="'.$item->storage->id.'">'.$storage.'</a>';
                    } 
					
					if ($item->storage->href) {
                        $storage_additional_info = '<a target="_blank" href="'.$item->storage->href.'">'.$this->view->translate('Hinweise zum Standort').'</a>';
                    } else {
						$storage_additional_info = '';
					}
                }
                
                if ($item->about) {
                    $about = $item->about;
                }
   
                if ($openaccess->available) {
                    $status = $this->view->translate('online verfügbar');
                    $status_style = 'color: #3DA22D; font-weight: bold;';
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
                        $status_style = 'color: #EB8E12; font-weight: bold;';
						$score = $score + 5;
                    }
                    if ($openaccess->href) {
                        $info = '<a target="_blank" class="article_access_level" href="'.$openaccess->href.'">'.$this->view->translate('ansehen').'</a>';
                    }
                } else if ($remote->available) {
                    $status = $this->view->translate('online verfügbar');
                    $status_style = 'color: #3DA22D; font-weight: bold;';
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
                        $status_style = 'color: #EB8E12; font-weight: bold;';
                        $score = $score + 5;
                    }
                    if ($remote->href) {
                        $info = '<a target="_blank" class="article_access_level" href="'.$remote->href.'">ansehen</a>';
                    }
                } else if ($loan->available) {
                    $status_style = 'color: #3DA22D; font-weight: bold;';
                    $score = 20;
                    $status .= $this->view->translate('ausleihbar');
					
					
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
                        $status_style = 'color: #EB8E12; font-weight: bold;';
                        $score = $score + 5;
                    }
                    if (isset($loan->href) && $loan->limitation[0]->id !== 'http://purl.org/ontology/dso#ApprovalRequired') {
                        if (stristr($loan->href, 'action=order')) {
                            $info .= '<a target="_blank" href="/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'&type=order">'.$this->view->translate('bestellen').'</a>';
                        } else {
                            $info .= $this->view->translate('bitte am Standort entnehmen');
                        }
                    } else if ($loan->limitation[0]->id === 'http://purl.org/ontology/dso#ApprovalRequired') {
                            $info .= $this->view->translate('inquiry_required_for_approval');
					} else {
                        $info .= $this->view->translate('bitte am Standort entnehmen');
                    }
                } else if ($presentation->available) {
                    $status_style = 'color: #3DA22D; font-weight: bold;';
                    $score = 30;
                    $status .= $this->view->translate('vor Ort benutzbar');
                    
                    if (isset($presentation->href)) {
                        if (stristr($presentation->href, 'action=order')) {
                            $info .= '<a target="_blank" href="/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'&type=order">'.$this->view->translate('bestellen').'</a>'; // PAIA
                        } else {
                            $status = $this->view->translate('online verfügbar');
                            $info .= '<a target="_blank" class="article_access_level" href="'.$presentation->href.'">'.$this->view->translate('Zum Volltext').'</a>';
                        }
                    } else {
                        $info .= $this->view->translate('bitte am Standort entnehmen');
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
                        $status_style = 'color: #EB8E12; font-weight: bold;';
                        $score = $score + 5;
                    }
                    
                } else {
                    if (!$loan->available) {
                        if (isset($loan->href)) {
                            if (isset($loan->expected)) {
                                $parsedExpected = date_parse($loan->expected);
                                $status = $this->view->translate('ausgeliehen bis').' '.$parsedExpected['day'].'.'.$parsedExpected['month'].'.'.$parsedExpected['year'];
                            } else {
                                $status = $this->view->translate('ausgeliehen');
                            }
                            $status_style = 'color: #EB8E12; font-weight: bold;';
                            $score = 40;
                            if (isset($loan->queue)) {
                                $queue = $loan->queue.' '.$this->view->translate('Vormerkung');
                                if ($loan->queue != 1) {
                                    $queue .= $this->view->translate('Vormerkungen_plural_suffix');
                                }
                                $score = $score + $loan->queue;
                            }
                            if (stristr($loan->href, 'action=reserve')) {
                                $info = '<a target="_blank" href="/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'&type=recall">'.$this->view->translate('vormerken').'</a>'; // PAIA
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
                                $status_style = 'color: #EB8E12; font-weight: bold;';
                                $score = $score + 5;
                            }
                        } else if (!$presentation->available && isset($presentation->href)) {
                            if (isset($presentation->expected)) {
                                $parsedExpected = date_parse($presentation->expected);
                                $status = $this->view->translate('ausgeliehen bis').' '.$parsedExpected['day'].'.'.$parsedExpected['month'].'.'.$parsedExpected['year'];
                            } else {
                                $status = $this->view->translate('ausgeliehen');
                            }
                            $status_style = 'color: #EB8E12; font-weight: bold;';
                            $score = 50;
                            if (isset($presentation->queue)) {
                                $queue = $presentation->queue.' '.$this->view->translate('Vormerkung');
                                if ($presentation->queue != 1) {
                                    $queue .= $this->view->translate('Vormerkungen_plural_suffix');
                                }
                                $score = $score + $loan->queue;
                            }
                            if (stristr($presentation->href, 'action=reserve')) {
                                $info = '<a target="_blank" href="/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'&type=recall">vormerken</a>'; // PAIA

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
                                $status_style = 'color: #EB8E12; font-weight: bold;';
                                $score = $score + 5;
                            }
                        } else {
							if (isset($presentation->expected)) {
								$parsedExpected = date_parse($presentation->expected);
								$status = $this->view->translate('derzeit nicht verfügbar, verfügbar ab').' '.$parsedExpected['day'].'.'.$parsedExpected['month'].'.'.$parsedExpected['year'];
								$score = 90;
							} else {
								$status = $this->view->translate('derzeit nicht verfügbar');
								$score = 100;
							}
						
                            $status_style = 'color: #ff0000; font-weight: bold;';
                        }
                    } else {
						
						if (isset($loan->expected)) {
							$parsedExpected = date_parse($loan->expected);
							$status = $this->view->translate('derzeit nicht verfügbar, verfügbar ab').' '.$parsedExpected['day'].'.'.$parsedExpected['month'].'.'.$parsedExpected['year'];
							$score = 90;
						} else {
							$status = $this->view->translate('derzeit nicht verfügbar');
							$score = 100;
						}
						
                        $status_style = 'color: #ff0000; font-weight: bold;';
                    }
                    
                    // *** dev!
                    //$info .= $info = '<br/><br/><a target="_blank" href="/vufind/MyResearch/PlaceHold?documentId='.urlencode($documentId).'&itemId='.urlencode($itemId).'">vormerken dev</a>'; // PAIA
                    // *** dev!
                }
				
				if(($marcField951aValue == 'MC' || $marcField951aValue == 'ST')&& $status_style == 'color: #ff0000; font-weight: bold;') {
					$status = $this->view->translate('please select a volume');
					$status_style = 'color: #EB8E12; font-weight: bold;';
				}
				
                $result['daiaplus']['status'] = $status;
				$result['daiaplus']['status_style'] = $status_style;
                $result['daiaplus']['info'] = $info;
                $result['daiaplus']['info_org'] = $info;
                $result['daiaplus']['score'] = $score;
                $result['daiaplus']['label'] = $label;
                $result['daiaplus']['fulllabel'] = $fulllabel;
                $result['daiaplus']['department'] = $department;
                $result['daiaplus']['storage'] = $storage;
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
                $result['daiaplus']['status_style'] = 'color: #ff0000; font-weight: bold;';
                $result['daiaplus']['info'] = '';
                $result['daiaplus']['score'] = 3;
                $result['daiaplus']['label'] = '';
                $result['daiaplus']['fulllabel'] = '';
                $result['daiaplus']['department'] = '';
                $result['daiaplus']['storage'] = '';
                $result['daiaplus']['storage_org'] = '';
				$result['daiaplus']['storage_additional_info'] = '';
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
		
		$results = $this->makeDaiaConform($results, $daiaJson);
		$results = json_decode($results, true);
		return $results;
	}
    }
	
	public function makeDaiaConform ($results, $daiaJson, $bestResult = array()) {
		$daia_conform_results = array ();
		foreach ($daiaJson as $key => $value) {
			if($key == "document") {
				if(!empty($value)) {
					foreach($value as $document_key => $document_item){
						if($document_key == 0) {
							foreach($document_item as $document_item_key => $document_item_value){
								if($document_item_key == "item") {
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
		
		return  json_encode($daia_conform_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
    
	/**
     * Get Results from external E-Availability Service
     *
     * @return Array
     */
	//TODO: Complete function once external service is available
	public function getElectronicAvailability($ppn, $openUrl, $url_access, $url_access_level, $first_matching_issn, $GVKlink, $doi, $requesturi, $list) {
		return array();
	}
}

?>
