# Content Views CiviCRM - Display CiviCRM Data Processor results with Content Views

## Description

This plugin integrates [Content Views](https://wordpress.org/plugins/content-views-query-and-display-post-page/) with [CiviCRM](https://civicrm.org), using the [CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) as the data source. Any Data Processor with an API output can be listed on a WordPress page using the Content Views layouts, with optional live filters from Content Views Pro.

## Requirements

* WordPress 6.5 or later
* PHP 8.1 or later
* [Content Views](https://wordpress.org/plugins/content-views-query-and-display-post-page/)
* [CiviCRM](https://civicrm.org)
* [CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) extension
* [Content Views Pro](https://www.contentviewspro.com/) (optional, for live filters)

## Security and CiviCRM data

A view backed by CiviCRM shows CiviCRM data to everyone who can see the page it is placed on, including anonymous visitors. The data is fetched with CiviCRM permission checks enabled, so the permission set on each Data Processor **API output** decides what the person viewing the page can see.

For a public view, give the API output a permission that the anonymous role has in CiviCRM, and make sure the output only returns fields and records that are safe to publish. ***Anyone with that permission can read the whole output, not just the parts a view displays.***

When creating or editing a view, the **Data processor** list only shows the data processors whose API output permission the editor has in CiviCRM, so editors are not offered data they cannot read themselves. This limits the choice offered in the view editor; it does not replace trust. Anyone who can create or edit Content Views views decides what is published, so only give that capability to trusted staff.

## Features

* List the results of any CiviCRM Data Processor API output in Content Views layouts (grid, list and others)
* Item titles built from Data Processor fields, and item links to a page of your choice
* Sorting, limits and pagination
* Live filters with Content Views Pro, from the Data Processor's exposed filters, including a contact name search
* Views limited to the logged-in user's own CiviCRM contact
* Per-field display options: links, hidden labels and multi-value lists
* Support for a CiviCRM installation on another server, through a developer-supplied API object

## Usage

For more details on how to use this plugin, please read the [documentation](USAGE.md).

## For Developers

Please read the [contributing guide](CONTRIBUTING.md) for tips on how to maintain and extend this project.

To report a security vulnerability, please read the [security policy](SECURITY.md).

## About the Authors

This WordPress plugin was developed by the team at [Agileware](https://agileware.com.au).

[Agileware](https://agileware.com.au) provide a range of CiviCRM services including:

  * CiviCRM migration
  * CiviCRM integration
  * CiviCRM extension development
  * CiviCRM support
  * CiviCRM hosting
  * CiviCRM remote training services

Support your Australian [CiviCRM](https://civicrm.org) developers, [contact Agileware](https://agileware.com.au/contact) today!


![Agileware](logo/agileware-logo.png)
