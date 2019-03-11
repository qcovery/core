<?php
/**
 *
 */
namespace SFX\View\Helper\SFX;

class SFX extends \Zend\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config, \VuFind\Search\Memory $memory)
    {
        $this->config = $config;
    }

    public function getSfxLink($driver) {
        $sfx_domain = 'haw';

        $urls[] = ['url' => $driver->getOpenUrl()];

        $url = $urls[0]['url'];

        $sfxData = $driver->getMarcData('SFX');
        if (is_array($sfxData)) {
            foreach ($sfxData as $sfx) {
                if (is_array($sfx)) {
                    foreach ($sfx as $key => $value) {
                        $url .= '&rft.' . $key . '=' . urlencode($value['data'][0]);
                    }
                }
            }
        }

        $url = str_ireplace('rfr_', '', $url);
        $url = str_ireplace('rft.', '', $url);

        $url = 'http://sfx.gbv.de/sfx_' . $sfx_domain . '?' . $url;

        return urlencode($url);
    }
}
