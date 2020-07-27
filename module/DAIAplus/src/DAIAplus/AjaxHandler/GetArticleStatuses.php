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

    protected $format;

    protected $openUrl;

    protected $sfxLink;

    protected $viewTypeList = 'single';

    protected $viewTypeDetail = 'single'; 

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
        $clientUrl = $_SERVER['REMOTE_ADDR'];
        $resolverChecks = $this->config['ResolverChecks'];
        if (!empty($ids) && !empty($source)) {
            $listView = ($params->fromPost('list', $params->fromQuery('list', 'false')) === 'true') ? 1 : 0;
            $viewType = ($listView == 1) ? $this->viewTypeList : $this->viewTypeDetail;
            $singleView = ($viewType == 'single');
            foreach ($ids as $id) {

                $driver = $this->recordLoader->load($id, $source);
                $idResponses = [];
                $urlAccess = '';

                if (isset($resolverChecks['open_access']) && $resolverChecks['open_access'] == 'y') {
                    $urlAccess = $this->checkOpenAccess($driver);
                    if (!empty($urlAccess)) {
                        $urlAccessLevel = 'article_access_level';
                        $urlAccessLabel = 'Fulltext (DOAJ)';
                        $urlAccess = 'http://ilo.sub.uni-hamburg.de/sfx/sfxredir.php?url=' . $urlAccess;
                        $idResponses[] = $this->createResponse($urlAccess, $urlAccessLevel, $urlAccessLabel, 'OA');
                    }
                }
                if (!$singleView || empty($urlAccess)) {
                    if (isset($resolverChecks['fulltext']) && $resolverChecks['fulltext'] == 'y') {
                        $urlAccess = $this->checkFulltext($driver);
                        if (!empty($urlAccess)) {
                            $urlAccessLevel = 'article_access_level';
                            $urlAccessLabel = 'Fulltext';
                            $urlAccess = 'http://ilo.sub.uni-hamburg.de/sfx/sfxredir.php?url=' . $urlAccess;
                            $idResponses[] = $this->createResponse($urlAccess, $urlAccessLevel, $urlAccessLabel, 'Fulltext');
                        }
                    }
                }
                if (!$singleView || empty($urlAccess)) {
                    if (isset($resolverChecks['doi']) && $resolverChecks['doi'] == 'y') {
                        $urlAccess = $this->checkDoi($driver);
                        if (!empty($urlAccess)) {
                            $urlAccessLevel = 'article_access_level';
                            $urlAccessLabel = 'Fulltext (DOI)';
                            $urlAccess = 'http://ilo.sub.uni-hamburg.de/sfx/sfxredir.php?url=' . $urlAccess;
                            $idResponses[] = $this->createResponse($urlAccess, $urlAccessLevel, $urlAccessLabel, 'DOI');
                        }
                    }
                }
                if (!$singleView || empty($urlAccess)) {
                    $this->createUrls($driver);
                    $url = $this->prepareUrl($id, $listView);
                    $resolverResponse = json_decode($this->makeRequest($url), true);
                    $urlAccess = $resolverResponse['items']['sfx_check']['url_access'];
//print_r($resolverResponse);
                    if (!empty($urlAccess)) {
                        $response = ['list' => $resolverResponse['list'],
                                     'items' => ['sfx_check' => $resolverResponse['items']['sfx_check']]
                                    ];
                        $idResponses[] = $this->prepareData($response, 'SFX');
                    }
                    if (!$singleView || empty($urlAccess)) {
                        $urlAccess = $resolverResponse['items']['lr_check']['url_access'];
                        if (!empty($urlAccess)) {
                            $response = ['list' => $resolverResponse['list'],
                                         'items' => ['lr_check' => $resolverResponse['items']['lr_check']]
                                        ];
                            $idResponses[] = $this->prepareData($response, 'DNB/JoP');
                        }
                    }
                    if (!$singleView || empty($urlAccess)) {
                        if (isset($resolverChecks['journal']) && $resolverChecks['journal'] == 'y') {
                            $urlAccess = $this->checkParentId($driver);
                            if (!empty($urlAccess)) {
                                $idResponses[] = $this->createResponse($urlAccess, 'print_access_level', 'Journal', 'Print');
                            }
                        }
                    }
                    if (empty($urlAccess)) {
                        $response = $resolverResponse;
                        $idResponses[] = $this->prepareData($response, 'Check ILL');
                        $idResponses[] = $this->createResponse(urldecode($this->sfxLink), 'check_sfx_access_level', 'Check SFX', 'Check SFX');
                        //print_r($idResponses);
                    }
                }
                $idResponses['id'] = $id;
                $responses[] = $idResponses;
            }
        }
//print_r($responses);
        return $this->formatResponse(['statuses' => $responses]);
    }

    private function createResponse($access, $level, $label, $type) {
        $response = ['list' => ['url_access' => $access,
                                'url_access_level' => $level,
                                'url_access_label' => $label,
                                'link_status' => 1],
                     'items' => ['lr_check' => ['url_access' => $access,
                                                'url_access_level' => $level,
                                                'url_access_label' => $label,
                                                'link_status' => 1]
                                ]
                     ];
        return $this->prepareData($response, $type);
    }

    private function prepareData($rawData, $type) {
        $resolverLabels = $this->config['ResolverLabels'];
        if (!empty($rawData['items'])) {
            foreach ($rawData['items'] as $item) {
                if (!empty($item['url_access'])) {
                    $urlAccess = (is_array($item['url_access'])) ? $item['url_access'][0] : $item['url_access'];
                    if (strpos($urlAccess, 'type=ISN') > 0) {
                        $urlAccess .= urldecode('&filter[]=format_facet:Zeitschriften');
                    }
                    $level = str_replace('_access_level', '', $item['url_access_level']);
                    $label = $resolverLabels[$level] ?: $item['url_access_label'];
                    $data = [
                        'href' => $urlAccess,
                        'level' => $item['url_access_level'],
                        'label' => $this->translate($label, [], $label),
                        'notification' => $item['access_notification'],
                        'doi' => $item['doi'],
                        'type' => $type
                    ];
                    return $data;
                }
            }
        }
        return [];
    }

    private function checkDoi($driver) {
        $urlAccess = '';
        $doiData = $driver->getMarcData('ArticleDoi');
        foreach ($doiData as $doiDate) {
            if (!empty(($doiDate['doi']['data'][0]))) {
                $urlAccess = 'http://dx.doi.org/' . $doiDate['doi']['data'][0];
                break;
            }
        }
        return $urlAccess;
    }                

    private function checkOpenAccess($driver) {
        $urlAccess = '';
        $doajData = $driver->getMarcData('ArticleDoaj');
        foreach ($doajData as $doajDate) {
            if (!empty(($doajDate['url']['data'][0]))) {
                $urlAccess = $doajDate['url']['data'][0];
                break;
            }
        }
        return $urlAccess;
    }                

    private function checkFulltext($driver) {
        $urlAccess = '';
        $fulltextData = $driver->getMarcData('ArticleFulltext');
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

    private function createUrls($driver) {
        $openUrl = $driver->getOpenUrl();
        $formats = $driver->getFormats();
        $format = strtolower(str_ireplace('electronic ','',$formats[0]));

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

        $sfxDomain = $this->config['DAIA']['sfxDomain'] ?? '';
//        $sfxLink = urlencode'http://sfx.gbv.de/sfx_' . $sfxDomain . '?' . $sfxLink;
        $sfxLink = 'http://sfx-49gbv.hosted.exlibrisgroup.com/sfx_' . $sfxDomain . '?' . $sfxLink;
        $this->sfxLink = $sfxLink;
        $this->openUrl = $openUrl;
        $this->format = $format;
    }

    private function prepareUrl($id, $listView) {
        $isil = $this->config['Global']['isil'];
        $url = $this->config['DAIA_' . $isil]['url'];
        $url .= 'electronicavailability/' . $id . '?';
        $url .= 'apikey=' . $this->config['DAIA_' . $isil]['daiaplus_api_key'];
        $url .= '&openurl=' . urlencode($this->openUrl);
        $url .= '&list=' . $listView;
        $url .= '&mediatype=' . urlencode($this->format);
        if ($sfxDomain) {
            $url .= '&sfx=' . urlencode($this->sfxLink);
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


/*
    private function prepareDataOld($rawData, $list, $type) {
        $resolverLabels = $this->config['ResolverLabels'];
//        $data = [];
        if (!empty($rawData['items']['sfx'])) {
            $label = $resolverLabels['article'] ?: 'SFX';
            $data = [
                'href' => $rawData['items']['sfx'],
                'level' => 'article_access_level',
                'label' => $this->translate($label)
            ];
        } elseif (!empty($rawData)) {
            if ($list == 1 && !empty($rawData['list']['url_access'])) {
                $urlAccess = (is_array($rawData['list']['url_access'])) ? $rawData['list']['url_access'][0] : $rawData['list']['url_access'];
                if (strpos($urlAccess, 'type=ISN') > 0) {
                    $urlAccess .= urldecode('&filter[]=format_facet:Zeitschriften');
                }
                $level = str_replace('_access_level', '', $rawData['list']['url_access_level']);
                $label = $resolverLabels[$level] ?: $rawData['list']['url_access_label'];
                $data = [
                    'href' => $urlAccess,
                    'level' => $rawData['list']['url_access_level'],
                    'label' => $this->translate($label),
                    'doi' => $rawData['list']['doi']
                ];
            } else {
                if (empty($rawData['list']['url_access'])) {
                    $level = str_replace('_access_level', '', $rawData['list']['url_access_level']);
                    $label = $resolverLabels[$level] ?: $rawData['list']['url_access_label'];
                    $data = [
                        'label' => $this->translate($label)
                    ];
                } else {
                    foreach ($rawData['items'] as $item) {
                        if (!empty($item) && !empty($item['url_access'])) {
                            $urlAccess = (is_array($item['url_access'])) ? $item['url_access'][0] : $item['url_access'];
                            if (strpos($urlAccess, 'type=ISN') > 0) {
                                $urlAccess .= urldecode('&filter[]=format_facet:Zeitschriften');
                            }
                            $level = str_replace('_access_level', '', $item['url_access_level']);
                            $label = $resolverLabels[$level] ?: $item['url_access_label'];
                            $data = [
                                'href' => $urlAccess,
                                'level' => $item['url_access_level'],
                                'label' => $this->translate($label, [], $label),
                                'notification' => $item['access_notification'],
                                'doi' => $item['doi']
                            ];
                        }
                    }
                }
            }
        }
        $data['type'] = $type;
        return $data;
    }
*/
}
