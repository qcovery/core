# AvailabilityPlus - Module Description

## Test-Tool
--> short description of Test-Tool to be added
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
    listview: # expected availability for result list view, optional, array
      description: '<description of what is expected to appear as availability in listview, supersedes any node with text or html directly below expected>' # optional, text
      description_xy: '<description of what is expected to appear as availability in listview for specific language selected in VuFind, xy represents language code, supersedes expected->listview->description>' # optional, text, multiple possible, up to one for every language code used in VuFind installation
      html: '<html which is expected to appear as availability in listview, supersedes expected->listview->description_xy>' # optional, html
      html_xy: '<html which is expected to appear as availability in listview for specific language selected in VuFind, xy represents language code, supersedes expected->öistview->html>' # optional, html, multiple possible, up to one for every language code used in VuFind installation
    recordview: # expected availability for record view, optional, array
      description: '<description of what is expected to appear as availability in recordview, supersedes any node with text or html directly below expected>' # optional, text
      description_xy: '<description of what is expected to appear as availability in recordview for specific language selected in VuFind, xy represents language code, supersedes expected->recordview->description>' # optional, text, multiple possible, up to one for every language code used in VuFind installation
      html: '<html which is expected to appear as availability in recordview, supersedes expected->recordview->description_xy>' # optional, html
      html_xy: '<html which is expected to appear as availability in recordview for specific language selected in VuFind, xy represents language code, supersedes expected->öistview->html>' # optional, html, multiple possible, up to one for every language code used in VuFind installation
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
    listview:
      html_en: '<a href="https://www.fulltext.com">Fulltext</a>'
      html_de: '<a href="https://www.fulltext.com">Volltext</a>'
    recordview:
      description_en: 'access information'
      description_de: 'Zugangsinformation'
  reason: 'to give an example for test cases'
  date: '2022-04-09'
  creator: 'Jon Doe'
  rules: 'no rule applicable'
  detailsLink: 'http://www.test.com'
```