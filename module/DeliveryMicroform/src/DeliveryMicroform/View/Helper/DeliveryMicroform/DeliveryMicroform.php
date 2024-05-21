<?php
/**
 *
 */
namespace DeliveryMicroform\View\Helper\DeliveryMicroform;

class DeliveryMicroform extends \Laminas\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config)
    {
        $this->config = $config->toArray();
    }

    public function getTypeByLabel($label) {
        if ($configurationKey = $this->getConfigByLabel($label)) {
            return str_ireplace('AF_', '', $configurationKey);
        }
        return '';
    }

    public function getButtonByLabel($label) {
        if ($configurationKey = $this->getConfigByLabel($label)) {
            if (isset($this->config[$configurationKey]['labelButton'])) {
                return $this->config[$configurationKey]['labelButton'];
            }
        }
        return '';
    }

    private function getConfigByLabel($label) {
        foreach ($this->config as $key => $configuration) {
            if ($this->startsWith($key, 'AF_')) {
                if (isset($configuration['label']) && is_array($configuration['label'])) {
                    foreach ($configuration['label'] as $checkLabel) {
                        if ($label == $checkLabel) {
                            return $key;
                        }
                    }
                }
            }
        }
        return null;
    }

    private function startsWith($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
