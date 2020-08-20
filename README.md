# Content Views CiviCRM - Content Views integration with the CiviCRM Data Processor

Plugin for integrating [Content Views](https://wordpress.org/plugins/content-views-query-and-display-post-page/) with [CiviCRM](https://civicrm.org) using the [CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) as the data source.

## Requirements

[CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) must be installed in the CiviCRM site to use this integration with WordPress and CiviCRM.

[Content Views](https://wordpress.org/plugins/content-views-query-and-display-post-page/) plugin must be installed in WordPress.

## Quick guide

- add a data processor
- select your data sources
- add an api output
- select the fields, must include a field named id (this field won't be displayed)
- format the title in the **display settings -> title content**
- select the filters - uncheck the exposed flag if you don't want it to be a live filter
- (optional) set default value for filters
- order the fields and filters in the way you want
- save it

Then, you can go to content views and select civicrm content type. You will find it under the Data Processor dropdown. Preview it!

## Content Views Pro is optional

Content Views Pro is not required for this plugin to function. Content Views Pro provides a "live filters" feature in Content Views. If you want to use this feature then we recommend purchasing a [Content Views Pro subscription](https://www.contentviewspro.com/).

The following patch [patches/filter.patch](https://github.com/agileware/content-views-civicrm-data-processor/blob/master/patches/filter.patch) needs to be applied to the **Content Views** plugin to then enable the CiviCRM options to be shown in the Filter Settings tab when using Content Views Pro. 

### Add a live filter for contact name

- add a filter to the Data Processor
- the filter field is contact id
- give whatever the title you want
- change the name to *contact_name_search* 
- save it

## Filters
The live filter only display filters with `exposed to user` and not `required`.

One case to use a filter with both `exposed to user` and `required` is a filter for current contact id.

## Extra options
Set the extra options with the field name.

Example:
- To display a list: `field_name_cvc_l`
- Check the href link and hide label: `field_name_cvc_rh`
- `field_name` or `field_name_cvc_` for empty option
```php
	const HREF = 'r';
	const HIDE_LABEL = 'h';
	const LIST = 'l';
	const USER_CONTACT_ID = 'i';
```
