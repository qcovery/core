<?php

return array (
  'controllers' =>
  array (
    'factories' =>
    array (
      'AvailabilityPlus\\Controller\\AvailabilityPlusController' => 'VuFind\\Controller\\AbstractBaseFactory',
    ),
    'aliases' =>
    array (
      'AvailabilityPlus' => 'AvailabilityPlus\\Controller\\AvailabilityPlusController',
      'availabilityplus' => 'AvailabilityPlus\\Controller\\AvailabilityPlusController',
    ),
  ),
  'router' =>
  array (
    'routes' =>
    array (
      'availabilityplus-home' =>
      array (
        'type' => 'Laminas\\Router\\Http\\Literal',
        'options' =>
        array (
          'route' => '/AvailabilityPlus/Home',
          'defaults' =>
          array (
            'controller' => 'AvailabilityPlus',
            'action' => 'Home',
          ),
        ),
      ),
      'availabilityplus-testcases' =>
      array (
        'type' => 'Laminas\\Router\\Http\\Literal',
        'options' =>
        array (
          'route' => '/AvailabilityPlus/TestCases',
          'defaults' =>
          array (
            'controller' => 'AvailabilityPlus',
            'action' => 'TestCases',
          ),
        ),
      ),
      'availabilityplus-debug' =>
      array (
        'type' => 'Laminas\\Router\\Http\\Segment',
        'options' =>
        array (
          'route' => '/AvailabilityPlus/Debug/[:id]',
          'constraints' =>
          array (
            'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
            'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
          ),
          'defaults' =>
          array (
            'controller' => 'AvailabilityPlus',
            'action' => 'Debug',
          ),
        ),
      ),
    ),
  ),
  'vufind' =>
  array (
    'plugin_managers' =>
    array (
      'ajaxhandler' =>
      array (
        'factories' =>
        array (
          'AvailabilityPlus\\AjaxHandler\\GetItemStatuses' => 'AvailabilityPlus\\AjaxHandler\\GetItemStatusesFactory',
        ),
        'aliases' =>
        array (
          'VuFind\\AjaxHandler\\GetItemStatuses' => 'AvailabilityPlus\\AjaxHandler\\GetItemStatuses',
        ),
      ),
      'resolver_driver' =>
      array (
        'factories' =>
        array (
          'AvailabilityPlus\\Resolver\\Driver\\AvailabilityPlusResolver' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\DAIA' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\DAIAJournal' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\DAIAHsH' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\DAIAKSF' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\DAIAParent' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\DAIAplus' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\FulltextFinder' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrint' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintHsHElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintKSFElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintPrint' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintHsHPrint' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintKSFPrint' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\Subito' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\SubitoISBN' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\SubitoISSN' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\Unpaywall' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'VuFind\\Resolver\\Driver\\AvailabilityPlusResolver' => 'VuFind\\Resolver\\Driver\\DriverWithHttpClientFactory',
        ),
        'aliases' =>
        array (
          'VuFind\\Resolver\\Driver\\AvailabilityPlusResolver' => 'AvailabilityPlus\\Resolver\\Driver\\AbstractBase',
          'AvailabilityPlusResolver' => 'AvailabilityPlus\\Resolver\\Driver\\AvailabilityPlusResolver',
          'DAIA' => 'AvailabilityPlus\\Resolver\\Driver\\DAIA',
          'DAIAJournal' => 'AvailabilityPlus\\Resolver\\Driver\\DAIAJournal',
          'DAIAHsH' => 'AvailabilityPlus\\Resolver\\Driver\\DAIAHsH',
          'DAIAKSF' => 'AvailabilityPlus\\Resolver\\Driver\\DAIAKSF',
          'DAIAParent' => 'AvailabilityPlus\\Resolver\\Driver\\DAIAParent',
          'FulltextFinder' => 'AvailabilityPlus\\Resolver\\Driver\\FulltextFinder',
          'JournalsOnlinePrint' => 'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrint',
          'JournalsOnlinePrintElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintElectronic',
          'JournalsOnlinePrintHsHElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintHsHElectronic',
          'JournalsOnlinePrintKSFElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintKSFElectronic',
          'JournalsOnlinePrintPrint' => 'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintPrint',
          'JournalsOnlinePrintHsHPrint' => 'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintHsHPrint',
          'JournalsOnlinePrintKSFPrint' => 'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintKSFPrint',
          'Subito' => 'AvailabilityPlus\\Resolver\\Driver\\Subito',
          'SubitoISBN' => 'AvailabilityPlus\\Resolver\\Driver\\SubitoISBN',
          'SubitoISSN' => 'AvailabilityPlus\\Resolver\\Driver\\SubitoISSN',
          'Unpaywall' => 'AvailabilityPlus\\Resolver\\Driver\\Unpaywall'
        ),
      ),
    ),
  ),
);
