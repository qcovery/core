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
  ),
  'vufind' => 
  array (
    'plugin_managers' => 
    array (
      'ajaxhandler' => 
      array (
        'factories' => 
        array (
          'DAIAplus\\AjaxHandler\\GetResolverLinks' => 'DAIAplus\\AjaxHandler\\GetResolverLinksFactory',
        ),
        'aliases' => 
        array (
          'VuFind\\AjaxHandler\\GetResolverLinks' => 'DAIAplus\\AjaxHandler\\GetResolverLinks',
        ),
      ),
    ),
  ),
);