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
  expected: '<description of what is expected to appear as availability>' # optional, text
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
  expected: 'access information'
  reason: 'to give an example for test cases'
  date: '2022-04-09'
  creator: 'Jon Doe'
  rules: 'no rule applicable'
  detailsLink: 'http://www.test.com'
```
