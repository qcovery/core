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

## Debug-Tool: Debugging of Availability Checks



/vufind/AvailabilityPlus/Debug/{id}
or
/AvailabilityPlus/Debug/{id}

Add &debug_ap=true to the URL and a link to the Debug-Tool for each record will appear

## TestCase-Tool: One Place to Check Returns of Availability Checks

... 
/vufind/AvailabilityPlus/TestCases 
or
/AvailabilityPlus/TestCases

### Configuration of Test Cases
Configuration file: availabilityplus-testcases.yaml

Format:
```yaml
<id>: # id of record in index
  driver: '<driver to load record>' # required, Solr, Search2, ...
  description: '<short description of test case>' # optional, text
  expected: # definition of expected results, optional, array
    description: '<description of what is expected to appear as availability>' # optional, text
    description_xy: '<description of what is expected to appear as availability for specific language selected in VuFind, xy represents language code, supersedes expected->description>' # optional, text, multiple possible, up to one for every language code used in VuFind installation
    html: '<html which is expected to appear as availability, supersedes expected->description_xy>' # optional, html
    html_xy: '<html which is expected to appear as availability for specific language selected in VuFind, xy represents language code, supersedes expected->html>' # optional, html, multiple possible, up to one for every language code used in VuFind installation
    resultlist: # expected availability for result list view, optional, array
      description: '<description of what is expected to appear as availability in listview, supersedes any node with text or html directly below expected>' # optional, text
      description_xy: '<description of what is expected to appear as availability in listview for specific language selected in VuFind, xy represents language code, supersedes expected->listview->description>' # optional, text, multiple possible, up to one for every language code used in VuFind installation
      html: '<html which is expected to appear as availability in listview, supersedes expected->listview->description_xy>' # optional, html
      html_xy: '<html which is expected to appear as availability in listview for specific language selected in VuFind, xy represents language code, supersedes expected->öistview->html>' # optional, html, multiple possible, up to one for every language code used in VuFind installation
    recordview: # expected availability for record view, optional, array
      description: '<description of what is expected to appear as availability in recordview, supersedes any node with text or html directly below expected>' # optional, text
      description_xy: '<description of what is expected to appear as availability in recordview for specific language selected in VuFind, xy represents language code, supersedes expected->recordview->description>' # optional, text, multiple possible, up to one for every language code used in VuFind installation
      html: '<html which is expected to appear as availability in recordview, supersedes expected->recordview->description_xy>' # optional, html
      html_xy: '<html which is expected to appear as availability in recordview for specific language selected in VuFind, xy represents language code, supersedes expected->öistview->html>' # optional, html, multiple possible, up to one for every language code used in VuFind installation
  title: '<title of publication as reference>' # optional, text
  reason: '<reason for adding test case>' # optional, text
  date: '<date when test case was added>' # optional, date yyyy-mm-dd
  creator: '<name of person who added or suggested test case>' # optional, text
  rules: 'use to mention rule from availabilityplus-resolver-<driver>.yaml if tested by test case' # optional, text
  detailsLink: '<url which leads to more details about this test case, e.g. a GitHub Issue, an internal GitLab Issue>' # optional, url
```
Example:
```yaml
123456789:
  driver: 'Solr'
  description: 'sample description'
  expected:
    resultlist:
      html_en: '<a href="https://www.fulltext.com">Fulltext</a>'
      html_de: '<a href="https://www.fulltext.com">Volltext</a>'
    recordview:
      description_en: 'access information'
      description_de: 'Zugangsinformation'
  title: 'Example Title'
  reason: 'to give an example for test cases'
  date: '2022-04-09'
  creator: 'Jon Doe'
  rules: 'no rule applicable'
  detailsLink: 'http://www.test.com'
```

## ToDos
- Create Composer module
- remove hard-coded references to `/vufind/...`, so URLs are generated independently of whether `/vufind` is used with URL path
- rework MARC configuration in [../../config/vufind/availabilityplus.yaml](../../config/vufind/availabilityplus.yaml) to be more generic, currently it is based on the MARC structure used by [K10plus-Zentral](https://github.com/gbv/findex-config/tree/master/SolrCloud)
- Rework DAIA configuration and move grouping from templates to DAIA-Resolver
- Change Resolver interface to allow for providing MARC-Data for configuration or rules based on MARC-Data
- Add A+ parsing for SFX-Resolver
- Add CrossRef to get DOIs to pass on to Unpaywall
- rework availabilityplus-result-list.phtml and availabilityplus-result-list.phtml, only one Template might be necessary, potentially they might not be needed if parameter values can be obtained directly in [GetItemStatuses.php](src/AvailabilityPlus/AjaxHandler/GetItemStatuses.php#L94)
