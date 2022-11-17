# AvailabilityPlus - Module Description

Aim of module: To ...

## Installation (manual steps)

Currently only a manual installation is possible. The module will be made available as a Composer module in the future. 

- [ ] This branch needs to be cloned into a separate folder and then the files copied or linked in the vufind folder of the system in which AvailabilityPlus is to be integrated. 
- [ ] Required Module: [RecordDriver from this branch](../RecordDriver)
- [ ] Besides the Module Package these files are required:
  - [ ] AvailabilityPlus-Configuration [../../config/vufind](../../config/vufind) all files starting with availabilityplus
  - [ ] AvailabilityPlus-Theme [../../themes/availabilityplus](../../themes/availabilityplus)
  - [ ] AvailabilityPlus-Translations [languages](languages/) need to be added to the respective language files
- [ ] Changes to PluginManager.php-files might be required: References to GetItemStatuses and GetItemStatuses Factory need to be changed to the AvailabilityPlus Namespace
- [ ] Changes to Theme config: the availabilityplus theme needs to be added as a mixin
- [ ] The modules RecordDriver and AvailabilityPlus need to be added to the modules used by VuFind in the Apache configuration. AvailabilityPlus needs to be specified after RecordDriver as it depends on it. 
- [ ] If a custom js/check_item_statuses.js is in the Theme used, then it needs to be removed.
- [ ] Add rendering of availabilityplus-Templates
  - [ ] in lists: [availabilityplus-result-list.phtml](../../themes/availabilityplus/templates/RecordDriver/SolrDefault/availabilityplus-result-list.phtml)
  - [ ] in record view (preferably on the right hand side): [availabilityplus-view.phtml](../themes/availabilityplus/templates/record/availabilityplus-view.phtml)
- [ ] If your system does not use `/vufind` as root in the URL path, then it might be necessary to remove `/vufind´ in some configuration, template and module files. In some case the path is still hard-coded. An alternative could be to add a rewrite rule in your web server configuration.
Example for rendering:
```
<!--Module AvailabilityPlus-->
<?=$this->render('RecordDriver/SolrDefault/availabilityplus-result-list.phtml', ['driver' => $this->driver]) ?>
<!--Module AvailabilityPlus-->
```

## Module configuration

### Configuration of Order and Mode of Availability Checks

...

### Configuration of MARC-Data used for Availability Checks

...

### Configuration of Resolvers used for Availability Checks

...


## TestCase-Tool

The TestCase-Tool was developed to provide one page which displays the availability information for a set of configured Index-IDs. The configuration can also included a description or an expected result in HTML against which the actual result is being checked. An example how the TestCase-Tool works, can be found [here] (https://hilkat.uni-hildesheim.de/vufind/AvailabilityPlus/).

The TestCase-Tool can be configured [here](../../config/vufind/availabilityplus-testcases.yaml). The yaml-File provides comments on how to structure the configuration and an example.

The TestCase-Tool also provides links to the Debug-Tool. 

**It might be necessary to remove `/vufind` in URL paths set in [testcases.phtml](../../themes/availabilityplus/templates/availabilityplus/testcases.phtml), if your system is set up without using `/vufind`.**

## Debug-Tool



Depending on your setup you will find /vufind/AvailabilityPlus/Debug/{id}
or
/AvailabilityPlus/Debug/{id}

Add &debug_ap=true to the URL and a link to the Debug-Tool for each record will appear



## ToDos
- Create Composer module
- remove hard-coded references to `/vufind/...`, so URLs are generated independently of whether `/vufind` is used with URL path
- rework MARC configuration in [../../config/vufind/availabilityplus.yaml](../../config/vufind/availabilityplus.yaml) to be more generic, currently it is based on the MARC structure used by [K10plus-Zentral](https://github.com/gbv/findex-config/tree/master/SolrCloud)
- Rework DAIA configuration and move grouping from templates to DAIA-Resolver
- Change Resolver interface to allow for providing MARC-Data for configuration or rules based on MARC-Data
- Add A+ parsing for SFX-Resolver
- Add CrossRef to get DOIs to pass on to Unpaywall
- rework availabilityplus-result-list.phtml and availabilityplus-result-list.phtml, only one Template might be necessary, potentially they might not be needed if parameter values can be obtained directly in [GetItemStatuses.php](src/AvailabilityPlus/AjaxHandler/GetItemStatuses.php#L94)
