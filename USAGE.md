# Content Views CiviCRM User Guide

This plugin lists the results of a [CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) on a WordPress page, using the [Content Views](https://wordpress.org/plugins/content-views-query-and-display-post-page/) layouts.

## Security and CiviCRM data

A view shows CiviCRM data to everyone who can see its page, including anonymous visitors. The permission set on the Data Processor **API output** decides what the person viewing the page can see, so check it before publishing a view. Anyone with that permission can read the whole output, not just the parts the view displays, so only include fields and records that are safe to publish. See [Permissions](#permissions) below.

## Create a Data Processor in CiviCRM

1. Add a new Data Processor and select your data sources.
2. Add an **API output**.
3. Select the fields. Include a field named `id` (it is not displayed). It is used for the item link URL. Without it, all rows are still listed, but items are not linked and a warning is written to the CiviCRM log.
4. Select the filters. Uncheck **Exposed to user** for any filter that should not be a live filter.
5. Optionally, set default values for the filters.
6. Order the fields and filters the way you want them displayed.
7. Save the Data Processor.

### Permissions

The data is fetched with CiviCRM permission checks enabled, so the permission set on the Data Processor **API output** applies to the person viewing the page. For a view shown to anonymous visitors, set the output permission to one that the anonymous role has in CiviCRM. Choose this carefully, as anyone with that permission can read the output.

Sites that need the behaviour of earlier versions (no permission check) can opt out with:

```php
add_filter( 'content_views_civicrm_check_permissions', '__return_false' );
```

***With this filter, every Data Processor API output can be read by any visitor to a page with a CiviCRM view.***

## Create a view in Content Views

1. Add a new view in Content Views.
2. In **Filter Settings**, set **Content type** to **CiviCRM**.
3. In the **CiviCRM filter** settings:
   - **Data processor**: select the Data Processor to list.
   - **Sorting**: optionally, set the sort order as a CiviCRM API sort, for example `display_name ASC`.
   - **Limit**: optionally, set the maximum number of results. When pagination is enabled, the pagination **Items per page** setting is used instead.
4. In **Display Settings**, under **Title and links**:
   - **Title content**: the item title. Use `${field_name}` placeholders for Data Processor field values, for example `${display_name} (${city})`.
   - **CiviCRM link URL**: optionally, the page each item links to. The item's `id` is added as a parameter, for example `https://example.org/profile/?id=123`. Leave it empty for items without links.
5. Preview the view, then add its shortcode to a page.

The fields selected in the Data Processor API output are displayed for each item, in the order set in the Data Processor.

## Filters

The Data Processor's filters control which results are listed:

- A filter that is **Exposed to user** and not **Required** becomes a live filter (see below). Its value can also be set in the page URL, for example `?city=Perth`.
- A filter that is both **Exposed to user** and **Required** is used for the current contact filter (see the `i` option under [Extra options](#extra-options)).
- Filters that are not exposed use the default values set in the Data Processor.

## Live filters with Content Views Pro

[Content Views Pro](https://www.contentviewspro.com/) is not required for this plugin to function. It adds a "live filters" feature to Content Views, which lets visitors filter the results on the page.

With Content Views Pro, every exposed filter that is not required is shown with the results:

- Filters on a field with a list of options are shown as a dropdown. For a State/Province field, the options are limited to the countries in the CiviCRM **Available States and Provinces** setting.
- Other filters are shown as a text box.

The patch [patches/filter.patch](patches/filter.patch) needs to be applied to the **Content Views** plugin (*not the Content Views Pro plugin*). This enables the CiviCRM and Data Processor options to be shown in the Filter Settings tab when using Content Views Pro.

### Add a live filter for contact name

1. Add a filter to the Data Processor on a contact ID field.
2. Give it the title you want visitors to see.
3. Change its system name to end with `_cvc_s`, for example `contact_name_search_cvc_s`.
4. Save the Data Processor.

Visitors can then type part of a contact's name. The text is matched against the contacts' sort names, and only results for the matching contacts are listed.

## Extra options

To control how fields and filters behave, add options to their system name in the Data Processor. Append `_cvc_` followed by one or more option letters to the name:

| Option | Applies to | Effect |
| ------ | ---------- | ------ |
| `r` | Field | Display the value as a link, opening in a new tab. `https://` is added when the value does not start with `http`. |
| `h` | Field | Hide the field label. |
| `l` | Field | Display a comma-separated value as a list. |
| `i` | Filter | Current contact: limit the results to the logged-in user's CiviCRM contact. Use it on a filter that is both **Exposed to user** and **Required**. The value is always taken from the logged-in user and cannot be changed by visitors. Anonymous visitors see no results. |
| `s` | Filter | Contact name search: see [Add a live filter for contact name](#add-a-live-filter-for-contact-name). |

### Examples

- Display a list: `field_name_cvc_l`
- Display a link and hide the label: `field_name_cvc_rh`
- No options: `field_name` or `field_name_cvc_`
- Display a contact's name without a label:
  1. Add a field for the contact display name. Its default system name is `display_name`.
  2. Change the system name to `display_name_cvc_h`.
- Show only the logged-in user's own records: add a contact ID filter named `contact_id_cvc_i`, and set it as both **Exposed to user** and **Required**.

## Using a CiviCRM installation on another server

The plugin does not include a client for a CiviCRM installation on another server, but a developer can add one. See [Using a CiviCRM installation on another server](CONTRIBUTING.md#using-a-civicrm-installation-on-another-server) in the contributing guide.
