# Contributing to Content Views CiviCRM

This guide is for developers who want to help maintain or extend this plugin. If you just want to *use* the plugin, see the [User Guide](USAGE.md) instead.

## Architecture overview

The plugin bootstraps from `content-views-civicrm.php`. On the WordPress `init` hook it creates the `Content_Views_CiviCRM` singleton (`CVC()`), which includes the class files and creates one object per class:

- `Content_Views_CiviCRM_Api` (`includes/class-cvc-api.php`): the only place that calls the CiviCRM API. All other classes call CiviCRM through `$cvc->api`. `Content_Views_CiviCRM_Api_Adapter` in the same file wraps a custom API object (see [Hooks](#hooks)).
- `Content_Views_CiviCRM_Query` (`includes/class-cvc-query.php`): builds the API parameters for a view and replaces the view's `WP_Query` with the Data Processor results.
- `Content_Views_CiviCRM_Display` (`includes/class-cvc-display.php`): renders the fields of each item, the item links and the total count for pagination.
- `Content_Views_CiviCRM_Filter` (`includes/class-cvc-filter.php`): renders the live filter inputs when Content Views Pro is active.
- `Content_Views_CiviCRM_Dp_Option` (`includes/class-cvc-dp-option.php`): parses the `_cvc_` options from field and filter names.
- `Content_Views_CiviCRM_Settings` (`includes/class-cvc-settings.php`) and the classes in `settings/`: add the CiviCRM content type and its settings to the Content Views admin screens.

### How a view is queried

Content Views builds a `WP_Query` for each view. For a view with the `civicrm` content type:

1. The `pt_cv_query_parameters` filter (`Content_Views_CiviCRM_Query::alter_query_parameters()`) builds the CiviCRM API parameters from the view settings, the live filter values and the URL.
2. The `pt_cv_query_params` filter registers a one-off `posts_pre_query` filter for that view's query.
3. The `posts_pre_query` filter calls the Data Processor API output and returns one mock `WP_Post` per result, so `WP_Query` never runs its SQL. The filter removes itself after it runs and ignores every query that is not for the `civicrm` post type.

Content Views keys items by post ID, so every mock post needs a unique ID. Results without an `id` field, or with a repeated `id`, get a negative placeholder ID. Item links use the result's own `id` field, never the post ID.

## Directory structure

- `content-views-civicrm.php`: plugin header and bootstrap.
- `includes/`: the classes described above, and the plugin updater (see [Plugin updates](#plugin-updates)).
- `settings/`: the Content Views admin settings for the CiviCRM content type. The CiviCRM filter settings are added through the `pt_cv_custom_filters` hook, after and alongside any settings Content Views Pro adds there.
- `assets/js/`: admin scripts. `admin-filter-settings.js` hides the Content Views **Common** and **Advance** filter groups on the view edit screen when the content type is CiviCRM, since those settings do not apply to CiviCRM views.
- `logo/`: images used by the README.
- `.github/workflows/`: the release workflow, see [Versioning and releases](#versioning-and-releases).

## Hooks

### Filters

- `content_views_civicrm_api_object`: supply your own API object instead of calling the local CiviCRM. See [Using a CiviCRM installation on another server](#using-a-civicrm-installation-on-another-server).
- `content_views_civicrm_check_permissions`: return `false` to call Data Processor API outputs without CiviCRM permission checks. Receives the Data Processor output as the second argument. Defaults to `true`.
- `content_views_civicrm_current_contact_id`: the CiviCRM contact id of the logged-in user, for the current contact (`_cvc_i`) filter. Defaults to the local CiviCRM session's contact id.

### Actions

- `content_views_civicrm_files_included`: after the plugin's class files are included.
- `content_views_civicrm_loaded`: after the plugin has been set up.

## Using a CiviCRM installation on another server

By default the plugin calls CiviCRM installed on the same WordPress site. The plugin does not include a client for a remote CiviCRM, but a developer can supply one with the `content_views_civicrm_api_object` filter. CiviCRM then does not need to be installed on the WordPress site, but Content Views still does.

The object only needs a public `call( $entity, $action, $params )` method. It must make an APIv3 call and return the result array (`is_error`, `count`, `values`), and may throw an `Exception` on errors. For example:

```php
class My_Remote_CiviCRM_Api {
	public function call( $entity, $action, $params ) {
		// Call the remote CiviCRM APIv3 here, for example through CiviProxy or
		// the CiviMRF connector plugin, and return the decoded result array.
	}
}

add_filter( 'content_views_civicrm_api_object', function () {
	return new My_Remote_CiviCRM_Api();
} );
```

Things to consider before using a remote CiviCRM:

- **Permissions:** every call runs as the CiviCRM user that owns the API key, not as the visitor, so the Data Processor output permission no longer limits what each visitor sees. Use a dedicated API user with only the permissions the public views need.
- **Current contact filter (`_cvc_i`):** the remote CiviCRM does not know who is logged in to WordPress. Supply the logged-in user's remote contact id with the `content_views_civicrm_current_contact_id` filter, otherwise views using this filter show no results.
- **Speed:** each view makes several API calls per page, so consider caching in your API object.

## Security rules for changes

Views are rendered for anonymous visitors, including through the Content Views pagination AJAX action, so treat every request value as hostile:

- Never copy request data (`$_POST`, `$_GET`, `$_REQUEST`) into CiviCRM API parameters directly. Only accept values for the Data Processor's exposed filters, and only scalar values.
- Never let a request value replace the current contact (`_cvc_i`) filter.
- Keep `check_permissions` on the Data Processor API output calls.
- Escape everything printed into HTML: `esc_html()` for text, `esc_attr()` for attributes, `esc_url()` for URLs, and `wp_kses_post()` where formatting HTML is allowed. The mock posts are marked `raw`, so WordPress does not sanitise them for you.
- Do not show CiviCRM API error messages to visitors. `Content_Views_CiviCRM_Api::call()` logs them instead.

## Dependencies

- There are no Composer or JavaScript dependencies, and no build step.
- There is no automated test suite in this repository. Verify changes manually against a CiviCRM/WordPress site with Content Views (and Content Views Pro, for live filters) before releasing.

## Versioning and releases

- The plugin version lives in two places in `content-views-civicrm.php`: the `Version:` header and the `$version` property. Bump both and merge that to `master` as part of any release.
- Releases are published by the **Cut release** GitHub workflow (`.github/workflows/cut-release.yml`, using the shared `agileware/ci-workflows` workflow). Run it from the repository's **Actions** tab with the new version number, for example `0.2.4`. It checks the `Version:` header matches, copies `master` to the `release` branch without the development files (`.github`, `CONTRIBUTING.md`, `.gitignore`), tags it, and publishes the GitHub release. Do not tag releases by hand.
- The `release` branch must exist on GitHub before the workflow is first run.

## Plugin updates

The plugin is not hosted on WordPress.org, so it provides its own updates from GitHub releases, the same way as Agileware's other WordPress plugins. `Content_Views_CiviCRM_Upgrader` (`includes/class-cvc-upgrader.php`) is loaded from `content-views-civicrm.php`:

- When WordPress checks for plugin updates, it reads the latest GitHub release of `agileware/content-views-civicrm-data-processor` and offers it as an update when its tag is a newer version than the installed `Version:` header. The result is cached for 12 hours (1 hour after a failed lookup). **Check again** on the WordPress Updates page skips the cache.
- The update downloads the release's source archive, so everything on the `release` branch is installed on client sites.
- GitHub archives unpack into a folder named after the repository and commit, so the updater renames it to the folder the plugin is installed in.
- The `Update URI` header stops WordPress from offering a WordPress.org plugin that happens to have the same folder name as an update.
