<?php
/**
 * "Get Resolver Links" AJAX handler
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
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Resolver\AjaxHandler;

use Laminas\Config\Config;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\Resolver\Connection;
use VuFind\Resolver\Driver\PluginManager as ResolverManager;
use VuFind\Session\Settings as SessionSettings;

/**
 * "Get Resolver Links" AJAX handler
 *
 * Fetch Links from resolver given an OpenURL and format as HTML
 * and output the HTML content in JSON object.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetResolverLinks extends AbstractBase implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Resolver driver plugin manager
     *
     * @var ResolverManager
     */
    protected $pluginManager;

    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * Top-level VuFind configuration (config.ini)
     *
     * @var Config
     */
    protected $resolverConfig;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss       Session settings
     * @param ResolverManager   $pm       Resolver driver plugin manager
     * @param RendererInterface $renderer View renderer
     * @param Config            $config   Top-level VuFind configuration (config.ini)
     */
    public function __construct(SessionSettings $ss, ResolverManager $pm,
        RendererInterface $renderer, Config $resolverConfig
    ) {
        $this->sessionSettings = $ss;
        $this->pluginManager = $pm;
        $this->renderer = $renderer;
        $this->resolverConfig = $resolverConfig->toArray();
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
        $this->disableSessionWrites();  // avoid session write timing bug
        $openUrl = $params->fromQuery('openurl', '');
        $resolverService = $params->fromQuery('resolverservice', ''); 
        $searchClassId = $params->fromQuery('searchClassId', '');

        $snippets = [];
        foreach($this->resolverConfig as $service => $params) {
            if (!empty($resolverService) && $resolverService != $service) {
                continue;
            }
            if (!empty($params['resolver'])) {
                $resolverType = $params['resolver'];
                if (!$this->pluginManager->has($resolverType)) {
                    return $this->formatResponse(
                        $this->translate("Could not load driver for $resolverType"),
                        self::STATUS_HTTP_ERROR
                    );
                }
                $resolver = new Connection($this->pluginManager->get($resolverType));
                if (false && isset($params['resolver_cache'])) {
                    $resolver->enableCache($params['resolver_cache']);
                }
                $resolver->setBaseUrl($params['url']);
                $resolver->setParameters($params['params']);
                $result = $resolver->fetchLinks($openUrl);
                // Sort the returned links into categories based on service type:
                $electronic = $print = $services = [];
                foreach ($result as $link) {
                    switch ($link['service_type'] ?? '') {
                        case 'getHolding':
                            $print[] = $link;
                            break;
                        case 'getWebService':
                            $services[] = $link;
                            break;
                        case 'getDOI':
                        // Special case -- modify DOI text for special display:
                            $link['title'] = $this->translate('Get full text');
                            $link['coverage'] = '';
                        case 'getFullTxt':
                        default:
                            $electronic[] = $link;
                            break;
                    }
                }

                // Get the OpenURL base:
                if (isset($params['url'])) {
                    // Trim off any parameters (for legacy compatibility -- default config
                    // used to include extraneous parameters):
                    [$base] = explode('?', $params['url']);
                } else {
                    $base = false;
                }

                $moreOptionsLink = (false && $resolver->supportsMoreOptionsLink())
                    ? $resolver->getResolverUrl($openUrl) : '';

                // Render the links using the view:
                $view = [
                    'openUrlBase' => $base, 'openUrl' => $openUrl, 'print' => $print,
                    'electronic' => $electronic, 'services' => $services,
                    'searchClassId' => $searchClassId,
                    'moreOptionsLink' => $moreOptionsLink
                ];
                $snippets[] = $this->renderer->render('ajax/resolverLinks.phtml', $view);
            }
        }
        // output HTML encoded in JSON object
        $html = implode("<br/>\n", $snippets);
        return $this->formatResponse(compact('html'));
    }
}