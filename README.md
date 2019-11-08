# Content Views CiviCRM with CiviCRM Data Processor

Plugin for integrating [Content Views](https://wordpress.org/plugins/content-views-query-and-display-post-page/) with [CiviCRM](https://civicrm.org) using the [CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) as the data source.

[CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) must be installed in the CiviCRM site to use this integration with WordPress and CiviCRM.

A quick guide to set up a data processor to be used in content views:
- add a data processor
- select your data sources
- add an api output
- select the fields, must include a field named id and a field named title (the field named id won't be displayed)
- select the filters - uncheck the exposed flag if you don't want it to be a live filter
- (optional) set default value for filters
- order the fields and filters in the way you want
- save it

Then, you can go to content views and select civicrm content type. You will find it under the data processor dropdown. Preview it!

## Add a live filter for contact name
- add a filter to the data processor
- the filter field is contact id
- give whatever the title you want
- change the name to *contact_name_search* 
- save it
