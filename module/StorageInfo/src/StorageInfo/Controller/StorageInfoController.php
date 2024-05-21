<?php

namespace StorageInfo\Controller;

use VuFind\Controller\AbstractBase;
use Laminas\ServiceManager\ServiceLocatorInterface;

class StorageInfoController extends AbstractBase
{
    private $storageUrl;
    private $storageInfo;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service locator
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        $this->serviceLocator = $sm;
    }

    public function storageAction () {
        $view = $this->createViewModel();

        $this->storageUrl = $this->params()->fromPost('uri', $this->params()->fromQuery('uri', []));
        $this->storageInfo = json_decode(file_get_contents($this->storageUrl.'?format=json'));

        $view->name = $this->getValue('http://xmlns.com/foaf/0.1/name');
        $view->description = $this->getValue('http://purl.org/dc/elements/1.1/description');
        $view->openinghours = $this->getValue('http://purl.org/ontology/gbv/openinghours');
        $view->address = $this->getValue('http://purl.org/ontology/gbv/address');
        $view->phone = $this->getValue('http://xmlns.com/foaf/0.1/phone');
        $view->geo_long = $this->getlocationValue('http://www.w3.org/2003/01/geo/wgs84_pos#long');
        $view->geo_lat = $this->getlocationValue('http://www.w3.org/2003/01/geo/wgs84_pos#lat');
        $view->postal = $this->getlocationValue('http://www.w3.org/2006/vcard/ns#postal-code');
        $view->locality = $this->getlocationValue('http://www.w3.org/2006/vcard/ns#locality');
        $view->street = $this->getlocationValue('http://www.w3.org/2006/vcard/ns#street-address');

        return $view;
    }

    private function getValue ($element) {
        if ($elementObject = $this->storageInfo->{$this->storageUrl}->{$element}) {
            if (isset($elementObject[0])) {
                return $elementObject[0]->value;
            }
        }
        return null;
    }

    private function getLocationValue ($detail) {
        if ($elementObject = $this->storageInfo->{$this->storageUrl}->{'http://www.w3.org/2003/01/geo/wgs84_pos#location'}) {
            if (!isset($elementObject[0])) {
                return null;
            }
            if ($locationObject = $this->storageInfo->{$elementObject[0]->value}) {
                if ($locationDetailObject = $locationObject->{$detail}) {
                    if (isset($locationDetailObject[0])) {
                        return $locationDetailObject[0]->value;
                    }
                }
            }
        }
        return null;
    }
}

