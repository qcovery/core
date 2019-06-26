<?php
/**
 *
 */
namespace HelpTooltips\View\Helper\HelpTooltips;

class HelpTooltips extends \Zend\View\Helper\AbstractHelper
{
    protected $helpTooltipsConfig;


    public function __construct($config, \VuFind\Search\Memory $memory)
    {
        $this->helpTooltipsConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/HelpTooltips.ini'), true);
    }

    public function getHelpTooltipsConfig ($context = 'all') {
        if ($context != 'all') {
            $helpTooltips = [];
            foreach ($this->helpTooltipsConfig as $helpTooltip) {
                if ($helpTooltip['context'] == $context) {
                    $helpTooltips[] = $helpTooltip;
                }
            }
            return $helpTooltips;
        } else {
            return $this->helpTooltipsConfig;
        }
    }

    public function getHelpFormAction () {
        return $_SERVER['REQUEST_URI'];
    }
}
