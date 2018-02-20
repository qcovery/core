<?php
/**
 *
 */
namespace Libraries\View\Helper\Libraries;

class ConnectedLibraries extends \Zend\View\Helper\AbstractHelper
{
    protected $Libraries;

    /**
     *
     */
    public function __construct($Libraries)
    {
        $this->Libraries = $Libraries;
    }

    /**
     *
     */
    public function getConnectedLibraries($searchClassId, $driver)
    {
        $includedLibraries = $this->Libraries->getLibraryCodes($searchClassId);
        $libraryCodes = array_intersect($includedLibraries, $driver->getLibraries());
        $connectedLibraries = [];
        foreach ($libraryCodes as $libraryCode) {
            $library = $this->Libraries->getLibrary($libraryCode);
            $connectedLibraries[] = $library['abbrieviation'];
        }
        return $connectedLibraries;
    }
}
