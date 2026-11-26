<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content_Views_CiviCRM_Settings_Filter class.
 */
class Content_Views_CiviCRM_Settings_Filter {

	/**
	 * Plugin instance reference.
	 * @since 0.1
	 * @var Content_Views_CiviCRM Reference to plugin instance
	 */
	protected $cvc;

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
		add_filter( PT_CV_PREFIX_ . 'post_types_list', [ $this, 'filter_post_types_list' ] );
		// After Content Views Pro (priority 10), which replaces this filter's value.
		add_filter( PT_CV_PREFIX_ . 'custom_filters', [ $this, 'custom_filters' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_script' ] );
	}

	/**
	 * On the Content Views view edit screen, load the script that hides the
	 * Content Views "Common" and "Advance" filter groups for CiviCRM views.
	 */
	public function enqueue_admin_script() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! defined( 'PT_CV_DOMAIN' ) || $page !== PT_CV_DOMAIN . '-add' ) {
			return;
		}

		$file = $this->cvc->get_path() . 'assets/js/admin-filter-settings.js';
		wp_enqueue_script(
			'content-views-civicrm-filter-settings',
			$this->cvc->get_url() . 'assets/js/admin-filter-settings.js',
			[ 'jquery' ],
			file_exists( $file ) ? (string) filemtime( $file ) : false,
			true
		);
		// The group labels as Content Views shows them, in the site language.
		wp_add_inline_script(
			'content-views-civicrm-filter-settings',
			'window.contentViewsCiviCRMFilterSettings = ' . wp_json_encode( [
				'labels' => [
					// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Content Views' own labels
					__( 'Common', 'content-views-query-and-display-post-page' ),
					// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Content Views' own labels
					__( 'Advance', 'content-views-query-and-display-post-page' ),
				],
			] ) . ';',
			'before'
		);
	}

	/**
	 * Filter post type list.
	 *
	 * Adds Contact post type to the Filter settings.
	 *
	 * @param array $types Post types list
	 *
	 * @return array $types Filtered post types list
	 * @since 0.1
	 */
	public function filter_post_types_list( $types ) {
		$types['civicrm'] = __( 'CiviCRM', 'content-views-civicrm' );

		return $types;
	}

	/**
	 * Add the CiviCRM filter settings to the Filter Settings tab.
	 *
	 * Content Views shows the value of this filter as a single settings
	 * block, right after the Content type setting. Other plugins use it too:
	 * Content Views Pro returns a wrapper block holding several blocks, each
	 * shown only for its own content type. The CiviCRM block is added to
	 * that wrapper, or the existing block and the CiviCRM block are put in a
	 * wrapper of the same form, so every plugin's settings are kept.
	 *
	 * @param array $options settings block from other plugins, if any
	 *
	 * @return array
	 */
	public function custom_filters( $options ) {
		$block = $this->get_civicrm_settings_block();
		if ( empty( $options ) || ! is_array( $options ) ) {
			return $block;
		}

		// Inside a wrapper, blocks are plain label + params groups.
		unset( $block['extra_setting'] );

		if ( $this->is_wrapper_block( $options ) ) {
			$options['params'][0]['params'][] = $block;

			return $options;
		}

		return [
			'label'         => [ 'text' => '' ],
			'extra_setting' => [
				'params' => [
					'width' => 12,
				],
			],
			'params'        => [
				[
					'type'   => 'group',
					'params' => [ $options, $block ],
				],
			],
		];
	}

	/**
	 * Whether a settings block is a wrapper holding a group of blocks, as
	 * returned by Content Views Pro.
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	private function is_wrapper_block( $options ) {
		return isset( $options['params'] ) && is_array( $options['params'] ) && count( $options['params'] ) === 1
		       && isset( $options['params'][0]['type'], $options['params'][0]['params'] )
		       && $options['params'][0]['type'] === 'group'
		       && is_array( $options['params'][0]['params'] )
		       && empty( $options['label']['text'] );
	}

	private function get_civicrm_settings_block() {
		return [
			'label'         => [ 'text' => __( 'CiviCRM filter', 'content-views-civicrm' ) ],
			'extra_setting' => [
				'params' => [
					'wrap-class' => PT_CV_Html::html_panel_group_class(),
					'wrap-id'    => PT_CV_Html::html_panel_group_id( PT_CV_Functions::string_random() )
				]
			],
			'dependence'    => [ 'content-type', [ 'civicrm' ] ],
			'params'        => [
				[
					'type'   => 'group',
					'params' => [
						// data processor
						[
							'label'  => [ 'text' => __( 'Data processor', 'content-views-civicrm' ) ],
							'params' => [
								[
									'type'    => 'select',
									'name'    => 'data_processor_id',
									'options' => $this->get_data_processor(),
									'class'   => 'select2',
									'std'     => '',
									'desc'    => __( 'Select the data you want to list here.', 'content-views-civicrm' )
								]
							]
						],
						// sort
						[
							'label'  => [ 'text' => __( 'Sorting', 'content-views-civicrm' ) ],
							'params' => [
								[
									'type' => 'text',
									'name' => 'civicrm_sort',
									'std'  => '',
									'desc' => __( 'Set the sorting order.', 'content-views-civicrm' )
								]
							]
						],
						// limit
						[
							'label'  => [ 'text' => __( 'Limit', 'content-views-civicrm' ) ],
							'params' => [
								[
									'type' => 'number',
									'name' => 'civicrm_limit',
									'std'  => '',
									'desc' => __( 'Set the limit of the result.', 'content-views-civicrm' )
								]
							]
						],
					]
				]
			]
		];
	}

	private function get_data_processor() {
		if ( ! $this->cvc->api->is_dp_enabled() ) {
			return [];
		}
		$result  = $this->cvc->api->call_values( 'DataProcessorOutput', 'get', [
			'sequential'                  => 1,
			'type'                        => "api",
			'api.DataProcessor.getsingle' => [ 'id' => "\$value.data_processor_id" ],
		] );
		$options = [];
		foreach ( $result as $dp ) {
			$options[ $dp['data_processor_id'] ] = $dp['api.DataProcessor.getsingle']['title'];
		}

		return $options;
	}
}
