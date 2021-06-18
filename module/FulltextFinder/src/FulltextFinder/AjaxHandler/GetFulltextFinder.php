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
namespace FulltextFinder\AjaxHandler;

use VuFind\AjaxHandler\AbstractBase;
use VuFind\Exception\ILS as ILSException;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\ILS\Connection;
use VuFind\ILS\Logic\Holds;
use VuFind\Session\Settings as SessionSettings;
use VuFind\Crypt\HMAC;
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
class GetFulltextFinder  extends AbstractBase
{

    protected $config;
    protected $renderer;

    /**
     * Constructor
     *
     * @param Config            $config    Top-level configuration
     */
    public function __construct(Config $config, RendererInterface $renderer) {
        $this->config = $config;
        $this->renderer = $renderer;
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
        $openUrl = $params->fromQuery('openurl');
        $list = $params->fromQuery('list');
        $searchClassId = $params->fromQuery('searchClassId');

        $fulltextfinderApiUrl = 'https://api.ebsco.io/ftf/ftfaccount/'.$this->config['FulltextFinder']['account'].'.main.ftf/openurl?'.$openUrl;
        $ch = curl_init();
        $timeout = 0;
        curl_setopt($ch, CURLOPT_URL, $fulltextfinderApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'password: '.$this->config['FulltextFinder']['password']
        ]);

        $fulltextfinderApiResult = json_decode(curl_exec($ch));
        curl_close($ch);

        $categories = [];
        if (isset($this->config['FulltextFinder']['categories'])) {
            $categories = $this->config['FulltextFinder']['categories']->toArray();
        }
        $links = [];
        if (isset($fulltextfinderApiResult->contextObjects)) {
            foreach ($fulltextfinderApiResult->contextObjects as $contextObject) {
                if (isset($contextObject->targetLinks)) {
                    foreach ($contextObject->targetLinks as $targetLink) {
                        if (!empty($categories)) {
                            if (in_array($targetLink->category, $categories)) {
                                $links[] = $targetLink;
                            }
                        } else {
                            $links[] = $targetLink;
                        }
                    }
                }
            }
        }

        $html = $this->renderer->render(
            'fulltextfinder/result.phtml', [
                'searchClassId' => $searchClassId,
                'links' => $links,
                'url' => $fulltextfinderApiUrl,
            ]
        );

        return $this->formatResponse(compact('html'));
    }

}
