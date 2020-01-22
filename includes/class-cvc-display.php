<?php

/**
 * Content_Views_CiviCRM_Settings_Display class.
 */
class Content_Views_CiviCRM_Display {

	/**
	 * Plugin instance reference.
	 * @since 0.1
	 * @var Content_Views_CiviCRM Reference to plugin instance
	 */
	protected $cvc;

	/**
	 * fields.
	 *
	 * @since 0.1
	 * @var array
	 */
	public $fields = [];

	/**
	 * fields for multi-value
	 *
	 * @since 0.1
	 * @var array
	 */
	public $multi_value_fields = [];

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
		add_filter( PT_CV_PREFIX_ . 'all_display_settings', [ $this, 'filter_all_display_settings' ] );
		add_filter( PT_CV_PREFIX_ . 'field_item_html', [ $this, 'contact_fields_html' ], 5, 3 );
		add_filter( PT_CV_PREFIX_ . 'fields_html', [ $this, 'fields_html' ], 10, 2 );
		add_filter( PT_CV_PREFIX_ . 'total_posts', [ $this, 'total_posts' ] );
		add_filter( PT_CV_PREFIX_ . 'field_href', [ $this, 'field_href' ], 10, 2 );
		add_filter( PT_CV_PREFIX_ . 'link_html', [ $this, 'link_html' ], 10, 2 );
	}

	/**
	 * remove unwanted fields
	 *
	 * @param $html
	 * @param $post
	 *
	 * @return mixed
	 */
	public function fields_html( $html, $post ) {
		if ( $post->post_type != 'civicrm' ) {
			return $html;
		}
		foreach ( $html as $key => $item ) {
			if ( ! in_array( $key, array_keys( $this->fields ) ) ) {
				unset( $html[ $key ] );
			}
		}

		return $html;
	}

	public function total_posts( $total ) {
		// fixme may not need this
		$view_settings = array_map( 'cv_esc_sql', [] );
		$content_type  = PT_CV_Functions::setting_value( PT_CV_PREFIX . 'content-type', $view_settings );
		$args          = PT_CV_Functions::view_filter_settings( $content_type, $view_settings );
		PT_CV_Functions::view_get_pagination_settings( $dargs, $args, [] );
		if ( $args['post_type'] != 'civicrm' ) {
			return $total;
		}
		$args       = apply_filters( PT_CV_PREFIX_ . 'query_parameters', $args );
		$api_params = $args['civicrm_api_params'];
		$dp         = $this->cvc->api->get_data_processor_by_id( $args['data_processor_id'] );
		$result     = $this->cvc->api->call( $dp['api_entity'], $dp['api_count_action'], $api_params );
		if ( ! $result['is_error'] ) {
			return $result;
		}

		return 0;
	}

	/**
	 * Filter display settitngs.
	 *
	 * @param array $args The display args
	 *
	 * @return array $args Filtered display args
	 * @since 0.1
	 */
	public function filter_all_display_settings( $args ) {
		if ( empty( $this->fields ) ) {
			$id           = current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'data_processor_id', true ) );
			$this->fields = $this->get_display_fields_by_data_processor( $id );
		}

		$fields = $this->fields;
		unset( $fields['id'] );
		unset( $fields['title'] );
		$fields = array_flip( $fields );

		$args['fields'] = array_merge( $args['fields'], $fields );

		return $args;
	}

	/**
	 * Render contact fields html.
	 *
	 * @param string $html
	 * @param string $field_name
	 * @param WP_Post $post WP_Post
	 *
	 * @return string $html
	 */
	public function contact_fields_html( $html, $field_name, $post ) {

		if ( $post->post_type != 'civicrm' ) {
			return $html;
		}

		if ( empty( $this->fields ) ) {
			$this->fields = $this->get_display_fields_by_data_processor( $post->data_processor_id );
		}
		$label_html = "<strong>{$this->fields[$field_name]}</strong>: ";
		// hide the label if the title start with 'hide_label'
		if ( $this->has_option( $field_name, Content_Views_CiviCRM_Dp_Option::HIDE_LABEL) ) {
			$label_html = '';
		}
		$value_html = $post->$field_name;
		// if it is a link
		if ( $this->has_option( $field_name, Content_Views_CiviCRM_Dp_Option::HREF) ) {
			$href = $post->$field_name;
			if ( strpos( $post->$field_name, 'http' ) !== 0 ) {
				$href = 'https://' . $href;
			}
			$value_html = "<a href='{$href}' target='_blank'>{$value_html}</a>";
		}
		// display list if the field is multi-value field
		if ( $this->has_option( $field_name, Content_Views_CiviCRM_Dp_Option::LIST) ) {
			$values     = explode( ',', $post->$field_name );
			$value_html = '';
			foreach ( $values as $value ) {
				$value_html .= "<li>$value</li>";
			}
			$value_html = "<ul>$value_html</ul>";
		}

		return $html = "<div class='col-md-12 pt-cv-ctf-column'><div class='{$field_name} pt-cv-custom-fields pt-cv-ctf-post_field_1'><div class='pt-cv-ctf-value'>{$label_html}{$value_html}</div></div></div>";

	}

	/**
	 * Contact fields options.
	 * @return array $options
	 * @since 0.1
	 */
	public function get_display_fields_by_data_processor( $id ) {
		$dp = $this->cvc->api->call_values( 'DataProcessorOutput', 'get', [
			'sequential'        => 1,
			'type'              => "api",
			'data_processor_id' => $id
		] );
		$dp = array_shift( $dp );

		return array_reduce( $this->get_fields_for( $dp['api_entity'], $dp['api_action'] ), function ( $fields, $field ) {
			if ( $field['api.return'] ) {
				$fields[ $field['name'] ] = $field['title'];
				// the only evidence for a multi-value field - options
				if ( !empty( $field['options'] ) ) {
					$this->multi_value_fields[] = $field['name'];
				}
			}

			return $fields;

		}, [] );

	}

	/**
	 * the link url
	 *
	 * @param string $link
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	public function field_href( $link, $post ) {
		if ( $post->post_type != 'civicrm' ) {
			return $link;
		}
		static $url = null;
		if ( $url === null ) {
			$settingsValueByPrefix = PT_CV_Functions::settings_values_by_prefix(
				PT_CV_PREFIX . 'civicrm_link_url',
				true
			);
			$url = array_shift( $settingsValueByPrefix );
		}

		return $url ? $url . '?id=' . $post->ID : '';
	}

	/**
	 * remove a tag if it is not a link
	 *
	 * @param string $html
	 * @param array $args [wp_post, html, class]
	 *
	 * @return string
	 */
	public function link_html( $html, $args ) {
		if ( $args[0]->post_type != 'civicrm' ) {
			return $html;
		}
		static $url = null;
		if ( $url === null ) {
			$settingsValueByPrefix = PT_CV_Functions::settings_values_by_prefix(
				PT_CV_PREFIX . 'civicrm_link_url',
				true
			);
			$url = array_shift( $settingsValueByPrefix );
		}
		if ( ! empty( $url ) ) {
			return $html;
		}
		$html = preg_replace( '/<a.*>(.*)<\/a>/', '$1', $html );

		return $html;
	}

	/**
	 * Retrieve fields for an entity.
	 *
	 * @param $entity
	 * @param $action
	 *
	 * @return array $options
	 * @since 0.1
	 */
	public function get_fields_for( $entity, $action ) {

		return $this->cvc->api->call_values( $entity, 'getfields', [ 'action' => $action ] );

	}

	private function has_option( $name, $option ) {
		return $this->cvc->dp_options->has_option( $name, $option );
	}

}
