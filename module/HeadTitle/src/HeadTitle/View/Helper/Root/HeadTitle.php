<?php

    /**
     * @see       https://github.com/laminas/laminas-view for the canonical source repository
     * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
     * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
     */

    namespace HeadTitle\View\Helper\Root;

    use Laminas\View\Exception;

    /**
     * Helper for setting and retrieving title element for HTML head.
     *
     * Duck-types against Laminas\I18n\Translator\TranslatorAwareInterface.
     */
    class HeadTitle extends \Laminas\View\Helper\HeadTitle
    {
        private $headTitleConfig;

        public function __construct()
        {
            parent::__construct();
            if ($configFile = realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/HeadTitle.ini')) {
                $this->headTitleConfig = parse_ini_file($configFile, true);
            }
        }

        public function renderTitle()
        {
            return $this->getTitlePrefix().parent::renderTitle();
        }

        private function getTitlePrefix () {
            if ($this->headTitleConfig && isset($this->headTitleConfig['title_prefix'])) {
                return $this->headTitleConfig['title_prefix'];
            }
            return '';
        }
    }
