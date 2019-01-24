<?php
/**
 *
 */
namespace DAIAplus\View\Helper\DAIAplus;

class DAIAplus extends \Zend\View\Helper\AbstractHelper
{
    protected $paiaConfig;

    /**
     *
     */
    public function __construct($config, \VuFind\Search\Memory $memory)
    {
        $this->paiaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/PAIA.ini'), true);
    }

    public function getSfxLink($driver) {
        $sfx_domain = '';
        if (isset($this->paiaConfig['DAIA']['sfxDomain'])) {
            $sfx_domain = $this->paiaConfig['DAIA']['sfxDomain'];
        }

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
