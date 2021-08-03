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
            $issn = $driver->getCleanISSN();
            if (!stristr($openUrl, 'rft.issn')) {
                $openUrl .= '&rft.issn='.$issn;
            } else if (stristr($openUrl, 'rft.issn=&')) {
                $openUrl = str_ireplace('rft.issn=&', 'rft.issn='.$issn.'&', $openUrl);
            }
        }
        if (!stristr($openUrl, 'rft.isbn') || stristr($openUrl, 'rft.isbn=&')) {
            $isbn = $driver->getCleanISBN();
            if ($isbn == '') {
                $isbnsFromMarc = $driver->getMarcData('Isbns');
                if (isset($isbnsFromMarc[0]['link']['data'][0])) {
                    $isbn = $isbnsFromMarc[0]['link']['data'][0];
                }
            }
            if (!stristr($openUrl, 'rft.isbn')) {
                $openUrl .= '&rft.isbn='.$isbn;
            } else if (stristr($openUrl, 'rft.isbn=&')) {
                $openUrl = str_ireplace('rft.isbn=&', 'rft.isbn='.$isbn.'&', $openUrl);
            }
        }

        return $openUrl;
    }
}
