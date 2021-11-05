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
        $source = $params->fromPost('source', $params->fromQuery('source', ''));
        if (!empty($ids) && !empty($source)) {
            $listView = ($params->fromPost('list', $params->fromQuery('list', 'false')) === 'true') ? 1 : 0;
            foreach ($ids as $id) {
                $driver = $this->recordLoader->load($id, $source);
                $urlAccess = '';
		$response = [];
                $daiaplus_check_bool = true;

				$urlAccess = $this->checkParentId($driver);
				if (!empty($urlAccess)) {
					$urlAccessLevel = 'print_access_level';
                    $urlAccessLabel = 'Journal';
					$response[] = [ 
					                'href' => $urlAccess,
                                    'level' => $urlAccessLevel,
									'label' => $this->translate($urlAccessLabel),
									'html' => '<a href="'.$urlAccess.'" class="'.$urlAccessLevel.'" title="'.$urlAccessLabel.'" target="_blank">'.$this->translate($urlAccessLabel).'</a><br/>'
								];
				}


                $urlAccessUncertain = $this->checkDirectLink($driver);
                if (!empty($urlAccessUncertain)) {
                    $urlAccessLevel = 'uncertain_article_access_level';
                    $urlAccessLabel = 'Go to Publication';
                }

                $urlAccess = $this->checkFreeAccess($driver, $urlAccessUncertain);
                if (!empty($urlAccess)) {
                    $urlAccessLevel = 'fa_article_access_level';
                    $urlAccessLabel = 'full_text_fa_article_access_level';
					$response[] = [ 
					                'href' => $urlAccess,
                                    'level' => $urlAccessLevel,
									'label' => $this->translate($urlAccessLabel),
									'html' => '<a href="'.$urlAccess.'" class="'.$urlAccessLevel.'" title="'.$urlAccessLabel.'" target="_blank">'.$this->translate($urlAccessLabel).'</a><br/>'
								];
                }

                $response['id'] = $id;
                $responses[] = $response;
            }
        }
        return $this->formatResponse(['statuses' => $responses]);
    }

    private function checkFreeAccess($driver, $urlAccessUncertain = '') {
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

    private function checkParentId($driver) {
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
}
