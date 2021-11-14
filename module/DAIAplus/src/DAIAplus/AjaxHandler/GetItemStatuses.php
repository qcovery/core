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
use Zend\View\Renderer\RendererInterface;

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
class GetItemStatuses extends \VuFind\AjaxHandler\GetItemStatuses implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    protected $recordLoader;
    
    protected $config;

    protected $checks;

    protected $source;

    protected $driver;
	
	protected $current_mode;
	
	protected $renderer;
	
	protected $default_template;

    /**
     * Constructor
     *
     * @param Loader            $loader    For loading record data via driver
     * @param Config            $config    Top-level configuration
     * @param RendererInterface $renderer  View renderer
     */
    public function __construct(Loader $loader, Config $config, RendererInterface $renderer) {
        $this->recordLoader = $loader;
        $this->config = $config->toArray();
        $this->checks = $this->config['RecordView'];
		$this->renderer = $renderer;
		$this->default_template = 'ajax/default.phtml';
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
                $this->driver = $this->recordLoader->load($id, $this->source);
				$this->driver->addSolrMarcYaml($this->config['General']['availabilityplus_yaml'], false);
				$responses = [];
				$response = [];
                foreach($this->checks as $check => $this->current_mode) {
                    if(in_array($check_mode,array('continue','always'))) {
                        $result = $this->performAvailabilityCheck($check);
                        if(!empty($result)) {
                            $response[] = $result;
							$check_mode = $this->current_mode;
                        }
                    }
					
                }
                $response['id'] = $id;
                $responses[] = $response;
            }
        }
        return $this->formatResponse(['statuses' => $responses]);
    }

    /**
     * Determines which check to run, based on keyword in configuration. Determination in this order depending on match between name and logic: 
	 * 1) function available with name in this class
    `* 2) MarcKey defined in availabilityplus_yaml
     * 3) MarcCategory defined in availabilityplus_yaml
     * TODO: Add checks for resolver and DAIA
	 *
     * @check name of check to run
     *
     * @return array [response data for check]
     */
	//TODO: Add checks for resolver and DAIA
    private function performAvailabilityCheck($check) {
		
		if(method_exists($this, $check)){
			$responses = $this->{$check}();
			/*$response['check'] = $check;
			$response['message'] = 'method in class exists';*/	
		} elseif (!empty($this->driver->getMarcData($check))) {
			$responses = $this->checkSolrMarcKey($check);
			/*$response['check'] = $check;
			$response['message'] = 'MARC key exists';*/
		} elseif (!empty($this->driver->getSolrMarcKeys($check))) {
			$responses = $this->checkSolrMarcCategory($check);
			/*$response['check'] = $check;
			$response['message'] = 'MARC category exists';*/
		} else {
			$response['check'] = $check;
			$response['message'] = 'no MARC configuration or function for check exists';
			$responses[] = $response;
		}
		
        return $responses;
    }
	
	 /**
     * Perform check based on MarcKey
     *
     * @solrMarcKey name of MarcKey in availabilityplus_yaml
     *
     * @return array [response data]
     */
	// TODO: support for multiple responses = not break on first match
    private function checkSolrMarcKey($solrMarcKey) {      
		$data = $this->driver->getMarcData($solrMarcKey);
		$view_method = $this->getViewMethod($data);
		foreach ($data as $date) {
			if (!empty($date['url']['data'][0])) $url = $date['url']['data'][0];
			$level = $solrMarcKey;
			$label = $solrMarcKey;
			if(!empty($date['level']['data'][0])) $level.=" ".$date['level']['data'][0];
			if(!empty($date['label']['data'][0])) $label.=" ".$date['label']['data'][0];
			$response = [ 
							'url' => $url,
							'level' => $level,
							'label' => $label,
						];
			$response['html'] = $this->applyTemplate($view_method, $response);
			break;
		}
       
        return $response;
    }   
	
     /**
     * Perform check based on MarcCategory
     *
     * @category name of MarcCategory in availabilityplus_yaml
     *
     * @return array [response data]
     */
	// TODO: support for multiple responses = not break on first match
    private function checkSolrMarcCategory($category) {
		foreach ($this->driver->getSolrMarcKeys($category) as $solrMarcKey) {
			$data = $this->driver->getMarcData($solrMarcKey);
			$view_method = $this->getViewMethod($data);
			foreach ($data as $date) {
				if (!empty($date['url']['data'][0])) $url = $date['url']['data'][0];
				$level = $category." ".$solrMarcKey;
				$label = $category;
				if(!empty($date['level']['data'][0])) $level.=" ".$date['level']['data'][0];
				if(!empty($date['label']['data'][0])) $label.=" ".$date['label']['data'][0];
				
				$response = [ 
								'url' => $url,
								'level' => $level,
								'label' => $label,
								'view-method' => $view_method
							];
				$response['html'] = $this->applyTemplate($view_method, $response);
				break;
			}
		}
       
        return $response;
    }

     /**
     * Support method to determine if a view-method, i.e. a name of template file has been defined, if not then the default_template is used
     *
     * @data data provided via parsing of availabilityplus_yaml
     *
     * @return string
     */	
	private function getViewMethod($data) {
		$view_method = $this->default_template;
		if(!empty($data['view-method'])) $view_method = 'ajax/'.$data['view-method'].'.phtml';
		return $view_method;
	}
	
     /**
     * Support method to apply template
     *
     * @view_method name of template file
	 * @response response data
     *
     * @return string (html code)
     */	
	private function applyTemplate($view_method, $response) {
		return $this->renderer->render($view_method, $response);
	}

     /**
     * Custom method to check for a parent work that is a holding of the library
     *
     * @return array [response data]
     */		
	private function checkParentWork() {
        $parentData = $this->driver->getMarcData('ArticleParentId');
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
                $url = '/vufind/Record/' . $parentId;
            }
        }
		
		if (!empty($url)) {
			$response = [ 
							'url' => $url,
							'level' => 'ParentWork',
							'label' => 'Go to parent work',
						];
			$response['html'] = $this->renderer->render($this->default_template, $response);
		}
        return $response;
    }
}
