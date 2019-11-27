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
				$options[]           = $group;

				// add field display settings for civi content
				$prefix              = 'field-';
				$options[]           = [
					'label'         => [ 'text' => __('Title and links', 'content_views_civicrm') ],
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
								[
									'label' => ['text' => __('Title content', 'content_views_civicrm')],
									'params' => [
										[
											'type' => 'text',
											'name' => 'civicrm_title',
											'std' => '',
											'desc' => __('Use ${field_name} for placeholder', 'content_views_civicrm')
										]
									]
								],
								[
									'label'  => [ 'text' => __( 'CiviCRM link URL', 'content-views-civicrm' ) ],
									'params' => [
										[
											'type' => 'text',
											'name' => 'civicrm_link_url',
											'std'  => '',
											'desc' => __( 'The url when the link get clicked. Note that the id will be passed as a parameter', 'content-views-civicrm' )
										]
									]
								],
								PT_CV_Settings::title_heading_tag( $prefix )
							]
						]
					]
				];
			} else {
				$options[] = $group;
			}

			return $options;
		}, [] );
	}
}
