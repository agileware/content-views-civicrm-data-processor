<?php

/**
 * Content_Views_CiviCRM_Settings class.
 */
class Content_Views_CiviCRM_Settings {

	/**
	 * Plugin instance reference.
	 * @since 0.1
	 * @var object Reference to plugin instance
	 */
	protected $cvc;

	/**
	 * Filters settings management object view.
	 * @since 0.1
	 * @var object $query Content_Views_CiviCRM_Settings_Filter
	 */
	protected $filters;

	/**
	 * Display settings management object for view.
	 * @since 0.1
	 * @var object $display Content_Views_CiviCRM_Settings_Display
	 */
	protected $display;

	/**
	 * Constructor.
	 *
	 * @param object $cvc Reference to plugin instance
	 */
	public function __construct( $cvc ) {
		$this->cvc = $cvc;
		$this->include_files();
		$this->setup_objects();
	}

	/**
	 * Include files.
	 * @since 0.1
	 */
	protected function include_files() {
		include $this->cvc->get_path() . 'settings/class-cvc-settings-filter.php';
		include $this->cvc->get_path() . 'settings/class-cvc-settings-display.php';
	}

	/**
	 * Setup objects.
	 * @since 0.1
	 */
	protected function setup_objects() {
		$this->filters = new Content_Views_CiviCRM_Settings_Filter( $this->cvc );
		$this->display = new Content_Views_CiviCRM_Settings_Display( $this->cvc );
	}
}