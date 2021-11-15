<?php
/**
 * OpenUrl view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Resolver\View\Helper\Resolver;

use VuFind\View\Helper\Root\Context;
use VuFind\Resolver\Driver\PluginManager;

/**
 * OpenUrl view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class OpenUrl extends \VuFind\View\Helper\Root\OpenUrl
{
    /**
     * VuFind OpenURL configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $resolverConfig;

    /**
     * Constructor
     *
     * @param Context                $context       Context helper
     * @param array                  $openUrlRules  VuFind OpenURL rules
     * @param PluginManager          $pluginManager Resolver plugin manager
     * @param \Laminas\Config\Config $config        VuFind OpenURL config
     */
    public function __construct(
        Context $context,
        $openUrlRules,
        PluginManager $pluginManager,
        $config = null,
        $resolverConfig = null
    ) {
        parent::__construct($context, $openUrlRules, $pluginManager, $config);
        $this->resolverConfig = $resolverConfig->toArray();
    }

    public function getActiveServices()
    {
        $services = [];
        foreach($this->resolverConfig as $service => $params) {
            if (!empty($params['url']) && $this->isActive($service)) {
                $services[] = $service;
            }
        }
        return $services;
    }

    public function resolve()
    {
        $snippets = [];
        foreach($this->resolverConfig as $service => $params) {
            if (!empty($params['url']) && $this->isActive($service)) {
                $snippets[] = $this->renderTemplateForService($service);
            }
        }
        if (empty($snippets)) {
            $snippets[] = parent::renderTemplate();
        }
        return $snippets;
    }

    /**
     * Public method to check whether OpenURLs are active for current record
     *
     * @return bool
     */
    public function isActive($resolver = null)
    {
        // check first if OpenURLs are enabled for this RecordDriver
        // check second if OpenURLs are enabled for this context
        // check last if any rules apply
        if (!$this->recordDriver->getOpenUrl()
            || !$this->checkContext()
            || !$this->checkIfRulesApply($resolver)
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check if the rulesets found apply to the current record. First match counts.
     *
     * @return bool
     */
    protected function checkIfRulesApply($resolver = null)
    {
        // special case if no rules are defined at all assume that any record is
        // valid for openUrls
//print_r($this->openUrlRules);
        if (!isset($this->openUrlRules) || count($this->openUrlRules) < 1) {
            return true;
        }
        foreach ($this->openUrlRules as $rules) {
            if ((empty($resolver) || $rules['resolver'] == $resolver)
                && !$this->checkExcludedRecordsRules($rules)
                && $this->checkSupportedRecordsRules($rules)
            ) {
                return true;
            }
        }
        return false;
    }

    protected function renderTemplateForService($service)
    {
        $embed = (isset($this->config->embed) && !empty($this->config->embed));

        $embedAutoLoad = $this->config->embed_auto_load ?? false;
        // ini values 'true'/'false' are provided via ini reader as 1/0
        // only check embedAutoLoad for area if the current area passed checkContext
        if (!($embedAutoLoad === "1" || $embedAutoLoad === "0")
            && !empty($this->area)
        ) {
            // embedAutoLoad is neither true nor false, so check if it contains an
            // area string defining where exactly to use autoloading
            $embedAutoLoad = in_array(
                strtolower($this->area),
                array_map(
                    'trim',
                    array_map(
                        'strtolower',
                        explode(',', $embedAutoLoad)
                    )
                )
            );
        }

        $resolver = $this->resolverConfig[$service]['resolver'];
        $baseUrl = $this->resolverConfig[$service]['url'];
        $openurl = $this->recordDriver->getOpenUrl();
        if ($this->resolverPluginManager->has($resolver)) {
            $resolverObj = new \VuFind\Resolver\Connection(
                $this->resolverPluginManager->get($resolver)
            );
            $resolverObj->setBaseUrl($baseUrl);
            if (isset($this->resolverConfig[$service]['params'])) {
                $resolverObj->setParameters($this->resolverConfig[$service]['params']);
            }
            $resolverUrl = $resolverObj->getResolverUrl($openurl);
        } else {
            $resolverUrl = empty($baseUrl) ? '' : $baseUrl . '?' . $openurl;
        }

        // Build parameters needed to display the control:
        $params = [
            'service' => $service,
            'resolverUrl' => $resolverUrl,
            'openUrl' => $openurl,
            'openUrlBase' => empty($baseUrl) ? false : $baseUrl,
            'openUrlWindow' => empty($this->config->window_settings)
                ? false : $this->config->window_settings,
            'openUrlGraphic' => empty($this->config->graphic)
                ? false : $this->config->graphic,
            'openUrlGraphicWidth' => empty($this->config->graphic_width)
                ? false : $this->config->graphic_width,
            'openUrlGraphicHeight' => empty($this->config->graphic_height)
                ? false : $this->config->graphic_height,
            'openUrlEmbed' => $embed,
            'openUrlEmbedAutoLoad' => $embedAutoLoad
        ];
        if (!empty($imagebased)) {
            $this->addImageBasedParams($imagebased, $params);
        }

        // Render the subtemplate:
        return $this->context->__invoke($this->getView())->renderInContext(
            'Helpers/openurl.phtml', $params
        );
    }
}
