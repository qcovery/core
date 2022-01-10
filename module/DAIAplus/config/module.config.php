<?php

return array (
  'service_manager' => 
  array (
    'allow_override' => true,
    'factories' => 
    array (
      'DAIAplus\\AjaxHandler\\PluginManager' => 'VuFind\\ServiceManager\\AbstractPluginManagerFactory',
      'DAIAplus\\ILS\\Connection' => 'DAIAplus\\ILS\\ConnectionFactory',
      'DAIAplus\\ILS\\Driver\\PluginManager' => 'VuFind\\ServiceManager\\AbstractPluginManagerFactory',
    ),
    'aliases' => 
    array (
      'VuFind\\AjaxHandler\\PluginManager' => 'DAIAplus\\AjaxHandler\\PluginManager',
      'VuFind\\ILSConnection' => 'DAIAplus\\ILS\\Connection',
      'VuFind\\ILSDriverPluginManager' => 'DAIAplus\\ILS\\Driver\\PluginManager',
    ),
  );
);