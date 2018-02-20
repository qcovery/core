<?php
/**
 *
 */
namespace BelugaConfig\View\Helper\BelugaConfig;

class ConfigReader extends \Zend\View\Helper\AbstractHelper
{

    protected $belugaConfig;

    public function __construct($belugaConfig)
    {
        $this->belugaConfig = $belugaConfig;
    }

    /**
     *
     */
    public function getConfigData($dataSection, $dataKey = null) {
        if ($dataKey) {
            return $this->belugaConfig[$dataSection][$dataKey];
        } else {
            return $this->belugaConfig[$dataSection];
        }
    }
}
