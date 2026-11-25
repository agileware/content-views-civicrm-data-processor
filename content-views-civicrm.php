<?php
/**
 * Plugin Name: Content Views CiviCRM
 * Description: CiviCRM integraton for Content Views.
 * Version: 0.2.4
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Agileware
 * Author URI: https://agileware.com.au/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Plugin URI: https://github.com/agileware/content-views-civicrm-data-processor
 * GitHub Plugin URI: agileware/content-views-civicrm-data-processor
 * Text Domain: content-views-civicrm
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Content_Views_CiviCRM {

	/**
	 * Version.
	 * @since 0.1
	 * @var string $version
	 */
	protected $version = '0.2.4';

	/**
	 * Pro dependency.
	 * @since 0.1
	 * @var bool $pro
	 */
	private $pro;

	/**
	 * Plugin path.
	 * @since 0.1
	 * @var string $path
	 */
	private $path;

	/**
	 * Plugin url.
	 * @since 0.1
	 * @var string $url
	 */
	private $url;

	/**
	 * Query management object.
	 * @since 0.1
	 * @var object $query Content_Views_CiviCRM_Query
	 */
	protected $query;

	/**
	 * Settings management object.
	 * @since 0.1
	 * @var object $settings Content_Views_CiviCRM_Settings
	 */
	protected $settings;

	/**
	 * Settings management object.
	 * @since 0.1
	 * @var object $display Content_Views_CiviCRM_Display
	 */
	protected $display;

	/**
	 * Settings management object.
	 * @since 0.1
	 * @var object $display Content_Views_CiviCRM_Filter
	 */
	protected $filter;

	/**
	 * @since 0.1
	 * @var object $dp_options Content_Views_CiviCRM_Dp_Option
	 */
	public $dp_options;

	/**
	 * CiviCRM Api object.
	 *
	 * @since 0.1
	 * @var Content_Views_CiviCRM_Api
	 */
	public $api;

	/**
	 * The plugin instance.
	 * @since 0.1
	 * @var object $instance The plugin instance
	 */
	private static $instance;

	/**
	 * Returns a single instance of this object when called.
	 * @return object $instance Content_Views_CiviCRM instance
	 * @since 0.1.1
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// instantiate
			self::$instance = new self;

			self::$instance->init();

			/**
			 * Broadcast that this plugin is loaded.
			 * @since 0.1
			 */
			do_action( 'content_views_civicrm_loaded' );

		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 * @since 0.1
	 */
	private function __construct() {
		// plugin path
		$this->path = plugin_dir_path( __FILE__ );
		// plugin url
		$this->url = plugin_dir_url( __FILE__ );
	}

	/**
	 * Check plugin dependencies.
	 *
	 * @param bool $local_civicrm whether CiviCRM must be installed on this site,
	 *   false when a custom API object talks to CiviCRM
	 *
	 * @return bool True if dependencies exist
	 * @since 0.1
	 */
	private function check_dependencies( $local_civicrm = true ) {
		// content views
		if ( ! class_exists( 'PT_Content_Views' ) ) {
			return false;
		}
		// civi
		if ( $local_civicrm && ! function_exists( 'civi_wp' ) ) {
			return false;
		}

		// good to go
		return true;
	}

	/**
	 * Intitialize.
	 * @since 0.1
	 */
	private function init() {
		$this->include_files();
		$this->setup_objects();
	}

	/**
	 * Include files.
	 * @since 0.1
	 */
	private function include_files() {
		include $this->path . 'includes/class-cvc-query.php';
		include $this->path . 'includes/class-cvc-settings.php';
		include $this->path . 'includes/class-cvc-api.php';
		include $this->path . 'includes/class-cvc-display.php';
		include $this->path . 'includes/class-cvc-filter.php';
		include $this->path . 'includes/class-cvc-dp-option.php';

		/**
		 * Broadcast that this plugin's files have bee included.
		 *
		 * @since 0.1
		 */
		do_action( 'content_views_civicrm_files_included' );
	}

	/**
	 * Setup objects.
	 * @since 0.1
	 */
	private function setup_objects() {

		/**
		 * Filter CiviCRM Api object.
		 *
		 * Hook your own API object, for example one that calls a CiviCRM
		 * installation on another server. The only requirement is a public
		 * call( $entity, $action, $params ) method that returns an APIv3 style
		 * result array ('is_error', 'count', 'values') and may throw an
		 * Exception on errors. See the README.
		 *
		 * @since 0.1
		 */
		$api = apply_filters( 'content_views_civicrm_api_object', false );
		if ( is_object( $api ) && ! is_callable( [ $api, 'call' ] ) ) {
			Content_Views_CiviCRM_Api::log_error( 'Content Views CiviCRM: the content_views_civicrm_api_object filter returned an object without a call() method, it is ignored.' );
			$api = false;
		}

		if ( ! $this->check_dependencies( ! is_object( $api ) ) ) {

			add_action( 'admin_notices', [ $this, 'show_notice' ] );

			return;

		}

		if ( ! is_object( $api ) ) {
			$api = new Content_Views_CiviCRM_Api;
		} elseif ( ! $api instanceof Content_Views_CiviCRM_Api ) {
			$api = new Content_Views_CiviCRM_Api_Adapter( $api );
		}
		$this->api      = $api;
		$this->query    = new Content_Views_CiviCRM_Query( $this );
		$this->settings = new Content_Views_CiviCRM_Settings( $this );
		$this->display = new Content_Views_CiviCRM_Display( $this );
		$this->filter = new Content_Views_CiviCRM_Filter( $this );
		$this->dp_options = new Content_Views_CiviCRM_Dp_Option( $this );
	}

	/**
	 * Shows admin notice.
	 *
	 * @since 0.1
	 */
	public function show_notice() {

		echo '<div class="notice notice-error"><p>' . esc_html__( 'Content Views CiviCRM requires the Content Views plugin and CiviCRM to be installed and activated.', 'content-views-civicrm' ) . '</p></div>';

	}

	/**
	 * Plugin path.
	 * @return string $path
	 * @since 0.1
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Plugins url.
	 * @return string $url
	 * @since 0.1
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Pro plugin enabled.
	 * @return bool $pro
	 * @since 0.1
	 */
	public function has_pro() {
		if ( ! isset( $this->pro ) ) {
			$this->pro = defined( 'PT_CV_VERSION_PRO' );
		}
		return $this->pro;
	}

}

function CVC() {
	return Content_Views_CiviCRM::instance();
}

// initialize
add_action( 'init', 'CVC' );
