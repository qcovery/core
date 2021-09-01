<?php
/**
 *
 */
namespace Libraries\View\Helper\Libraries;

use Libraries\Libraries;

class ConnectedLibraries extends \Laminas\View\Helper\AbstractHelper
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
        $libraryCodes = $this->getConnectedLibrariesCodes($searchClassId, $driver);
        $connectedLibraries = [];
        foreach ($libraryCodes as $libraryCode) {
            $connectedLibraries[$libraryCode] = $this->Libraries->getLibrary($libraryCode);
        }
        return $connectedLibraries;
    }

    public function getSelectedLibrary() {
        return $this->Libraries->selectLibrary();
    }

    /**
     *
     */
    public function getConnectedLibrariesCodes($searchClassId, $driver = null) {
        $libraryCodes = array_unique($this->Libraries->getLibraryCodes($searchClassId));
        if (!empty($driver)) {
            $collectionDetails = $driver->getMarcData('CollectionDetails');
            $holdingCodes = [];
            if (is_array($collectionDetails)) {
                foreach ($collectionDetails as $collectionDetail) {
                    if (isset($collectionDetail['code']['data'][0])) {
                        $holdingCodes[] = $collectionDetail['code']['data'][0];
                    }
                }
            }
            $libraryCodes = array_intersect($libraryCodes, $holdingCodes);
        }
        return $libraryCodes;
    }

    /**
     *
     */
    public function getConnectedLibrariesLinkData($libraryCode) {
        return $this->Libraries->getLibraryLinkData($libraryCode);
    }
}
