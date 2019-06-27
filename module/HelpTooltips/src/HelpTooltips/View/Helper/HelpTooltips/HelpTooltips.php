<?php
/**
 *
 */
namespace HelpTooltips\View\Helper\HelpTooltips;

class HelpTooltips extends \Zend\View\Helper\AbstractHelper
{
    protected $helpTooltipsConfig;
    protected $session;

    public function __construct($config, \VuFind\Search\Memory $memory, $sessionManager)
    {
        $this->helpTooltipsConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/HelpTooltips.ini'), true);
        $this->sessionManager = $sessionManager;
        $this->session = new \Zend\Session\Container(
            'HelpTooltips', $essionManager
        );
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

    public function showHelp () {
        if ($_POST['showHelp']) {
            $this->session->showHelp = true;
        } else if ($_POST['hideHelp']) {
            $this->session->showHelp = false;
        }

        return $this->session->showHelp;
    }
}
