<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin updates from GitHub releases.
 *
 * The plugin is not hosted on WordPress.org, so WordPress cannot find its
 * updates. This class offers the latest GitHub release of the repository as
 * a normal WordPress plugin update, the same way as Agileware's other
 * WordPress plugins (see wp-civicrm-ux). Releases are published by the
 * "Cut release" GitHub workflow.
 */
class Content_Views_CiviCRM_Upgrader {

	/**
	 * How long a successful release lookup is cached, in seconds.
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * How long a failed release lookup is cached, in seconds, so a GitHub
	 * outage or rate limit does not cause a request on every update check.
	 */
	const ERROR_CACHE_TTL = HOUR_IN_SECONDS;

	private $repo       = ''; // owner/name on GitHub
	private $plugin_uri = '';
	private $plugin     = ''; // folder/filename.php
	private $plugin_dir = ''; // absolute path of the installed plugin folder
	private $name       = '';
	private $slug       = '';
	private $version    = '';
	private $author     = '';
	private $author_uri = '';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file The main plugin file.
	 * @param string $repo The GitHub repository, as owner/name.
	 */
	public function __construct( $plugin_file, $repo ) {
		$plugin_data = get_file_data( $plugin_file, [
			'PluginName' => 'Plugin Name',
			'PluginURI'  => 'Plugin URI',
			'Version'    => 'Version',
			'Author'     => 'Author',
			'AuthorURI'  => 'Author URI',
		] );

		$this->repo       = $repo;
		$this->plugin_uri = $plugin_data['PluginURI'];
		$this->plugin     = plugin_basename( $plugin_file );
		$this->plugin_dir = dirname( $plugin_file );
		$this->name       = $plugin_data['PluginName'];
		$this->slug       = basename( $this->plugin_dir );
		$this->version    = $plugin_data['Version'];
		$this->author     = $plugin_data['Author'];
		$this->author_uri = $plugin_data['AuthorURI'];
	}

	/**
	 * Initialize the updater by hooking into WordPress.
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugins_api_filter' ], 10, 3 );
		add_filter( 'upgrader_source_selection', [ $this, 'fix_plugin_directory_name' ], 10, 4 );
	}

	/**
	 * Check for plugin updates.
	 *
	 * @param object $transient The current update transient.
	 *
	 * @return object Modified update transient with potential update data.
	 */
	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}

		if ( ! empty( $transient->response[ $this->plugin ] ) ) {
			return $transient;
		}

		$update_info = $this->get_update_info();
		if ( ! $update_info ) {
			return $transient;
		}

		$plugin = (object) [
			'id'          => $this->plugin,
			'slug'        => $this->slug,
			'plugin'      => $this->plugin,
			'new_version' => $update_info->version,
			'url'         => $update_info->html_url,
			'package'     => $update_info->zipball_url,
		];

		// Listing the plugin in no_update as well lets WordPress offer the
		// auto-updates setting for it.
		if ( version_compare( $this->version, $update_info->version, '<' ) ) {
			$transient->response[ $this->plugin ] = $plugin;
		} else {
			$transient->no_update[ $this->plugin ] = $plugin;
		}

		return $transient;
	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @param mixed $result
	 * @param string $action
	 * @param object $args
	 *
	 * @return mixed
	 */
	public function plugins_api_filter( $result, $action = '', $args = null ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || ( $args->slug !== $this->slug ) ) {
			return $result;
		}

		$update_info = $this->get_update_info();
		if ( ! $update_info ) {
			return $result;
		}

		$plugin_info                = new stdClass();
		$plugin_info->name          = $this->name;
		$plugin_info->slug          = $this->slug;
		$plugin_info->version       = $update_info->version;
		$plugin_info->author        = sprintf( '<a href="%s">%s</a>', esc_url( $this->author_uri ), esc_html( $this->author ) );
		$plugin_info->homepage      = $this->plugin_uri;
		$plugin_info->download_link = $update_info->zipball_url;
		// WordPress filters the sections through wp_kses() before showing them.
		$plugin_info->sections = [
			'release_notes' => $update_info->html_body,
		];

		return $plugin_info;
	}

	/**
	 * Convert the Markdown release notes to HTML using GitHub's Markdown API.
	 *
	 * @param string $markdown_body
	 *
	 * @return string HTML, or the escaped Markdown when the conversion fails
	 */
	private function convert_markdown_to_html( $markdown_body ) {
		$response = wp_remote_post( 'https://api.github.com/markdown', [
			'headers' => [
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => $this->name . ' Plugin Updater',
			],
			'body'    => wp_json_encode( [
				'text'    => (string) $markdown_body,
				'mode'    => 'gfm',
				'context' => $this->repo,
			] ),
		] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return '<pre>' . esc_html( (string) $markdown_body ) . '</pre>';
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Get the latest release information from cached data or from GitHub.
	 *
	 * @return object|false Latest release information or false on failure.
	 */
	private function get_update_info() {
		$version_info = $this->allow_cached() ? get_transient( $this->get_cache_key() ) : false;

		if ( false === $version_info ) {
			$version_info = $this->get_update_info_from_remote();

			if ( $version_info ) {
				// Cache the body after converting from Markdown to HTML
				$version_info->html_body = $this->convert_markdown_to_html( $version_info->body ?? '' );
				set_transient( $this->get_cache_key(), $version_info, self::CACHE_TTL );
			} else {
				// Remember the failure for a while instead of retrying on every check.
				set_transient( $this->get_cache_key(), 'error', self::ERROR_CACHE_TTL );
			}
		}

		return is_object( $version_info ) ? $version_info : false;
	}

	/**
	 * Get the latest release information from the GitHub repository.
	 *
	 * @return object|false Latest release information or false on failure.
	 */
	private function get_update_info_from_remote() {
		$response = wp_remote_get( 'https://api.github.com/repos/' . $this->repo . '/releases/latest', [
			'headers' => [
				'User-Agent' => $this->name . ' Plugin Request', // GitHub API requires a User-Agent header.
				'Accept'     => 'application/vnd.github+json',
			],
		] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		// Only accept a release with a plain version tag and a download from
		// this repository on GitHub.
		if ( ! is_object( $data ) || empty( $data->tag_name ) || ! is_string( $data->tag_name )
		     || ! preg_match( '/^v?\d+(\.\d+)*$/', $data->tag_name )
		     || empty( $data->zipball_url ) || ! is_string( $data->zipball_url )
		     || 0 !== strpos( $data->zipball_url, 'https://api.github.com/repos/' . $this->repo . '/' ) ) {
			return false;
		}

		$data->version  = ltrim( $data->tag_name, 'v' );
		$data->html_url = ( isset( $data->html_url ) && is_string( $data->html_url ) ) ? $data->html_url : $this->plugin_uri;

		return $data;
	}

	/**
	 * Gets the unique key (option name) for the cached release information.
	 *
	 * @return string
	 */
	private function get_cache_key() {
		return 'cvc_vi_' . md5( $this->slug );
	}

	/**
	 * Whether cached release information can be used. The "Check again"
	 * button on the WordPress Updates page skips the cache.
	 *
	 * @return bool
	 */
	private function allow_cached() {
		return empty( $_GET['force-check'] ) || ! current_user_can( 'update_plugins' );
	}

	/**
	 * Fix the directory name of the plugin during the update process.
	 *
	 * Release archives from GitHub unpack into a folder named after the
	 * owner, repository and commit. WordPress installs the update under that
	 * folder name, so it must be renamed to the folder the plugin is currently
	 * installed in.
	 *
	 * @param string $source The source path of the update.
	 * @param string $remote_source The remote source path of the update.
	 * @param WP_Upgrader $upgrader The WP_Upgrader instance performing the update.
	 * @param array $hook_extra Additional arguments passed to the filter.
	 *
	 * @return string|WP_Error Modified source path.
	 */
	public function fix_plugin_directory_name( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		if ( ! isset( $source, $remote_source, $upgrader, $wp_filesystem ) ) {
			return $source;
		}

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin ) {
			return $source;
		}

		$corrected_source = trailingslashit( $remote_source ) . basename( $this->plugin_dir ) . '/';

		if ( trailingslashit( $source ) === $corrected_source ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
			return $corrected_source;
		}

		return new WP_Error( 'rename_failed', __( 'Failed to rename plugin directory to match the existing directory.', 'content-views-civicrm' ) );
	}
}
