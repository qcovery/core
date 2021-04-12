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
            $isbn = $driver->getCleanISBN();
            if ($isbn == '') {
                $isbnsFromMarc = $driver->getMarcData('Isbns');
                if (isset($isbnsFromMarc[0]['link']['data'][0])) {
                    $isbn = $isbnsFromMarc[0]['link']['data'][0];
                }
            }
            $openUrl .= '&rft.isbn='.$isbn;
        }

        return $openUrl;
    }
}
