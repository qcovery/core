<?php
/**
 *
 */
namespace FulltextFinder\View\Helper\FulltextFinder;

class FulltextFinder extends \Zend\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getOpenUrl($driver) {
        $openUrl = $driver->getOpenUrl();

        if (!stristr($openUrl, 'rft.issn') || stristr($openUrl, 'rft.issn=&')) {
            $openUrl .= '&rft.issn='.(string)$driver->getCleanISSN();
        }
        if (!stristr($openUrl, 'rft.isbn') || stristr($openUrl, 'rft.isbn=&')) {
            $openUrl .= '&rft.isbn='.(string)$driver->getCleanISBN();
        }

        return $openUrl;
    }
}
