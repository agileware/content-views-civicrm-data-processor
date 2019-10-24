<?php

/**
 * Content_Views_CiviCRM_Settings_Display class.
 */
class Content_Views_CiviCRM_Filter {

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

	public $dpid = 0;

	public $live_filter_parameters = [];

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
		if ( $this->cvc->has_pro() ) {
			add_filter( PT_CV_PREFIX_ . 'before_output_html', array( $this, 'before_output_html' ), 999 );
		}
	}

	public function before_output_html( $html ) {
		if ( current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'content-type', true ) ) != 'civicrm'
		     || ! $this->prepare() ) {
			return $html;
		}

		foreach ( $this->fields as $field ) {
			if ( $field['options'] ) {
				$html .= $this->select_input_html( $field['name'], $field['title'], $field['options'], true );
			} else {
				$html .= $this->text_input_html( $field['name'], $field['title'], true );
			}
		}

		return $html;
	}

	/**
	 * Prepare for the fields
	 *
	 * @return bool success or fail
	 */
	private function prepare() {
		if ( $_POST['query'] ) {
			parse_str( $_POST['query'], $query );
		}
		$this->live_filter_parameters = $query ?? [];
		$this->dpid                   = current( PT_CV_Functions::settings_values_by_prefix( PT_CV_PREFIX . 'data_processor_id', true ) );
		if ( empty( $this->dpid ) ) {
			return false;
		}
		$this->fields = $this->cvc->api->call_values( 'DataProcessorFilter', 'get', [
			'sequential'        => 1,
			'is_exposed'        => 1,
			'data_processor_id' => $this->dpid
		] );
		$dp           = $this->cvc->api->call_values( 'DataProcessorOutput', 'get', [
			'sequential'        => 1,
			'type'              => "api",
			'data_processor_id' => $this->dpid
		] );
		if ( empty( $dp ) ) {
			return false;
		}
		$dp = array_shift( $dp );
		// add options for select dropdown
		$result = $this->cvc->api->call_values( $dp['api_entity'], 'getfields', [ 'api_action' => $dp['api_action'] ] );
		foreach ( $this->fields as $key => $field ) {
			if ( $result[ $field['name'] ] && $result[ $field['name'] ]['options'] ) {
				$field['options'] = $result[ $field['name'] ]['options'];
			}
			$this->fields[ $key ] = $field;
		}

		return true;
	}

	/**
	 * Generate html for text input
	 *
	 * @param string $name
	 * @param string $title this is the label
	 * @param string $default the default value| true if you want the input value remains
	 * @param string $placeholder
	 * @param string $class
	 *
	 * @return string
	 */
	protected function text_input_html( $name, $title, $default = '', $placeholder = '', $class = 'cvp-live-filter cvp-search-box' ) {
		global $pt_cv_id;
		if ( $default === true ) {
			$default = $this->live_filter_parameters[ $name ] ?? '';
		}
		$html = sprintf(
			'<div class="%s" data-name="%s" data-sid="%s"><label for="%s" class="cvp-label">%s</label><input id="%s" type="text" name="%s" value="%s" data-nosubmit="true" placeholder="%s"></div>',
			$class,
			$name,
			$pt_cv_id,
			$name,
			$title,
			$name,
			$name,
			$default,
			$placeholder
		);

		return $html;
	}

	/**
	 * Generate html for dropdown
	 *
	 * @param string $name
	 * @param string $title the label
	 * @param array $options html string
	 * @param string $selected the default value| true if you want the input value remains
	 * @param bool $allow_none the text for empty value, true for 'None' and false for 'All'
	 * @param string $class
	 *
	 * @return string
	 */
	protected function select_input_html( $name, $title, $options, $selected = '', $allow_none = false, $class = 'cvp-live-filter cvp-dropdown' ) {
		global $pt_cv_id;
		$options_html = '<option value="">All</option>';
		if ( $allow_none ) {
			$options_html = '<option value="">None</option>';
		}
		if ( $selected === true ) {
			$selected = $this->live_filter_parameters[ $name ] ?? '';
		}
		foreach ( $options as $key => $text ) {
			$selected_html = '';
			if ( $key == $selected ) {
				$selected_html = 'selected';
			}
			$options_html .= sprintf( '<option value="%s" %s>%s</option>', $key, $selected_html, $text );
		}
		$html = sprintf(
			'<div class="%s" data-name="%s" data-sid="%s"><label class="cvp-label">%s</label><select name="%s">%s</select></div>',
			$class,
			$name,
			$pt_cv_id,
			$title,
			$name,
			$options_html
		);

		return $html;
	}
}
