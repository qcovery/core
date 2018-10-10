<?php
/**
 *
 */
namespace Libraries\View\Helper\Libraries;

use Libraries\Libraries;

class ConnectedLibraries extends \Zend\View\Helper\AbstractHelper
{
    protected $Libraries;

    /**
     *
     */
    public function __construct($config, \VuFind\Search\Memory $memory)
    {
        $this->Libraries = new Libraries($config, $memory);
    }

    /**
     *
     */
    public function getConnectedLibraries($searchClassId, $driver = null)
    {
        $libraryCodes = $this->Libraries->getLibraryCodes($searchClassId);
        if (!empty($driver)) {
            $collectionDetails = $driver->getMarcData('CollectionDetails');
            $holdingCodes = [];
            foreach ($collectionDetails as $collectionDetail) {
                if (isset($collectionDetail['code'])) {
                    $holdingCodes[] = $collectionDetail['code'];
                }
            }
            $libraryCodes = array_intersect($libraryCodes, $holdingCodes);
        }
        $connectedLibraries = [];
        foreach ($libraryCodes as $libraryCode) {
            $connectedLibraries[] = $this->Libraries->getLibrary($libraryCode);
        }
        return $connectedLibraries;
    }

    public function getSelectedLibrary() {
        return $this->Libraries->selectLibrary();
    }
}
