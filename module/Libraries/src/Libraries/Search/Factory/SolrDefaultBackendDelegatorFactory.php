<?php

/**
 * Factory for the default SOLR backend.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2013.
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
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Libraries\Search\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use VuFindSearch\Backend\Solr\HandlerMap;
use Libraries\Backend\Solr\Connector;

/**
 * Factory for the default SOLR backend.
 *
 * @category VuFind
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class SolrDefaultBackendDelegatorFactory implements DelegatorFactoryInterface
{

    protected $uniqueKey = 'id';

    /**
     * Create the SOLR backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    public function __invoke(
        ContainerInterface $container, $name, callable $callback,
        array $options = null)
    {
        $this->config = $container->get('VuFind\Config\PluginManager');
        $connector = $this->createConnector();
        $backend = call_user_func($callback, $connector);
/* 
        $backend = new Backend($connector);
        $backend->setQueryBuilder($this->createQueryBuilder());
        $backend->setSimilarBuilder($this->createSimilarBuilder());
        if ($this->logger) {
            $backend->setLogger($this->logger);
        }
*/
/*

        if ($this->serviceLocator->has('VuFind\Log\Logger')) {
            $this->logger = $this->serviceLocator->get('VuFind\Log\Logger');
        }

        $specs = $container->get('VuFind\Config\SearchSpecsReader')
            ->get($this->searchYaml);
        $backend->setQueryBuilder($this->createQueryBuilder($specs));
*/
        return $backend;
    }

    /**
     * Create the SOLR connector.
     *
     * @return Connector
     */
    protected function createConnector()
    {
        $config = $this->config->get('config');

        $handlers = [
            'select' => [
                'fallback' => true,
                'defaults' => ['fl' => '*,score'],
                'appends'  => ['fq' => []],
            ],
            'terms' => [
                'functions' => ['terms'],
            ],
        ];
        foreach ($this->getHiddenFilters() as $filter) {
            array_push($handlers['select']['appends']['fq'], $filter);
        }
        $connector = new \Libraries\Backend\Solr\Connector(
            $this->getSolrUrl(), new HandlerMap($handlers), $this->uniqueKey
        );
        $connector->setTimeout(
            isset($config->Index->timeout) ? $config->Index->timeout : 30
        );

        if ($this->logger) {
            $connector->setLogger($this->logger);
        }
/*  
       if ($this->serviceLocator->has('VuFindHttp\HttpService')) {
            $connector
                ->setProxy($this->serviceLocator->get('VuFindHttp\HttpService'));
        }
*/
        return $connector;
    }

    /**
     * Get all hidden filter settings.
     *
     * @return array
     */
    protected function getHiddenFilters()
    {
        $search = $this->config->get('searches');
        $hf = [];

        // Hidden filters
        if (isset($search->HiddenFilters)) {
            foreach ($search->HiddenFilters as $field => $value) {
                $hf[] = sprintf('%s:"%s"', $field, $value);
            }
        }

        // Raw hidden filters
        if (isset($search->RawHiddenFilters)) {
            foreach ($search->RawHiddenFilters as $filter) {
                $hf[] = $filter;
            }
        }

        return $hf;
    }

    /**
     * Get the Solr URL.
     *
     * @param string $config name of configuration file (null for default)
     *
     * @return string|array
     */
    protected function getSolrUrl($config = null)
    {
        $config = $this->config->get($config ?? 'config');
        $url = $config->Index->url;
        $core = isset($config->Index->default_core)
            ? $config->Index->default_core : 'biblio';;
        if (is_object($url)) {
            return array_map(
                function ($value) use ($core) {
                    return "$value/$core";
                },
                $url->toArray()
            );
        }
        return "$url/$core";
    }

}
