<?php

class Content_Views_CiviCRM_Dp_Option {

	const HREF = 'r';
	const HIDE_LABEL = 'h';
	const LIST = 'l';
	const USER_CONTACT_ID = 'i';
	const CONTACT_NAME_SEARCH = 's';

	/**
	 * Plugin instance reference.
	 * @since 0.1
	 * @var Content_Views_CiviCRM Reference to plugin instance
	 */
	protected $cvc;

	protected $cached_options = [];

	/**
	 * Constructor.
	 *
	 * @param object $cvc Reference to plugin instance
	 */
	public function __construct( $cvc ) {
		$this->cvc = $cvc;
	}

	public function parse( $name ) {
		$options = explode( '_', $name );
		if ( !in_array( 'cvc', $options) ) {
			$this->cached_options[ $name ] = [];

			return;
		}
		$options = end( $options );
		$this->cached_options[ $name ] = str_split( $options );
	}

	public function has_option( $name, $option ) {
		if ( ! $this->cached_options[ $name ] ) {
			$this->parse( $name );
		}

		return in_array( $option, $this->cached_options[ $name ] );
	}
}
