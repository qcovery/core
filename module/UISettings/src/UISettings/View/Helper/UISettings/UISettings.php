<?php
/**
 *
 */
namespace UISettings\View\Helper\UISettings;

use Laminas\View\Helper\AbstractHelper;
use VuFind\Cookie\CookieManager;

class UISettings extends AbstractHelper
{
    protected $cookieManager;

    public function __construct(CookieManager $cookieManager)
    {
        $this->cookieManager = $cookieManager;
    }

    public function getUISettingsCss () {
        $styles = [];
        if ($this->cookieManager->get('ui_settings_contrast')) {
            $styles[] = 'uisettings_contrast_'.$this->cookieManager->get('ui_settings_contrast').'.css';
        }
        return $styles;
    }

    public function isHighContrast () {
        if ($this->cookieManager->get('ui_settings_contrast')) {
            return true;
        }
        return false;
    }
}
