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
  expected_description: '<description of what is expected to appear as availability>' # optional, text
  expected_description_xy: '<description of what is expected to appear as availability for specific language selected in VuFind, xy represents language code, supersedes expected_description>' # optional, text, multiple possible, up to one for every language code used in VuFind installation
  expected_html: '<html which is expected to appear as availability, supersedes expected_description_xy>' # optional, html
  expected_html_xy: '<html which is expected to appear as availability for specific language selected in VuFind, xy represents language code, supersedes expected_html>' # optional, html, multiple possible, up to one for every language code used in VuFind installation
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
  expected_description: 'access information'
  expected_description_en: 'access information'
  expected_description_de: 'Zugangsinformation'
  expected_html: 'Zugangsinformation'
  expected_html_en: '<a href="https://www.fulltext.com">Fulltext</a>'
  expected_html_de: '<a href="https://www.fulltext.com">Volltext</a>'
  reason: 'to give an example for test cases'
  date: '2022-04-09'
  creator: 'Jon Doe'
  rules: 'no rule applicable'
  detailsLink: 'http://www.test.com'
```