<?php
/**
 * "Get Item Status" AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace DAIAplus\AjaxHandler;

use VuFind\Record\Loader;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use Zend\Config\Config;
use Zend\Mvc\Controller\Plugin\Params;

/**
 * "Get Item Status" AJAX handler
 *
 * This is responsible for printing the holdings information for a
 * collection of records in JSON format.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetItemStatuses extends AbstractBase implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    protected $recordLoader;
    
    protected $config;

    protected $checks;

    protected $source;

    protected $driver;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss        Session settings
     * @param Config            $config    Top-level configuration
     * @param Connection        $ils       ILS connection
     * @param RendererInterface $renderer  View renderer
     * @param Holds             $holdLogic Holds logic
     */
    public function __construct(Loader $loader, Config $config) {
        $this->recordLoader = $loader;
        $this->config = $config->toArray();
        $this->checks = $this->config['RecordView'];
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $responses = [];
        $ids = $params->fromPost('id', $params->fromQuery('id', ''));
        $this->source = $params->fromPost('source', $params->fromQuery('source', ''));

        $list = ($params->fromPost('list', $params->fromQuery('list', 'false')) === 'true') ? 1 : 0;
        if($list) $this->checks = $this->config['ResultList'];

        if (!empty($ids) && !empty($this->source)) {
            foreach ($ids as $id) {
                $check_mode = 'continue';
                $driver = $this->recordLoader->load($id, $this->source);
                $this->driver = $driver;
				$this->driver->addSolrMarcYaml($this->config['General']['availabilityplus_yaml'], false);

                $urlAccess = '';
		        $response = [];

//TODO Remove Start
				$urlAccess = $this->checkParentId();
				if (!empty($urlAccess)) {
					$urlAccessLevel = 'print_access_level';
                    $urlAccessLabel = 'Journal';
					$response[] = [ 
					                'href' => $urlAccess,
                                    'level' => $urlAccessLevel,
									'label' => $urlAccessLabel,
									'html' => '<a href="'.$urlAccess.'" class="'.$urlAccessLevel.'" title="'.$urlAccessLabel.'" target="_blank">'.$this->translate($urlAccessLabel).'</a><br/>'
								];
				}


                $urlAccessUncertain = $this->checkDirectLink($driver);
                if (!empty($urlAccessUncertain)) {
                    $urlAccessLevel = 'uncertain_article_access_level';
                    $urlAccessLabel = 'Go to Publication';
                }

                $urlAccess = $this->checkFreeAccess2($driver, $urlAccessUncertain);
                if (!empty($urlAccess)) {
                    $urlAccessLevel = 'fa_article_access_level';
                    $urlAccessLabel = 'full_text_fa_article_access_level';
					$response[] = [ 
					                'href' => $urlAccess,
                                    'level' => $urlAccessLevel,
									'label' => $urlAccessLabel,
									'html' => '<a href="'.$urlAccess.'" class="'.$urlAccessLevel.'" title="'.$urlAccessLabel.'" target="_blank">'.$this->translate($urlAccessLabel).'</a><br/>'
								];
                }

                $response['id'] = $id;
                $responses[] = $response;
//TODO Remove End
				$responses2 = [];
				$response2 = [];
                foreach($this->checks as $check => $mode) {
                    if(in_array($check_mode,array('continue','always'))) {
                        $result = $this->performAvailabilityCheck($check, $id);
                        if(!empty($result)) {
                            $response2[] = $result;
                        }
                    }
					
                }
                $response2['id'] = $id;
                $responses2[] = $response2;
            }
        }
        return $this->formatResponse(['statuses' => $responses2]);
    }

    private function performAvailabilityCheck($check, $id) {
		
		if(method_exists($this,'check'.$check)){
			$response = $this->{'check'.$check}();
			$response['check'] = 'check'.$check;
			$response['message'] = 'method in class exists';			
		} elseif (!empty($this->driver->getMarcData($check))) {
			$response['check'] = $check;
			$response['message'] = 'MARC key exists';
		} elseif (!empty($this->driver->getSolrMarcKeys($check))) {
			$response = $this->checkSolrMarcCategory($check);
			$response['check'] = $check;
			$response['message'] = 'MARC category exists';
		} else {
			$response['check'] = $check;
			$response['message'] = 'no configuration or function for check exists';
		}
		
        return $response;
    }
	
    private function checkSolrMarcCategory($category) {
        $urlAccess = '';      
		$response = [];
		foreach ($this->driver->getSolrMarcKeys($category) as $solrMarcKey) {
			$data = $this->driver->getMarcData($solrMarcKey);
			foreach ($data as $date) {
				$urlAccessLevel = $category." ".$solrMarcKey;
				$urlAccessLabel = $category;
				if (!empty($date['url']['data'][0])) {
					$urlAccess = $date['url']['data'][0];
					if(!empty($date['class']['data'][0])) $urlAccessLevel.=" ".$date['class']['data'][0];
					break;
				}
			}
		}

		if (!empty($urlAccess)) {
			$response = [ 
							'href' => $urlAccess,
							'level' => $urlAccessLevel,
							'label' => $urlAccessLabel,
							'html' => '<a href="'.$urlAccess.'" class="'.$urlAccessLevel.'" title="'.$urlAccessLabel.'" target="_blank">'.$this->translate($urlAccessLabel).'</a><br/>'
						];
		}
       
        return $response;
    }      
//TODO Remove
    private function checkFreeAccess2($driver, $urlAccessUncertain = '') {
        $urlAccess = '';      
        $categories = array("marcFulltextCheckDirect", "marcFulltextCheckIndirect");
        
        foreach ($categories as $category) {
            foreach ($driver->getSolrMarcKeys($category) as $solrMarcKey) {
                $data = $driver->getMarcData($solrMarcKey);
                foreach ($data as $date) {
                    if ($category == "marcFulltextCheckDirect") {
                        if (!empty(($date['url']['data'][0]))) {
                            $urlAccess = $date['url']['data'][0];
                            break;
                        }
                    } else if ($urlAccessUncertain && $category == "marcFulltextCheckIndirect") {
                        if (!empty(($date))) {
                            $urlAccess = $urlAccessUncertain;
                            break;
                        }
                    }
                }
            }
        }
       
        return $urlAccess;
    }                
//TODO Remove
    private function checkDirectLink($driver) {
        $urlAccess = '';
        $fulltextData = $driver->getMarcData('ArticleDirectLink');
        foreach ($fulltextData as $fulltextDate) {
            if (!empty(($fulltextDate['url']['data'][0]))) {
                $urlAccess = $fulltextDate['url']['data'][0];
                break;
            }
        }
        return $urlAccess;
    }

//TODO Remove
    private function checkParentId() {
		$driver = $this->driver;
        $urlAccess = '';
        $parentData = $driver->getMarcData('ArticleParentId');
        foreach ($parentData as $parentDate) {
            if (!empty(($parentDate['id']['data'][0]))) {
                $parentId = $parentDate['id']['data'][0];
                break;
            }
        }
        if (!empty($parentId)) {
            $parentDriver = $this->recordLoader->load($parentId, 'Solr');
            $ilnMatch = $parentDriver->getMarcData('ILN');
            if (!empty($ilnMatch[0]['iln']['data'][0])) {
                $urlAccess = '/vufind/Record/' . $parentId;
            }
        }
        return $urlAccess;
    }
	
	private function checkParentWork() {
		$driver = $this->driver;
        $urlAccess = '';
		$response = [];
        $parentData = $driver->getMarcData('ArticleParentId');
        foreach ($parentData as $parentDate) {
            if (!empty(($parentDate['id']['data'][0]))) {
                $parentId = $parentDate['id']['data'][0];
                break;
            }
        }
        if (!empty($parentId)) {
            $parentDriver = $this->recordLoader->load($parentId, 'Solr');
            $ilnMatch = $parentDriver->getMarcData('ILN');
            if (!empty($ilnMatch[0]['iln']['data'][0])) {
                $urlAccess = '/vufind/Record/' . $parentId;
            }
        }
		
		if (!empty($urlAccess)) {
			$urlAccessLevel = 'print_access_level';
			$urlAccessLabel = 'Journal';
			$response = [ 
							'href' => $urlAccess,
							'level' => $urlAccessLevel,
							'label' => $urlAccessLabel,
							'html' => '<a href="'.$urlAccess.'" class="'.$urlAccessLevel.'" title="'.$urlAccessLabel.'" target="_blank">'.$this->translate($urlAccessLabel).'</a><br/>'
						];
		}
		
        return $response;
    }
}
