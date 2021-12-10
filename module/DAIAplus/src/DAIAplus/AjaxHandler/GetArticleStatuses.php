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
class GetArticleStatuses extends AbstractBase implements TranslatorAwareInterface
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
        $resolverChecks = $this->config['ResolverChecks'];
        if (!empty($ids) && !empty($source)) {
            $listView = ($params->fromPost('list', $params->fromQuery('list', 'false')) === 'true') ? 1 : 0;
            foreach ($ids as $id) {
                $driver = $this->recordLoader->load($id, $source);
                $urlAccess = '';
		$response = [];
                $daiaplus_check_bool = true;

                if (isset($resolverChecks['journal']) && $resolverChecks['journal'] == 'y') {
                    $urlAccess = $this->checkParentId($driver);
                    if (!empty($urlAccess)) {
                        $response = ['list' => ['url_access' => $urlAccess,
                                                'url_access_level' => 'journal_check',
                                                'url_access_label' => 'journal_check',
                                                'link_status' => 1],
                                     'items' => ['journal_check' =>
                                                    ['url_access' => $urlAccess,
                                                     'url_access_level' => 'journal_check',
                                                     'url_access_label' => 'journal_check',
                                                     'link_status' => 1]
                                                 ]
                                    ];
                    //$daiaplus_check_bool = false;
                    }
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
                         $response['list'] = ['url_access' => $urlAccess,
                                            'url_access_level' => $urlAccessLevel,
                                            'url_access_label' => $urlAccessLabel,
                                            'link_status' => 1];
                         $response['items']['fa_check'] = ['url_access' => $urlAccess,
                                            'url_access_level' => $urlAccessLevel,
                                            'url_access_label' => $urlAccessLabel,
                                            'link_status' => 1];

                    $daiaplus_check_bool = false;
                }

                if ($daiaplus_check_bool == true) {
                    $url = $this->prepareUrl($driver, $id, $listView, $urlAccessUncertain, $urlAccessLevel);
                    error_log($url);
                    $request_result = json_decode($this->makeRequest($url), true);
                    if(empty($response)) {
                        $response = $request_result;
                    } else {
                        foreach($request_result['items'] as $key => $item) {
                            if(in_array($item['url_accces_level'],array('oa_article_access_level', 'oa_homepage_access_level', 'fa_article_access_level', 'fa_homepage_access_level', 'article_access_level', 'homepage_access_level', 'issue_access_level', 'print_access_level', 'volume_access_level', 'proxy_article_access_level', 'proxy_homepage_access_level', 'proxy_issue_access_level', 'proxy_volume_access_level'))) {
                                $response['list'] = $item;
                                $response['item'][$key] = $item;
                            }
                        }
                    }
					if(!empty($response['items']['journal_check'])) array_unshift($request_result['items'], $response['items']['journal_check']);
                        //$request_result['items']['journal_check'] = $response['items']['journal_check'];
					$response = $request_result;
                }

                $response = $this->prepareData($response, $listView);
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

    private function prepareUrl($driver, $id, $listView, $urlAccess = '', $urlAccessLevel = '') {
        $openUrl = $driver->getOpenUrl();
        $formats = $driver->getFormats();
        $format = strtolower(str_ireplace('electronic ','',$formats[0]));
        $doi = $driver->getMarcData('Doi');

        $sfxLink = $openUrl;
        $sfxData = $driver->getMarcData('SFX');

        if (is_array($sfxData)) {
            foreach ($sfxData as $sfxDate) {
                if (is_array($sfxDate)) {
                    foreach ($sfxDate as $key => $value) {
                        $sfxLink .= '&' . $key . '=' . urlencode($value['data'][0]);
                        if(strpos($openUrl, 'rft.' . $key . '=') === false) {
                            $openUrl .= '&rft.' . $key . '=' . urlencode($value['data'][0]);
                        }
                    }
                }
            }
        }

        if(strpos($openUrl, 'rft.genre=') === false) {
            $openUrl .= '&rft.genre=' . $format;
        }

        if (isset($this->config['DAIA']['sfxUrl']) && isset($this->config['DAIA']['sfxDomain'])) {
            $sfxUrl = $this->config['DAIA']['sfxUrl'] ?? '';
            $sfxDomain = $this->config['DAIA']['sfxDomain'] ?? '';
            if (!empty($sfxUrl) && !empty($sfxDomain)) {
                $sfxLink = urlencode($sfxUrl . '/sfx_' . $sfxDomain . '?' . $sfxLink);
            }
        }

        $url = $this->config['DAIA']['baseUrl'];
        $url = str_ireplace('/availability/', '/electronicavailability/', $url);
        $url .= $id . '?';
        $url .= 'apikey=' . $this->config['DAIA']['daiaplus_api_key'];
        $url .= '&openurl=' . urlencode($openUrl);
        $url .= '&list=' . $listView;
        $url .= '&mediatype=' . urlencode($format);

        if ($doi[0]['doi']['data'][0]) {
            $url .= '&doi=' . $doi[0]['doi']['data'][0];
        }
        
        if($urlAccess) {
            $url .= '&url-access=' . $urlAccess;
        }
        
        if($urlAccessLevel) {
            $url .= '&url-access-level=' . $urlAccessLevel;
        }

        if (!empty($sfxUrl) && !empty($sfxDomain)) {
            $url .= '&sfx=' . $sfxLink;
        }

        $url .= '&language=de';
        $url .= '&format=json';
        return $url;
    }

    private function makeRequest($url) {
        $req = curl_init();
        curl_setopt($req, CURLOPT_URL, $url);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($req, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($req);
        curl_close($req);
        return $result;
    }

    private function prepareData($rawData, $list) {
        $resolverLabels = $this->config['ResolverLabels'];
        $data = [];
        if (!empty($rawData['items']['sfx'])) {
            $label = $resolverLabels['article'] ?: 'SFX';
            $data[] = [
                'href' => $rawData['items']['sfx'],
                'level' => 'article_access_level',
                'label' => $this->translate($label)
            ];
        } elseif (!empty($rawData)) {
            if ($list == 1 && !empty($rawData['list']['url_access'])) {
                $urlAccess = (is_array($rawData['list']['url_access'])) ? $rawData['list']['url_access'][0] : $rawData['list']['url_access'];
				$urlAccess = str_replace('&filter[]=format_facet%3A(%22Zeitschriften%22%20OR%20%22Bücher%22)','',$urlAccess);
				$urlAccess = str_replace('lookfor=SGN ','type=Signature&lookfor=', $urlAccess);
                $level = str_replace('_access_level', '', $rawData['list']['url_access_level']);
                $label = $resolverLabels[$level] ?: $rawData['list']['url_access_label'];
                $data[] = [
                    'href' => $urlAccess,
                    'level' => $rawData['list']['url_access_level'],
                    'label' => $this->translate($label),
                    'doi' => $rawData['list']['doi'],
                    'holdings' => $rawData['list']['holdings']
                ];
            } else {
                if (empty($rawData['list']['url_access'])) {
                    $level = str_replace('_access_level', '', $rawData['list']['url_access_level']);
                    $label = $resolverLabels[$level] ?: $rawData['list']['url_access_label'];
                    $data[] = [
                        'label' => $this->translate($label)
                    ];
                } else {
                    foreach ($rawData['items'] as $item) {
                        if (!empty($item) && !empty($item['url_access'])) {
                            $urlAccess = (is_array($item['url_access'])) ? $item['url_access'][0] : $item['url_access'];
							$urlAccess = str_replace('&filter[]=format_facet%3A(%22Zeitschriften%22%20OR%20%22Bücher%22)','',$urlAccess);
							$urlAccess = str_replace('lookfor=SGN ','type=Signature&lookfor=', $urlAccess);
                            $level = str_replace('_access_level', '', $item['url_access_level']);
                            $label = $resolverLabels[$level] ?: $item['url_access_label'];
                            $data[] = [
                                'href' => $urlAccess,
                                'level' => $item['url_access_level'],
                                'label' => $this->translate($label, [], $label),
                                'notification' => $item['access_notification'],
                                'doi' => $item['doi'],
                                'holdings' => $item['holdings']
                            ];
                        }
                    }
                }
            }
        }
        return $data;
    }
}
