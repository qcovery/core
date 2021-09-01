<?php
/**
 * Factory for Libraries Module loading the correct Connector
 *
 * PHP version 5
 *
 * Copyright (C) Staats- und Universitätsbibliothek 2017.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Search
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */
namespace Libraries\Search\Factory;

use Libraries\Backend\Solr\Connector;
use VuFindSearch\Backend\Solr\HandlerMap;
use VuFind\Search\Factory\SolrDefaultBackendFactory as BackendFactory;

class SolrDefaultBackendFactory extends BackendFactory
{
    /**
     * Create the SOLR connector.
     *
     * @return Connector
     */
    protected function createConnector()
    {
        $config = $this->config->get($this->mainConfig);
        $searchConfig = $this->config->get($this->searchConfig);
        $defaultFields = $searchConfig->General->default_record_fields ?? '*';

        $handlers = [
            'select' => [
                'fallback' => true,
                'defaults' => ['fl' => '*,score'],
                'appends'  => ['fq' => []],
            ],
            'term' => [
                'functions' => ['terms'],
            ],
        ];

        foreach ($this->getHiddenFilters() as $filter) {
            array_push($handlers['select']['appends']['fq'], $filter);
        }

        $httpService = $this->serviceLocator->get(\VuFindHttp\HttpService::class);

	$client = $httpService->createClient();

        $connector = new Connector(
            $this->getSolrUrl(), new HandlerMap($handlers), $this->uniqueKey, $client
        );
        $connector->setTimeout($config->Index->timeout ?? 30);

        if ($this->logger) {
            $connector->setLogger($this->logger);
	}

        if (!empty($searchConfig->SearchCache->adapter)) {
            $cacheConfig = $searchConfig->SearchCache->toArray();
            $options = $cacheConfig['options'] ?? [];
            if (empty($options['namespace'])) {
                $options['namespace'] = 'Index';
            }
            if (empty($options['ttl'])) {
                $options['ttl'] = 300;
            }
            $settings = [
                'adapter' => [
                    'name' => $cacheConfig['adapter'],
                    'options' => $options,
                ]
            ];
            $cache = \Laminas\Cache\StorageFactory::factory($settings);
            $connector->setCache($cache);
        }
        return $connector;
    }
}

