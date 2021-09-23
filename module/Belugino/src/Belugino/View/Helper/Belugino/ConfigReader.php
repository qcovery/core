<?php
/**
 *
 */
namespace Belugino\View\Helper\Belugino;

use Laminas\View\Helper\AbstractHelper;

class ConfigReader extends AbstractHelper
{

    protected $beluginoConfig;

    public function __construct($beluginoConfig)
    {
        $this->beluginoConfig = (array)$beluginoConfig;
    }

    /**
     *
     */
    public function getConfigData($format) {
        return $this->beluginoConfig['belugino'][$format];
    }
}
