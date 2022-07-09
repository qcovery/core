<?php

namespace AvailabilityPlus\Resolver\Driver;

class PluginManager extends \VuFind\Resolver\Driver\PluginManager
{
    /**
     * Default plugin aliases.
     *
     * @var array
     */
    protected $aliases = [
        '360link' => 'VuFind\Resolver\Driver\Threesixtylink',
        'demo' => 'VuFind\Resolver\Driver\Demo',
        'ezb' => 'VuFind\Resolver\Driver\Ezb',
        'sfx' => 'VuFind\Resolver\Driver\Sfx',
        'redi' => 'VuFind\Resolver\Driver\Redi',
        'threesixtylink' => 'VuFind\Resolver\Driver\Threesixtylink',
        'AvailabilityPlusResolver' => 'AvailabilityPlus\Resolver\Driver\AvailabilityPlusResolver',
        'DAIA' => 'AvailabilityPlus\Resolver\Driver\DAIA',
        'DAIAHsH' => 'AvailabilityPlus\Resolver\Driver\DAIAHsH',
        'DAIAKSF' => 'AvailabilityPlus\Resolver\Driver\DAIAKSF',
        'FulltextFinder' => 'AvailabilityPlus\Resolver\Driver\FulltextFinder',
        'JournalsOnlinePrint' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrint',
        'JournalsOnlinePrintElectronic' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrintElectronic',
        'JournalsOnlinePrintHsHElectronic' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrintHsHElectronic',
        'JournalsOnlinePrintKSFElectronic' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrintKSFElectronic',
        'JournalsOnlinePrintPrint' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrintPrint',
        'JournalsOnlinePrintHsHPrint' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrintHsHPrint',
        'JournalsOnlinePrintKSFPrint' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrintKSFPrint',
        'Subito' => 'AvailabilityPlus\Resolver\Driver\Subito',
        'SubitoISSN' => 'AvailabilityPlus\Resolver\Driver\SubitoISSN',
        'SubitoISBN' => 'AvailabilityPlus\Resolver\Driver\SubitoISBN',
    ];

    /**
     * Default plugin factories.
     *
     * @var array
     */
    protected $factories = [
        'VuFind\Resolver\Driver\Threesixtylink' =>
            'VuFind\Resolver\Driver\DriverWithHttpClientFactory',
        'VuFind\Resolver\Driver\Demo' =>
            'Zend\ServiceManager\Factory\InvokableFactory',
        'VuFind\Resolver\Driver\Ezb' =>
            'VuFind\Resolver\Driver\DriverWithHttpClientFactory',
        'VuFind\Resolver\Driver\Sfx' =>
            'VuFind\Resolver\Driver\DriverWithHttpClientFactory',
        'VuFind\Resolver\Driver\Redi' =>
            'VuFind\Resolver\Driver\DriverWithHttpClientFactory',
        'VuFind\Resolver\Driver\AvailabilityPlusResolver' =>
            'VuFind\Resolver\Driver\DriverWithHttpClientFactory',
        'VuFind\Resolver\Driver\DAIA' =>
            'VuFind\Resolver\Driver\DriverWithHttpClientFactory',
        'VuFind\Resolver\Driver\JournalsOnlinePrintElectronic' =>
            'VuFind\Resolver\Driver\DriverWithHttpClientFactory',
        'VuFind\Resolver\Driver\FulltextFinder' =>
            'VuFind\Resolver\Driver\DriverWithHttpClientFactory',
    ];

}

