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
            $libraryCodes = array_intersect($includedLibraries, $driver->getLibraries());
        }
        $connectedLibraries = [];
        foreach ($libraryCodes as $libraryCode) {
            $library = $this->Libraries->getLibrary($libraryCode);
            $connectedLibraries[] = $library['abbrieviation'];
        }
        return $connectedLibraries;
    }
}
