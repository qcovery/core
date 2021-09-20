<?php
/**
 *
 */
namespace OpacScraper\View\Helper\OpacScraper;

class Holders extends \Laminas\View\Helper\AbstractHelper
{
    protected $holders;

    /**
     *
     */
    public function __construct($config)
    {
        $this->holders = array_keys($config->toArray());
    }

    /**
     *
     */
    public function getHoldingLibraries($driver)
    {
        $collectionDetails = $driver->getMarcData('CollectionDetails');
        $holdingCodes = [];
        if (is_array($collectionDetails)) {
            foreach ($collectionDetails as $collectionDetail) {
                if (isset($collectionDetail['code']['data'][0])) {
                    $holdingCodes[] = $collectionDetail['code']['data'][0];
                }
            }
        }
        $holdingCodes = array_intersect($this->holders, $holdingCodes);
        return $holdingCodes;
    }
}
