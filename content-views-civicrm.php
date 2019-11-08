<?php
/**
 * Plugin Name: Content Views CiviCRM
 * Description: CiviCRM integraton for Content Views.
 * Version: 0.1.2
 * Author: Agileware
 * Author URI: https://github.com/agileware/content-views-civicrm
 * Plugin URI: https://github.com/agileware/content-views-civicrm
 * GitHub Plugin URI: agileware/content-views-civicrm
 * Text Domain: content-views-civicrm
 * Domain Path: /languages
 */

class Content_Views_CiviCRM {

	/**
	 * Version.
	 * @since 0.1
	 * @var string $version
	 */
	protected $version = '0.1.2';

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
	 * @return bool True if dependencies exist
	 * @since 0.1
	 */
	private function check_dependencies() {
		// content views
		if ( ! class_exists( 'PT_Content_Views' ) ) {
			return false;
		}
		// civi
		if ( ! function_exists( 'civi_wp' ) ) {
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
		 * Hook your own api wrapper implementation, the only
		 * requirement is that it must have a 'call' and 'call_values' method.
		 *
		 * @since 0.1
		 */
		$this->api = apply_filters( 'content_views_civicrm_api_object', false );

		if ( ! is_object( $this->api ) && ! $this->check_dependencies() ) {

			add_action( 'admin_notices', [ $this, 'show_notice' ] );

			return;

		}

		$this->api      = $this->api ? $this->api : ( new Content_Views_CiviCRM_Api );
		$this->query    = new Content_Views_CiviCRM_Query( $this );
		$this->settings = new Content_Views_CiviCRM_Settings( $this );
		$this->display = new Content_Views_CiviCRM_Display( $this );
		$this->filter = new Content_Views_CiviCRM_Filter( $this );

	}

	/**
	 * Shows admin notice.
	 *
	 * @since 0.1
	 */
	public function show_notice() {

		printf(
			__( '<div class="notice notice-error"><p>It looks like CiviCRM is not activated, Content Views CiviCRM integration requires CiviCRM to be installed and activated.</p></div>', 'content-views-civicrm' )
		);

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
