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
        'type' => 'Zend\\Router\\Http\\Literal',
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
        'type' => 'Zend\\Router\\Http\\Literal',
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
        'type' => 'Zend\\Router\\Http\\Segment',
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
          'AvailabilityPlus\\Resolver\\Driver\\DAIAHsH' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\DAIAKSF' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\DAIAplus' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrint' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintHsHElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintKSFElectronic' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintPrint' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintHsHPrint' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
          'AvailabilityPlus\\Resolver\\Driver\\JournalsOnlinePrintKSFPrint' => 'AvailabilityPlus\\Resolver\\Driver\\DriverWithHttpClientFactory',
        ),
        'aliases' =>
        array (
          'VuFind\\Resolver\\Driver\\AvailabilityPlusResolver' => 'AvailabilityPlus\\Resolver\\Driver\\AbstractBase',
        ),
      ),
    ),
  ),
  'service_manager' =>
  array (
    'factories' =>
    array (
      'AvailabilityPlus\\Resolver\\Driver\\PluginManager' => 'AvailabilityPlus\\ServiceManager\\AbstractPluginManagerFactory',
    ),
    'aliases' =>
    array (
      'VuFind\\Resolver\\Driver\\PluginManager' => 'AvailabilityPlus\\Resolver\\Driver\\PluginManager',
    ),
  ),
);
