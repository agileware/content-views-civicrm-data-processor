# Content Views CiviCRM - Content Views integration with the CiviCRM Data Processor

Plugin for integrating [Content Views](https://wordpress.org/plugins/content-views-query-and-display-post-page/) with [CiviCRM](https://civicrm.org) using the [CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) as the data source.

## Requirements

[CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) must be installed in the CiviCRM site to use this integration with WordPress and CiviCRM.

[Content Views](https://wordpress.org/plugins/content-views-query-and-display-post-page/) plugin must be installed in WordPress.

## Quick guide

#### Add a new Data Processor in CiviCRM:
1. Select your data sources
1. Add an api output
1. Select the fields, must include a field named id (this field won't be displayed).
1. Select the filters - uncheck the exposed flag if you don't want it to be a live filter.
1. (optional) set default value for filters
1. order the fields and filters in the way you want
1. save it

Then, you can go to content views and select civicrm content type. You will find it under the Data Processor dropdown. Preview it!

## Content Views Pro is optional

Content Views Pro is not required for this plugin to function. Content Views Pro provides a "live filters" feature in Content Views. If you want to use this feature then we recommend purchasing a [Content Views Pro subscription](https://www.contentviewspro.com/).

The following patch [patches/filter.patch](https://github.com/agileware/content-views-civicrm-data-processor/blob/master/patches/filter.patch) needs to be applied to the **Content Views** plugin (_not the Content Views Pro plugin_). This will then enable the CiviCRM and Data Processor options to be shown in the Filter Settings tab when using Content Views Pro. 

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
To control the display of fields you can modify the system name for each field in the data processor.

Set the extra options by appending `_cvc_XX` to the field name:
- `r`: HREF: TODO How does this work?
- `h`: Hide the field label.
- `l`: Display a multivalue field as a HTML list.
- `i`: User Contact ID: TODO How does this work?
- `s`: Contact Name search: TODO How does this work?

#### Examples:
- To display a list: `field_name_cvc_l`
- Check the href link and hide label: `field_name_cvc_rh`
- `field_name` or `field_name_cvc_` for empty option

1. Create a field for contact display name.
1. The default system name is `display_name`.
1. To display without label change the system name to: `display_name_cvc_h`
