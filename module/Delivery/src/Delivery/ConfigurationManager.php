<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Available
 *
 * @author seng
 */
namespace Delivery;

use VuFind\Config\PluginManager as ConfigManager;

class ConfigurationManager {

    protected $configManager;

    protected $deliveryDomain;

    protected $deliveryDomains;

    protected $globalConfig;

    protected $mainConfig;

    protected $availabilityConfig;

    protected $orderDataConfig;

    public function __construct(ConfigManager $configManager, $deliveryDomain = 'main') 
    {
        $this->configManager = $configManager;
        $this->setConfigurations($deliveryDomain);
    }

    public function setConfigurations($deliveryDomain)
    {
        if ($deliveryDomain != $this->deliveryDomain) {
            $this->globalConfig = $this->configManager->get('deliveryGlobal')->toArray();
            $this->deliveryDomains = $this->getDeliveryDomains();
            if (!in_array($deliveryDomain, $this->deliveryDomains)) {
                $deliveryDomain = $this->deliveryDomains[0];
            }
            $this->mainConfig = $this->globalConfig[$deliveryDomain];
            $this->deliveryDomain = $deliveryDomain;
        }
    }

    public function getGlobalConfig()
    {
        return $this->globalConfig;
    }

    public function getMainConfig()
    {
        return $this->globalConfig[$this->deliveryDomain];
    }

    public function getAvailabilityConfig()
    {
        $availabilityConfigIni = $this->mainConfig['availability_config'];
        return $this->configManager->get($availabilityConfigIni)->toArray();
    }

    public function getOrderDataConfig()
    {
        $orderDataConfigIni = $this->mainConfig['orderdata_config'];
        return $this->configManager->get($orderDataConfigIni)->toArray();
    }

    public function getPluginConfig()
    {
        $plugin = $this->mainConfig['plugin'];
        return array_merge($this->globalConfig[$plugin], ['plugin' => $plugin]);
    }

    public function getDeliveryDomains()
    {
        $domains = [];
        if (!isset($this->deliveryDomains)) {
            foreach ($this->globalConfig as $domain => $config) {
                if (isset($config['domain'])) {
                    $domains[] = $domain;
                }
            }
        } else {
            $domains = $this->deliveryDomains;
        }
        return $domains;
    }
}
