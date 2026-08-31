# AvailabilityPlus - Module Description

**This module is developed for VuFind 10.X**  
**There is also a branch for VuFind 9.X (tested in 9.1.1)**  
(For VuFind 5 use [this branch](https://github.com/qcovery/core/blob/develop-5-aplus/module/AvailabilityPlus/))

Based on the original implementation, the logic of AvailabilityPlus was extracted and modularized in the course of the introduction of VuFind - by the Staatsbibliothek zu Berlin. It is now usable as an independent extension of VuFind.

The module tries to address the complex issue of checking and displaying availability information. The order and mode of availability checks can be configured for different Backends (Solr and Search2 have been confirmed to work) providing MARC-Data and for different media formats. It also provides a TestCase-Tool for displaying the availability information for a configured set of Index-Ids in on spot. Lastly, it provides a Debug-Tool which displays details on which checks were performed and their result. It was also an aim to be able to easily integrate different resolvers into this process and adjust their results based on configurable rules.

## Installation

- [ ] Clone/Download the project into your vufind root path  
  **we recommend to use `availabilityplus` as alias, because the shell script uses this name**
- [ ] Now you can run the symlink shell script
    ```
    . <repo-name>/symlink.sh
    ```
  **you can also symlink manually** use `ln -rs` for relative symlinks
    ```
    ln -s <path-to-availabilityplus-repo>/module/AvailabilityPlus <path-to-vufind>/module/
    ln -s <path-to-availabilityplus-repo>/themes/availabilityplus <path-to-vufind>/themes/
    ln -s <path-to-availabilityplus-repo>/local/config/vufind/* <path-to-vufind>/local/config/vufind/
    ln -s <path-to-availabilityplus-repo>/local/languages/AvailabilityPlus languages/
    ```
  e. g. ln -s /var/www/vufind/availabilityplus/local/config/vufind/* /var/www/vufind/local/config/vufind/
- [ ] You need to change your pluginmanager settings and extends to `AvailabilityPlus` for those files:
    ```
    <your-module>/RecordDriver/SolrMarc.php
    <your-module>/AjaxHandler/GetItemStatuses.php
    <your-module>/AjaxHandler/GetItemStatusesFactory.php
    ```
- [ ] You also need to check if you have duplicate function names with the AvailabilityPlus module.
- [ ] The module AvailabilityPlus need to be added to the modules used by VuFind in the Apache configuration. AvailabilityPlus needs to be specified before your custom Module.
- [ ] Add the `availabilityplus` theme as mixin in your default theme.
    ```
    'mixins' => [
        'availabilityplus',
    ]
    ```
- [ ] Add the availabilityplus snippet in your default theme. **use list = 0 for the record view and 1 for the result list**
    ```
    <!--Module AvailabilityPlus-->
    <?=$this->render('availabilityplus-snippet.phtml', ['driver' => $this->driver, 'additionalClass' => '', 'list' => '0']) ?>
    <!--Module AvailabilityPlus-->
    ```
- [ ] If you have a check_item_statuses.js in your default theme, then it needs to be removed.

## Module configuration

Configuration files in this module:
- Order and mode of availability checks: [availabilityplus.ini](local/config/vufind/availabilityplus.ini)
- MARC-Data used for availability checks and as parameter for resolvers: [availabilityplus.yaml](local/config/vufind/availabilityplus.yaml)
- TestCases: [availabilityplus-testcases.yaml](local/config/vufind/availabilityplus-testcases.yaml)
- Resolver:
  - Add your DAIA domain in the 'ResolverBaseURL' section in [availabilityplus-resolver.ini](local/config/vufind/availabilityplus-resolver.ini)
  - Add your `sid` in the 'ResolverExtraParams' section in [availabilityplus-resolver.ini](local/config/vufind/availabilityplus-resolver.ini) to verify your Library for JOP
  - Change the search types for JOP-Print [availabilityplus-resolver.ini](local/config/vufind/availabilityplus-resolver.ini)
  - Rules for adjusting resolver results, one file per resolver, with the name pattern `availabilityplus-resolver-<name-of-resolver>.yaml`, e. g. [availabilityplus-resolver-DAIA.yaml](local/config/vufind/availabilityplus-resolver-DAIA.yaml)

## TestCase-Tool

The TestCase-Tool was developed to provide a page which displays the availability information for a set of configured Index-IDs. The configuration can also include a description or an expected result in HTML against which the actual result is being checked. An example how the TestCase-Tool works, can be found [here](https://hilkat.uni-hildesheim.de/vufind/AvailabilityPlus/).

The TestCase-Tool can be configured in the [availabilityplus-testcases.yaml](local/config/vufind/availabilityplus-testcases.yaml). The yaml-File provides comments on how to structure the configuration and an example.

The TestCase-Tool (Url: `https://<your-domain>/AvailabilityPlus/TestCases`) also provides links to the Debug-Tool.

## Debug-Tool

The Debug-Tool was developed to display detailed information about which availability checks were performed and about their results. It can be accessed via the TestCase-Tool, by adding `debug_ap=true` as URL-parameter or by using `https://<your-domain>/AvailabilityPlus/Debug/<id>`. An example can be found [here](https://hilkat.uni-hildesheim.de/vufind/AvailabilityPlus/Debug/389600296?list=1&driver=Solr).

## TODO

- [ ] Create Composer module
- [ ] rework MARC configuration in [local/config/vufind/availabilityplus.yaml](local/config/vufind/availabilityplus.yaml) to be more generic, currently it is based on the MARC structure used by [K10plus-Zentral](https://github.com/gbv/findex-config/tree/master/SolrCloud)
- [ ] Rework DAIA configuration and move grouping from templates to DAIA-Resolver
- [ ] Change Resolver interface to allow for providing MARC-Data for configuration or rules based on MARC-Data
- [ ] Add A+ parsing for SFX-Resolver
- [ ] Add CrossRef to get DOIs to pass on to Unpaywall
- [ ] rework backend/source/driver handling to make sure it can be used with different backends, although at this point, the module will not work with backends not providing MARC-Data
