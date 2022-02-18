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
        'JournalsOnlinePrint' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrint',
        'JournalsOnlinePrintElectronic' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrintElectronic',
        'JournalsOnlinePrintPrint' => 'AvailabilityPlus\Resolver\Driver\JournalsOnlinePrintPrint',
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

    ];

}

