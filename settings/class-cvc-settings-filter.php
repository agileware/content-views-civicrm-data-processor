<?php

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
		add_filter( PT_CV_PREFIX_ . 'filter_settings_final', [ $this, 'final_filter_settings' ] );
		add_filter( PT_CV_PREFIX_ . 'custom_filters', [ $this, 'custom_filters' ] );
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
	 * This filter can only add one setting block
	 * and cannot modify other blocks dependencies
	 *
	 * @param $options
	 *
	 * @return array
	 */
	public function custom_filters( $options ) {
		if ( ! empty( $options ) ) {
			// Some other plugins using this filter
			return $options;
		}
		$options = $this->get_civicrm_settings_block();

		return $options;
	}

	public function final_filter_settings( $options ) {
		$all_post_types_but_civicrm = array_diff( array_keys( PT_CV_Values::post_types() ), [ 'civicrm' ] );

		return array_reduce( $options,
			function ( $options, $group ) use ( $all_post_types_but_civicrm ) {
				if ( ! is_array( $group ) ) {
					$options[] = $group;
					return $options;
				}
				if ( $group['label']['text'] == 'Common' || $group['label']['text'] == 'Advance' ) {
					$group['dependence'] = [ 'content-type', $all_post_types_but_civicrm ];
				}
				$options[] = $group;
				if ( $group['label']['text'] == 'Content type' ) {
					$options[] = $this->get_civicrm_settings_block();
				}

				return $options;
			},
			[] );
	}

	private function get_civicrm_settings_block() {
		// WPCV-24 We only want to add the settings block once
		static $is_added = false;
		if ( $is_added ) {
			return [];
		}
		$is_added = true;

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
