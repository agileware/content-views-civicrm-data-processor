<?php

/**
 * Content_Views_CiviCRM_Settings_Display class.
 */
class Content_Views_CiviCRM_Settings_Display {

	/**
	 * Plugin instance reference.
	 * @since 0.1
	 * @var object Reference to plugin instance
	 */
	protected $cvc;

	/**
	 * Contact fields.
	 *
	 * @since 0.1
	 * @var array
	 */
	public $contact_fields = [];

	/**
	 * Constructor.
	 *
	 * @param object $cvc Reference to plugin instance
	 *
	 * @since 0.1
	 */
	public function __construct( $cvc ) {
		$this->cvc = $cvc;
		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 * @since 0.1
	 */
	public function register_hooks() {
		// filter display settings
		add_filter( PT_CV_PREFIX_ . 'display_settings', [ $this, 'filter_display_settings' ] );
	}

	/**
	 * Filter display settings.
	 *
	 * @param array $options Display settings field options
	 *
	 * @return array $options Filtered display settings field options
	 * @since 0.1
	 */
	public function filter_display_settings( $options ) {
		// all post but civicrm_contact post type
		$all_post_types_but_civicrm = array_diff( array_keys( PT_CV_Values::post_types() ), [ 'civicrm' ] );
		return array_reduce( $options, function ( $options, $group ) use ( $all_post_types_but_civicrm ) {
			if ( isset( $group['label']['text'] ) && $group['label']['text'] == 'Fields settings' ) {
				// set dependence to all posts
				// needed to toggle off field settings for contact post type
				$group['dependence'] = [ 'content-type', $all_post_types_but_civicrm ];
			}
			$options[] = $group;
			return $options;
		}, [] );
	}
}
